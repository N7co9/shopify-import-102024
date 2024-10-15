<?php
declare(strict_types=1);

namespace App\Domain\Message;

interface MessengerInterface
{
    public function dispatch(ProductMessage $message): bool;
}