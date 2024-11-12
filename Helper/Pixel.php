<?php
/**
 * Profit Peak
 *
 * @category  Profit Peak
 * @package   ProfitPeak_Tracking
 * @author    Profit Peak Team <admin@profitpeak.io>
 * @copyright Copyright Profit Peak (https://profitpeak.io)
 */

namespace ProfitPeak\Tracking\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\ClientInterface;

use ProfitPeak\Tracking\Helper\Config;
use ProfitPeak\Tracking\Logger\ProfitPeakLogger;

class Pixel extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var ProfitPeakLogger
     */
    protected $logger;

    public function __construct(
        Context $context,
        ClientInterface $client,
        ProfitPeakLogger $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function formatPixelEvent($analyticsId, $url, $server, $store, $pageTitle, $timestamp, $eventName, $ip, $quoteId, $customerSession, $product, $order = null, $clientId = null)
    {
        if($order) {
            $billingAddress = $order->getBillingAddress();
        }
        return [
            "name" => $eventName,
            "type" => "standard",
            "userId" => session_id(),
            "analyticsId" => $analyticsId,
            "clientId" => $clientId,
            "pageTitle" => $pageTitle,
            "websiteId" => $store->getWebsiteId(),
            "storeId" => $store->getId(),
            "documentHost" => $server['HTTP_HOST'],
            "documentHref" => $url,
            "documentReferrer" => $server['HTTP_REFERER'],
            "navigatorLanguage" => $server['HTTP_ACCEPT_LANGUAGE'],
            "userAgent" => $server['HTTP_USER_AGENT'],
            "timestamp" => $timestamp,
            "ipv4" => $ip,
            "ipv6" => null,
            "cartToken" => $quoteId,
            "customerId" => $customerSession ? $customerSession->getCustomerId() : null,
            "customerEmail" => $customerSession ? $customerSession->getCustomer()->getEmail() : null,
            "productId" => $product ? $product->getId() : null,
            "productName" => $product ? $product->getName() : null,
            "checkoutToken" => null,
            "orderId" => $order ? $order->getId() : null,
            "orderNumber" => $order ? $order->getIncrementId() : null,
            "orderEmail" => $order ? $order->getCustomerEmail() : null,
            "orderPhone" => $order ? $billingAddress->getTelephone() : null,
            "orderAdddress" => $order ? implode(', ', $billingAddress->getStreet()) : null,
        ];
    }


    public function sendPixel($data)
    {
        try {
            $this->client->setHeaders([
                'Content-Type' => 'application/json'
            ]);

            $this->client->post(Config::PROFIT_PEAK_PIXEL_URL, json_encode($data));
        } catch (\Throwable $e) {
            // Log the error message along with the data being sent
            $this->logger->error('Helper send pixel error - ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e
            ]);
        }
    }
}
