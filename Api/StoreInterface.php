<?php
/**
* Profit Peak
*
* @category  Profit Peak
* @package   ProfitPeak_Tracking
* @author
* @copyright
*/

namespace ProfitPeak\Tracking\Api;

interface StoreInterface
{
    /**
     * Get store details with product and order count by store_id.
     *
     * @param int $store_id
     * @return void
     */
    public function getById($store_id);
}
