<?php
/**
* Profit Peak
*
* @category  Profit Peak
* @package   ProfitPeak_Tracking
* @author    Profit Peak Team <admin@profitpeak.io>
* @copyright Copyright Profit Peak (https://profitpeak.io/)
*/

namespace ProfitPeak\Tracking\Block\Adminhtml;

use ProfitPeak\Tracking\Helper\Data;

class Extensions extends \Magento\Config\Block\System\Config\Form\Field
{
	protected $_helper;
	const CHECK_TEMPLATE = 'system/config/extensions.phtml';

	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		Data $helper
	) {
		parent::__construct($context);
		$this->_helper = $helper;
	}

	protected function _prepareLayout()
	{
		parent::_prepareLayout();
		if (!$this->getTemplate()) {
			$this->setTemplate(static::CHECK_TEMPLATE);
		}
		return $this;
	}

	public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	{
		$this->setElement($element);
		return $this->toHtml();
	}

	public function getModuleList()
	{
		return $this->_helper->checkModule();
	}
}
