<?php
/**
 * Profit Peak
 *
 * @category  Profit Peak
 * @package   ProfitPeak_Tracking
 * @author    Profit Peak Team <admin@profitpeak.io>
 * @copyright Copyright Profit Peak (https://profitpeak.io/)
 */

namespace ProfitPeak\Tracking\Model\Api;

use ProfitPeak\Tracking\Api\StoreInterface;
use ProfitPeak\Tracking\Helper\Config;
use ProfitPeak\Tracking\Helper\Data;

use Magento\Store\Api\Data\StoreInterface as MagentoStoreInterface;
use Magento\Store\Api\Data\StoreExtensionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Store implements StoreInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var StoreExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        Response $response,
        DataObjectProcessor $dataObjectProcessor,
        StoreExtensionFactory $extensionFactory,
        TimezoneInterface $timezone,
        RequestInterface $request
    ) {
        $this->storeManager = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->response = $response;
        $this->extensionFactory = $extensionFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->timezone = $timezone;
        $this->request = $request;
    }

    /**
     * Get store details by store ID.
     *
     * @param int $store_id
     * @return void
     */
    public function getById($store_id)
    {
        $without_count = $this->request->getParam('without_count', '0') == '1';

        $data = [
            'version' => $this->helper->getVersion(),
            'data' => $this->executeGetData($store_id, $without_count),
        ];

        return $this->helper->sendJsonResponse($data);
    }

    /**
     * Get store details by store ID.
     *
     * @param int $store_id
     * @param bool $without_count
     * @return array
     */
    protected function executeGetData($store_id, $without_count = false)
    {
        $store = $this->storeManager->getStore($store_id);

        if (!$store) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('Store with ID "%1" does not exist.', $store_id));
        }

        $extension = $store->getExtensionAttributes();

        if ($extension === null) {
            $extension = $this->extensionFactory->create();
        }

        if (!$without_count) {
            $productCount = $this->getProductCountByStore($store->getId());
            $orderCount = $this->getOrderCountByStore($store->getId());
        }
        $weightUnit = $this->getWeightUnitByStore($store->getId());
        $licenseKey = $this->getLicenseKey($store->getId());
        $extensionEnabled = $this->isExtensionEnabled($store->getId());
        $headlessDetails = $this->getHeadlessDetails($store->getId());
        $timezone = $this->getTimezoneByStore($store->getId());

        $extension->setCurrencyCode($store->getCurrentCurrencyCode());

        if (isset($productCount)) {
            $extension->setProductCount($productCount);
        }

        if (isset($orderCount)) {
            $extension->setOrderCount($orderCount);
        }

        if (isset($weightUnit)) {
            $extension->setWeightUnit($weightUnit);
        }

        if (isset($licenseKey)) {
            $extension->setLicenseKey($licenseKey);
        }

        if (isset($extensionEnabled)) {
            $extension->setExtensionEnabled($extensionEnabled);
        }

        if (isset($headlessDetails)) {
            $extension->setHasHeadlessAttribute($headlessDetails['has_headless_attribute']);
            $extension->setHasPwaModule($headlessDetails['has_pwa_module']);
            $extension->setHasThemeId($headlessDetails['has_theme_id']);
        }

        if (isset($timezone)) {
            $extension->setTimezone($timezone);
        }

        return $this->dataObjectProcessor->buildOutputDataArray(
            $store,
            MagentoStoreInterface::class
        );
    }

    protected function getProductCountByStore($store_id)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addStoreFilter($store_id);
        return $collection->getSize();
    }

    protected function getOrderCountByStore($store_id)
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('store_id', $store_id);
        return $collection->getSize();
    }

    private function getTimezoneByStore($storeId)
    {
        return $this->timezone->getConfigTimezone(
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getWeightUnitByStore($storeId)
    {
        return $this->scopeConfig->getValue(
            'general/locale/weight_unit',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getLicenseKey($storeId)
    {
        return $this->scopeConfig->getValue(Config::XML_PATH_LICENSE_KEY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function isExtensionEnabled($storeId)
    {
        return $this->scopeConfig->isSetFlag(Config::XML_PATH_FEATURE_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function getIsHeadless($storeId)
    {
        $themeId = $this->scopeConfig->getValue('design/theme/theme_id', ScopeInterface::SCOPE_STORE, $storeId);

        $isPwaInstalled = $this->isPwaInstalled();

        if (empty($themeId) || $isPwaInstalled) {
            return true;
        }

        return false;
    }


    private function getHeadlessDetails($storeId)
    {
        $themeId = $this->scopeConfig->getValue('design/theme/theme_id', ScopeInterface::SCOPE_STORE, $storeId);
        $isPwaInstalled = $this->isPwaInstalled();

        return [
            'has_headless_attribute' => empty($themeId) || $isPwaInstalled,
            'has_pwa_module' => $isPwaInstalled,
            'has_theme_id' => !empty($themeId)
        ];
    }

    private function readComposerFile($filePath)
    {
        if (file_exists($filePath)) {
            $fileContent = file_get_contents($filePath);
            return json_decode($fileContent, true);
        }

        return null;
    }


    private function isPwaInstalled()
    {
        $composerFile = BP . '/composer.json';
        $composerLockFile = BP . '/composer.lock';

        if (!file_exists($composerFile) && !file_exists($composerLockFile)) {
            return [];
        }

        $composerData = $this->readComposerFile($composerFile);
        $composerLockData = $this->readComposerFile($composerLockFile);

        $pwaKeywords = ['pwa', '@magento/pwa-studio', 'venia', 'magento/pwa', 'caravelx/module-graphql-config'];

        $foundPwaDependencies = array_merge(
            $this->findPwaDependencies($composerData, $pwaKeywords),
            $this->findPwaDependencies($composerLockData, $pwaKeywords)
        );

        return array_unique($foundPwaDependencies);
    }

    private function findPwaDependencies(array $composerData, array $pwaKeywords)
    {
        $foundDependencies = [];

        if ($composerData) {
            $dependencies = array_merge(
                $composerData['require'] ?? [],
                $composerData['require-dev'] ?? []
            );

            foreach ($dependencies as $packageName => $version) {
                foreach ($pwaKeywords as $keyword) {
                    if (stripos($packageName, $keyword) !== false) {
                        $foundDependencies[] = $packageName;
                        break;
                    }
                }
            }
        }

        return $foundDependencies;
    }
}
