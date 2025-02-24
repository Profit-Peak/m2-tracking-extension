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

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use ProfitPeak\Tracking\Helper\Config;

class AnalyticsInfo extends Field
{
    protected $_template = 'ProfitPeak_Tracking::system/config/analytics_info.phtml';

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getAnalyticsJsUrl()
    {
        return Config::PROFIT_PEAK_PIXEL_URL;
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->toHtml();
    }
}
