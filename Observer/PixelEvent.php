<?php
/**
 * Profit Peak
 *
 * @category  Profit Peak
 * @package   ProfitPeak_Tracking
 * @author    Profit Peak Team <admin@profitpeak.io>
 * @copyright Copyright Profit Peak (https://profitpeak.io/)
 */
namespace ProfitPeak\Tracking\Observer;

use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\State;
use Magento\Framework\Registry;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use ProfitPeak\Tracking\Helper\Data;
use ProfitPeak\Tracking\Helper\Pixel;
use ProfitPeak\Tracking\Logger\ProfitPeakLogger;

class PixelEvent implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Pixel
     */
    protected $pixel;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var ProfitPeakLogger
     */
    protected $logger;

    public function __construct(
        RequestInterface $request,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        Data $helper,
        Pixel $pixel,
        Registry $registry,
        State $state,
        ProfitPeakLogger $logger,
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->pixel = $pixel;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->state = $state;
        $this->logger = $logger;
    }
    public function execute(Observer $observer)
    {
        try {
            /** @var ResponseHttp $response */
            $response = $observer->getEvent()->getResponse();
            $url = $this->request->getUriString();
            $eventName = 'page_viewed';

            if ($this->state->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML) {
                return;
            }

            // Skip if it's not a page view
            if ($this->request->getMethod() !== 'GET') {
                return;
            }

            // Skip REST & GraphQL requests
            if (str_contains($url, 'graphql?query') || str_contains($url, 'rest/')) {
                return true;
            }

            // Check if should skip tracking
            if ($this->shouldSkipTracking($response, $url)) {
                return;
            }

            $quoteId = null;
            $customerId = null;
            $customerEmail = null;

            $quote = $this->checkoutSession->getQuote();
            $quoteId = $quote ? $quote->getId() : null;

            $customerSession = $this->customerSession;

            $pageTitle = null;
            // Get the HTML body content
            $html = $response->getBody();

            // Extract the title using a regex
            preg_match('/<title>(.*?)<\/title>/', $html, $matches);
            if (!empty($matches[1])) {
                $pageTitle = $matches[1];
            }

            $currentProduct = $this->registry->registry('current_product');

            $pixel = $this->pixel->formatPixelEvent(
                $this->helper->getAnalyticsId($this->storeManager->getStore()->getId()),
                $url,
                $this->request->getServer(),
                $this->storeManager->getStore(),
                $pageTitle,
                (new \DateTime())->format(\DateTime::ATOM),
                $eventName,
                $this->helper->getCustomerIP(),
                $quoteId,
                $customerSession,
                $currentProduct,
                null,
                session_id()
            );
            $this->pixel->sendPixel($pixel);

        } catch (\Throwable $e) {
            $this->logger->info('Tracking event observer error - '. $e->getMessage());
        }
    }

    private function shouldSkipTracking(ResponseHttp $response, $url): bool
    {
        // Skip if it's an AJAX request
        if ($this->request->isAjax()) {
            return true;
        }

        // Skip if it's a redirect (3xx status codes)
        if ($response->getStatusCode() >= 300) {
            return true;
        }

        // Skip static files, media, etc.
        $pathInfo = $this->request->getPathInfo();
        $skipPaths = [
            '/favicon.ico'
        ];

        foreach ($skipPaths as $skipPath) {
            if (stripos($pathInfo, $skipPath) !== false) {
                return true;
            }
        }

        return false;
    }

}
