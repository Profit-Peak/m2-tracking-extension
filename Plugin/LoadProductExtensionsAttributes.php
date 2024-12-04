<?php
/**
* Profit Peak
*
* @category  Profit Peak
* @package   ProfitPeak_Tracking
* @author    Profit Peak Team <admin@profitpeak.io>
* @copyright Copyright Profit Peak (https://profitpeak.io/)
*/
namespace ProfitPeak\Tracking\Plugin;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;

use ProfitPeak\Tracking\Helper\Variants;
use ProfitPeak\Tracking\Logger\ProfitPeakLogger;

class LoadProductExtensionsAttributes
{
    /**
     * @var ProductExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var Variants
     */
    private $variantsHelper;

    /**
     * @var ProfitPeakLogger
     */
    private $logger;


    /**
     * @param Variants $extensionFactory
     * @param StockRegistryInterface $extensionFactory
     * @param ProductExtensionFactory $extensionFactory
     */
    public function __construct(
        Variants $variantsHelper,
        StockRegistryInterface $stockRegistry,
        ProductExtensionFactory $extensionFactory,
        ProfitPeakLogger $logger
    ) {
        $this->variantsHelper = $variantsHelper;
        $this->stockRegistry = $stockRegistry;
        $this->extensionFactory = $extensionFactory;
        $this->logger = $logger;
    }

    /**
     * Loads product entity extension attributes
     *
     * @param ProductInterface $entity
     * @param ProductExtensionInterface|null $extension
     * @return ProductExtensionInterface
     */
    public function afterGetExtensionAttributes(
        ProductInterface $entity,
        ProductExtensionInterface $extension = null
    ) {
        try {
            if ($extension === null) {
                $extension = $this->extensionFactory->create();
            }

            $brand = $entity->getAttributeText('manufacturer');
            if ($brand) {
                $extension->setBrand($brand);
            }

            $extension->setVariants($this->variantsHelper->getVariants($entity));

            if ($extension->getStockItem() === null) {
                $stockItem = $this->stockRegistry->getStockItem($entity->getId());

                if ($stockItem) {
                    $extension->setStockItem($stockItem);
                }
            }


        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->info('Database error occurred - '. $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->info('General error - '. $e->getMessage());
        }
        return $extension;
    }
}
