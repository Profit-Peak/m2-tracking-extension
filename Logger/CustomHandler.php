<?php
namespace ProfitPeak\Tracking\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class CustomHandler extends Base
{
    protected $fileName = '/var/log/profitpeak.log';
    protected $loggerType = Logger::DEBUG;
}
