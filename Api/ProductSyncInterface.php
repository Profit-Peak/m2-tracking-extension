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

interface ProductSyncInterface
{
    /**
     * POST for getting products API
     * @param int store_id
     * @return void
    */
    public function list($store_id);

    /**
     * Get order data
     *
     * @param int store_id
     * @param int product_id
     * @return void
     */
    public function getById($store_id, $product_id);

    /**
     * POST for setting products as sent API
     * @param int store_id
     * @return void
    */
    public function updateMany($store_id);
}
