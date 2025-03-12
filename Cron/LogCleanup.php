<?php
/**
* Profit Peak
*
* @category  Profit Peak
* @package   ProfitPeak_Tracking
* @author    Profit Peak Team <admin@profitpeak.io>
* @copyright Copyright Profit Peak (https://profitpeak.io/)
*/

namespace ProfitPeak\Tracking\Cron;

use Magento\Framework\Filesystem\Driver\File;

class LogCleanup
{
    protected $file;

    public function __construct(
        File $file
    ) {
        $this->file = $file;
    }

    public function execute()
    {
        $logFile = BP . '/var/log/profitpeak_error.log';

        // Check if the log file exists and clear it if older than 7 days
        if ($this->file->isExists($logFile)) {
            $fileAge = time() - $this->file->stat($logFile)['mtime'];

            if ($fileAge > 7 * 24 * 60 * 60) {
                $handle = $this->file->fileOpen($logFile, 'w');
                if ($handle) {
                    $this->file->fileClose($handle);
                } else {
                    throw new \Exception(__('Failed to open log file for truncation.'));
                }
            }
        }
    }

}
