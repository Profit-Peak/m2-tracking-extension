<?php
/**
* Profit Peak
*
* @category  Profit Peak
* @package   ProfitPeak_Tracking
* @author    Profit Peak Team <admin@profitpeak.io>
* @copyright Copyright Profit Peak (https://profitpeak.io/)
*/

namespace ProfitPeak\Tracking\Api;

interface ErrorLogInterface
{
    /**
     * Retrieve the contents of the error log.
     *
     * @return void
     */
    public function getErrorLog();
    
    /**
     * Remove error log.
     *
     * @return void
     */
    public function removeErrorLog();
}
