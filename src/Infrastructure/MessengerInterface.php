<?php
declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Message\ProductMessage;

interface MessengerInterface
{
    public function dispatch(ProductMessage $message): bool;
}