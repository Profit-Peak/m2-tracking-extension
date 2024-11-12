<?php
namespace ProfitPeak\Tracking\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Handler extends StreamHandler
{
    public function __construct()
    {
        $logFile = BP . '/var/log/profitpeak_error.log'; // Log file path
        $logLevel = Logger::INFO;
        parent::__construct($logFile, $logLevel);
    }
}
