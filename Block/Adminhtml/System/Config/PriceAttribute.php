<?php
/**
* Profit Peak
*
* @category  Profit Peak
* @package   ProfitPeak_Tracking
* @author    Profit Peak Team <admin@profitpeak.io>
* @copyright Copyright Profit Peak (https://profitpeak.io/)
*/

namespace ProfitPeak\Tracking\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class PriceAttribute extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        // Set default value if it's not already set
        if (!$element->getValue()) {
            $element->setValue('price');
        }
        return parent::_getElementHtml($element);
    }
}
