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

class Config
{
    const XML_PATH_LICENSE_KEY = 'profitpeak_tracking/settings/license_key';
    const XML_PATH_ANALYTICS_ID = 'profitpeak_tracking/settings/analytics_id';
    const XML_PATH_FEATURE_ENABLED = 'profitpeak_tracking/settings/enabled';
    const PROFIT_PEAK_URL = "https://app.profitpeak.io/api/auth/callback/magento2?";
    const PROFIT_PEAK_PIXEL_URL = "https://cdn.profitpeak.io/pixel/m2/pixel.min.js";
    const MAX_LIMIT = 500;
}
