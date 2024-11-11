<?php
/**
* Profit Peak
*
* @category  Profit Peak
* @package   ProfitPeak_Tracking
* @author    Profit Peak Team <admin@profitpeak.io>
* @copyright Copyright Profit Peak (https://profitpeak.io/)
*/

namespace ProfitPeak\Tracking\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Variants extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var Product
     */
    protected $_productHelper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        Product $productHelper,
        ProductRepositoryInterface $productRepository,
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productHelper = $productHelper;
        $this->_productRepository = $productRepository;
    }


    protected function buildConfigVariants($product)
    {
        $childIds = $product->getTypeInstance()->getChildrenIds($product->getId());

        $variants = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if ($childIds) {
            if (isset($childIds[0])) {
                $productCollection = $this->_productCollectionFactory->create();
                $productCollection->addAttributeToSelect('id')
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('sku')
                    ->addAttributeToSelect('price')
                    ->addAttributeToSelect('special_price')
                    ->addAttributeToSelect('status')
                    ->addAttributeToSelect('visibility')
                    ->addAttributeToSelect('type_id')
                    ->addAttributeToSelect('created_at')
                    ->addAttributeToSelect('updated_at')
                    ->addAttributeToSelect('manufacturer')
                    ->addAttributeToSelect('weight');
                $productCollection->addIdFilter($childIds[0]);
                $variants = $productCollection->getItems();
            }
        }
        return $variants;
    }

    protected function buildBundleVariants($product)
    {
        $variants = [];

        $optionsCollection = $product->getTypeInstance(true)->getSelectionsCollection(
            $product->getTypeInstance(true)->getOptionsIds($product),
            $product
        );

        foreach ($optionsCollection as $options) {
            if ($options->getTypeId() === 'simple') {
                $variants[] = $options;
            }
        }
        return $variants;
    }

    protected function buildGroupedVariants($product)
    {
        $variants = [];

        $options = $product->getTypeInstance(true)->getAssociatedProducts($product);
        foreach ($options as $option) {
            if ($option->getTypeId() === 'simple') {
                $variants[] = $option;
            }
        }
        return $variants;
    }

    public function getVariants(ProductInterface $product)
    {
        $variants = [];

        if ($product->getTypeId() == 'configurable') {
            $variants = $this->buildConfigVariants($product);
        } elseif ($product->getTypeId() == 'bundle') {
            $variants = $this->buildBundleVariants($product);
        } elseif ($product->getTypeId() == 'grouped') {
            $variants = $this->buildGroupedVariants($product);
        }

        return $variants;
    }
}
