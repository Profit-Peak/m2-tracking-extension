<?php
namespace ProfitPeak\Tracking\Model\Api;

use ProfitPeak\Tracking\Api\ErrorLogInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Exception\LocalizedException;
use ProfitPeak\Tracking\Helper\Data;

class ErrorLog implements ErrorLogInterface
{
    /**
     * @var File
     */
    protected $file;
    
    /**
     * @var Data
     */
    protected $helper;

    public function __construct(
        File $file,
        Data $helper
    )
    {
        $this->file = $file;
        $this->helper = $helper;
    }

    /**
     * Retrieve the latest 5 entries from the error log in a structured format.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getErrorLog()
    {
        $logFile = BP . '/var/log/profitpeak_error.log';

        if (!$this->file->isExists($logFile)) {
            throw new LocalizedException(__('Log file not found.'));
        }

        // Use PHP's native file() function to read lines
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Get the last 5 lines (or fewer if the file has fewer lines)
        $latestLines = array_slice($lines, -100);

        $logEntries = [];
        foreach ($latestLines as $line) {
            // Parse the log line with regex to extract the date, log level, and message
            preg_match('/^\[(.*?)\] (.*?)\.(ERROR|WARNING|INFO): (.*?) \[\]/', $line, $matches);

            if (!empty($matches)) {
                $logEntries[] = [
                    'timestamp' => $matches[1] ?? '',
                    'logger' => $matches[2] ?? '',
                    'level' => $matches[3] ?? '',
                    'message' => $matches[4] ?? ''
                ];
            }
        }

        return $logEntries;
    }

    /**
     * Remove the error log file.
     *
     * @return void
     * @throws LocalizedException
     */
    public function removeErrorLog()
    {
        $logFile = BP . '/var/log/profitpeak_error.log';

        if (!$this->file->isExists($logFile)) {
            throw new LocalizedException(__('Log file not found.'));
        }

        // Open the file in "write" mode, which truncates (clears) the file
        $handle = $this->file->fileOpen($logFile, 'w');
        if (!$handle) {
            throw new LocalizedException(__('Failed to open log file for truncation.'));
        }
        
        $this->file->fileClose($handle);

        // Return a JSON response indicating success
        $data['success'] = true;
        return $this->helper->sendJsonResponse($data);
    }
}
