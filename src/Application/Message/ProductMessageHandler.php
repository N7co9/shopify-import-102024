<?php
declare(strict_types=1);

namespace App\Application\Message;


use App\Application\Product\Transport\ProductProcessorInterface;
use App\Domain\Message\ProductMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProductMessageHandler
{
    public function __construct
    (
        private ProductProcessorInterface $productProcessor,

    )
    {
    }

    public function __invoke(ProductMessage $message): void
    {
        $productDTO = $message->getContent();

        $this->productProcessor->processProduct($productDTO);
    }

}
