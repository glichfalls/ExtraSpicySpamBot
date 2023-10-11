<?php

namespace App\Utils;

use Psr\Log\LoggerInterface;

class Memory
{

    public static function logMemoryReport(LoggerInterface $logger, $title = 'Server Memory Report'): void
    {
        $memoryUsage = memory_get_usage();
        $memoryPeak = memory_get_peak_usage();
        $memoryLimit = ini_get('memory_limit');

        $logger->error($title, [
            'memory_usage' => $memoryUsage,
            'memory_peak' => $memoryPeak,
            'memory_limit' => $memoryLimit
        ]);
    }

}