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
     * @param Variants $extensionFactory
     * @param StockRegistryInterface $extensionFactory
     * @param ProductExtensionFactory $extensionFactory
     */
    public function __construct(
        Variants $variantsHelper,
        StockRegistryInterface $stockRegistry,
        ProductExtensionFactory $extensionFactory
    ) {
        $this->variantsHelper = $variantsHelper;
        $this->stockRegistry = $stockRegistry;
        $this->extensionFactory = $extensionFactory;
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


        return $extension;
    }
}
