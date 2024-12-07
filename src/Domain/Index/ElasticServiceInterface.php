<?php
declare(strict_types=1);

namespace App\Domain\Index;

interface ElasticServiceInterface
{
    public function indexLogs(array $logs): void;
}