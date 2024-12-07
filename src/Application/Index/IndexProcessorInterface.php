<?php
declare(strict_types=1);

namespace App\Application\Index;

interface IndexProcessorInterface
{
    public function indexLogs(?string $logType = null): void;
}