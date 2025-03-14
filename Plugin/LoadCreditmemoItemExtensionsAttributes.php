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

use Magento\Sales\Api\Data\CreditmemoItemExtensionInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\CreditmemoItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use ProfitPeak\Tracking\Logger\ProfitPeakLogger;

class LoadCreditmemoItemExtensionsAttributes
{
    /**
     * @var CreditmemoItemExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var OrderItemExtensionFactory
     */
    protected $itemExtensionFactory;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProfitPeakLogger
     */
    private $logger;

    /**
     * @param CreditmemoItemExtensionFactory $extensionFactory
     * @param OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(
        CreditmemoItemExtensionFactory $extensionFactory,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderItemExtensionFactory $itemExtensionFactory,
        ProductRepositoryInterface $productRepository,
        ProfitPeakLogger $logger
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->orderItemRepository = $orderItemRepository;
        $this->itemExtensionFactory = $itemExtensionFactory;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Loads creditmemo item entity extension attributes
     *
     * @param CreditmemoItemInterface $entity
     * @param CreditmemoItemExtensionInterface|null $extension
     * @return CreditmemoItemExtensionInterface
     */
    public function afterGetExtensionAttributes(
        CreditmemoItemInterface $entity,
        CreditmemoItemExtensionInterface $extension = null
    ) {
        try {
            if ($extension === null) {
                $extension = $this->extensionFactory->create();
            }

            $orderItem = $this->orderItemRepository->get($entity->getOrderItemId());

            $productType = $orderItem->getProductType();

            if ($productType === 'grouped') {
                $productOptions = $orderItem->getProductOptions();
                $itemExtension = $orderItem->getExtensionAttributes();

                if ($itemExtension === null) {
                    $itemExtension = $this->itemExtensionFactory->create();
                }

                $productCode = $productOptions['super_product_config']['product_code'] ?? null;
                $productType = $productCode !== null ? $productOptions['super_product_config'][$productCode] : null;
                $productId = $productOptions['super_product_config']['product_id'] ?? null;

                if ($productType === 'grouped' && !empty($productId)) {
                    $groupedProduct = $this->productRepository->getById($productId);

                    if (!empty($groupedProduct)) {
                        $itemExtension->setGroupedProduct($groupedProduct);
                    }
                }
            } else if ($productType === 'bundle') {
                $product = $this->productRepository->getById($orderItem->getProductId());
                $dynamicPrice = $product->getPriceType() == \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC;
                $extension->setDynamicPrice($dynamicPrice);
            }

            $extension->setOrderItem($orderItem);
        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->info('Database error occurred - '. $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->info('General error - '. $e->getMessage());
        }
        return $extension;
    }
}
