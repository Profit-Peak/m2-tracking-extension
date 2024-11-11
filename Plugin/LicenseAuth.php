<?php

namespace ProfitPeak\Tracking\Plugin;

use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class LicenseAuth
{
    protected $request;
    protected $resourceConnection;
    protected $scopeConfig;

    public function __construct(
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeList($subject, $store_id)
    {
        $this->authenticate($store_id);
    }

    public function beforeGetById($subject, $store_id, $id=null)
    {
        $this->authenticate($store_id);
    }

    private function authenticate($store_id)
    {
        $licenseHeader = $this->request->getHeader('x-license-key');
        
        if (!$licenseHeader) {
            throw new AuthorizationException(__('License key header not found.'));
        }

        $storedLicenseKey = $this->scopeConfig->getValue(
            'profitpeak_tracking/settings/license_key',
            ScopeInterface::SCOPE_STORE,
            $store_id
        );

        if ($licenseHeader !== $storedLicenseKey) {
            throw new AuthorizationException(__('Invalid or missing license key.'));
        }
    }
}
