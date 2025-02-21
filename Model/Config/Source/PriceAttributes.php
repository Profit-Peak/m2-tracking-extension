<?php

namespace ProfitPeak\Tracking\Model\Config\Source;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class PriceAttributes implements OptionSourceInterface
{
    protected $attributeRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Retrieve the list of all product attributes as dropdown options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        try {
            // Get all product attributes
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $attributes = $this->attributeRepository->getList(\Magento\Catalog\Model\Product::ENTITY, $searchCriteria)->getItems();

            foreach ($attributes as $attribute) {
                /** @var Attribute $attribute */
                if ($attribute->getFrontendLabel()) { // Ensure label exists
                    $options[] = [
                        'value' => $attribute->getAttributeCode(),
                        'label' => __($attribute->getFrontendLabel())
                    ];
                }
            }
        } catch (\Exception $e) {
            $options[] = ['value' => '', 'label' => __('Unable to load attributes')];
        }

        return $options;
    }
}
