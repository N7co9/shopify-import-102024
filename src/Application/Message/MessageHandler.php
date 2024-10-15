<?php
declare(strict_types=1);

namespace App\Application\Message;

use App\Domain\Message\ProductMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MessageHandler
{
    public function __construct()
    {
    }

    public function __invoke(ProductMessage $message): void
    {

    }
}

