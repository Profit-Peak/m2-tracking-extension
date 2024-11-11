<?php
namespace ProfitPeak\Tracking\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface as ConfigResource;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

use ProfitPeak\Tracking\Helper\Config;
use ProfitPeak\Tracking\Block\Adminhtml\System\Config\ConnectProfitPeak;

class SaveLicenseKey extends Action
{
    protected $configWriter;
    protected $resultJsonFactory;
    protected $layoutFactory;
    protected $storeManager;
    protected $timezone;
    protected $scopeConfig;
    protected $configResource;

    public function __construct(
        Action\Context $context,
        WriterInterface $configWriter,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone,
        ScopeConfigInterface $scopeConfig,
        ConfigResource $configResource
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->storeManager = $storeManager;
        $this->timezone = $timezone;
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $licenseKey = $this->getRequest()->getParam('license_key');
        $analyticsId = $this->getRequest()->getParam('analytics_id');
        $storeId = $this->getRequest()->getParam('store_id');

        try {
            if ($licenseKey && $storeId) {
                $this->configWriter->save(Config::XML_PATH_LICENSE_KEY, $licenseKey, ScopeInterface::SCOPE_STORES, $storeId);
                $this->configWriter->save(Config::XML_PATH_ANALYTICS_ID, $analyticsId, ScopeInterface::SCOPE_STORES, $storeId);

                // Clear config cache
                // This is necessary because upon saving the license key, it is not reflected in the connectUrl if clear cache is not executed
                $this->configResource->reinit();
                $connectUrl = $this->getProfitPeakConnectUrl($storeId);

                return $result->setData(['success' => true, 'message' => __('License key saved successfully.'), 'connect_url' => $connectUrl]);
            } else {
                return $result->setData(['success' => false, 'message' => __('License key or store ID is missing.')]);
            }
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => __('Error saving license key: ') . $e->getMessage()]);
        }
    }


    public function getProfitPeakConnectUrl($storeId = 0)
    {
        $store = $this->storeManager->getStore($storeId);
        $parsedUrl = parse_url($store->getBaseUrl(UrlInterface::URL_TYPE_WEB), PHP_URL_HOST);
        $timezone = $this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORES, $storeId);
        $licenseKey = $this->scopeConfig->getValue(Config::XML_PATH_LICENSE_KEY, ScopeInterface::SCOPE_STORES, $storeId);
        $weightUnit = $this->scopeConfig->getValue(
            'general/locale/weight_unit',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return Config::PROFIT_PEAK_URL
            . "url={$parsedUrl}"
            . "&storeId={$store->getId()}"
            . "&name=" . urlencode($store->getName())
            . "&timezone=" . urlencode($timezone ?: "")
            . "&licenseKey=" . urlencode($licenseKey ?: "")
            . "&weightUnit=" . urlencode($weightUnit ?: "")
            . "&currencyCode=" . urlencode($store->getCurrentCurrencyCode() ?: "");
    }
}
