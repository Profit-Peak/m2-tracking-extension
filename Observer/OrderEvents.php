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

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;

use ProfitPeak\Tracking\Helper\Pixel;
use ProfitPeak\Tracking\Helper\Data;
use ProfitPeak\Tracking\Logger\CustomLogger;

class OrderEvents implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Pixel
     */
    protected $pixel;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var string
     */
    protected $syncOrderTable;

    /**
     * @var string
     */
    protected $syncProductTable;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomLogger
     */
    protected $logger;

    public function __construct(
        ResourceConnection $resourceConnection,
        CustomerSession $customerSession,
        Pixel $pixel,
        Data $helper,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        State $state,
        CustomLogger $logger,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->pixel = $pixel;
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;

        $this->connection = $this->resourceConnection->getConnection();
        $this->syncOrderTable = $this->resourceConnection->getTableName('profit_peak_order_sync');
        $this->syncProductTable = $this->resourceConnection->getTableName('profit_peak_product_sync');
        $this->state = $state;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            if ($order) {
                if ($this->getOrder($order)) {
                    $this->handleOrderUpdate($order);
                } else {
                    $this->handleOrderCreation($order);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->info('Order event observer error - '. $e->getMessage());
        }
    }

    private function getOrder($order)
    {
        try {
            $orderId = $order->getId();
            $storeId = $order->getStoreId();

            // Check if the order is already in the sync table
            $select = $this->connection->select()
                ->from($this->syncOrderTable, ['order_id'])
                ->where('order_id = ?', $orderId)
                ->where('store_id = ?', $storeId);

            return $this->connection->fetchOne($select);
        } catch (\Throwable $e) {
            $this->logger->info('Order event getOrder() error - '. $e->getMessage());
        }

        return null;
    }

    private function handleOrderCreation($order)
    {
        try {
            $requestContent = json_decode($this->request->getContent(), true);

            $orderId = $order->getId();
            $quoteId = $order->getQuoteId();
            $storeId = $order->getStoreId();
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

            // session customer
            $customerSession = $this->customerSession;

            $userAgent = $this->request->getServer('HTTP_USER_AGENT'); // Capture the user agent
            $layoutArea = $this->state->getAreaCode();

            $this->connection->insertOnDuplicate($this->syncOrderTable, [
                'order_id' => $orderId,
                'store_id' => $storeId,
                'sent' => 0,
                'status' => 'created',
                'user_agent' => $userAgent,
                'area_code' => $layoutArea,
            ], [
                'sent',
                'status'
            ]);

            // Process product sync update
            $this->updateProductSync($order);

            $pageTitle = null;
            $url = $this->request->getUriString();

            // Send pixel event
            $pixel = $this->pixel->formatPixelEvent(
                $this->helper->getAnalyticsId($this->storeManager->getStore()->getId()),
                $url,
                $this->request->getServer(),
                $this->storeManager->getStore(),
                $pageTitle,
                (new \DateTime())->format(\DateTime::ATOM),
                'order_placed',
                $order->getRemoteIp(),
                $quoteId,
                $customerSession,
                null,
                $order,
                isset($requestContent['operationName']) ? $requestContent['variables']['cartId'] : session_id(),
            );

            $this->pixel->sendPixel($pixel);
        } catch (\Throwable $e) {
            $this->logger->info('Order event handleOrderCreation() error - '. $e->getMessage());
        }
    }

    private function handleOrderUpdate($order)
    {
        try {
            $orderId = $order->getId();
            $orderStatus = $order->getStatus();
            $storeId = $order->getStoreId();
            $userAgent = $this->request->getServer('HTTP_USER_AGENT');
            $layoutArea = $this->state->getAreaCode();

            $this->connection->insertOnDuplicate($this->syncOrderTable, [
                'order_id' => $orderId,
                'store_id' => $storeId,
                'sent' => 0,
                'status' => $orderStatus,
                'user_agent' => $userAgent,
                'area_code' => $layoutArea,
            ], [
                'sent',
                'status',
            ]);

            // Process product sync update
            $this->updateProductSync($order);
        } catch (\Throwable $e) {
            $this->logger->info('Order event handleOrderUpdate() error - '. $e->getMessage());
        }
    }

    /**
     * Updates product sync status for products involved in the order.
     */
    private function updateProductSync($order)
    {
        try {
            foreach ($order->getAllItems() as $item) {
                $productId = $item->getProductId();
                $storeId = $order->getStoreId();

                $this->connection->insertOnDuplicate($this->syncProductTable, [
                    'product_id' => $productId,
                    'store_id' => $storeId,
                    'sent' => 0,
                    'status' => 'order_placed',
                ], [
                    'sent',
                    'status'
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->info('Order event updateProductSync() error - '. $e->getMessage());
        }
    }
}
