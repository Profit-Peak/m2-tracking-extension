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
use ProfitPeak\Tracking\Logger\CustomLogger;

class ProductEvents implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var CustomLogger
     */
    protected $logger;

    public function __construct(
        ResourceConnection $resourceConnection,
        CustomLogger $logger,
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $product = null;

            if ($event->getName() === 'catalog_product_save_after') {
                $product = $event->getProduct();
                $this->handleProductSave($product);
            } elseif ($event->getName() === 'catalog_product_delete_after') {
                $product = $event->getProduct();
                $this->handleProductDeletion($product);
            }
        } catch (\Throwable $e) {
            $this->logger->info('Product event observer error - '. $e->getMessage());
        }
    }

    private function handleProductSave($product)
    {
        try {
            $productId = $product->getId();
            $storeId = $product->getStoreId();

            $connection = $this->resourceConnection->getConnection();
            $syncTable = $this->resourceConnection->getTableName('profit_peak_product_sync');
            $productStatus = $product->getStatus();

            $connection->update(
                $syncTable,
                [
                    'sent' => 0,
                    'status' => $productStatus
                ],
                ['product_id = ?' => $productId]
            );
        } catch (\Throwable $e) {
            $this->logger->info('Product event handleProductSave() error - '. $e->getMessage());
        }
    }

    private function handleProductDeletion($product)
    {
        try {
            $productId = $product->getId();
            $storeId = $product->getStoreId();

            $connection = $this->resourceConnection->getConnection();
            $syncTable = $this->resourceConnection->getTableName('profit_peak_product_sync');

            $connection->update(
                $syncTable,
                [
                    'sent' => 0,
                    'status' => 'deleted',
                    'deleted_at' => (new \DateTime())->format('Y-m-d H:i:s')
                ],
                ['product_id = ?' => $productId]
            );
        } catch (\Throwable $e) {
            $this->logger->info('Product event handleProductDeletion() error - '. $e->getMessage());
        }
    }
}
