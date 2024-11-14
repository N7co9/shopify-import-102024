<?php
declare(strict_types=1);

namespace App\Application\Message;


use App\Application\Product\Transport\ProductProcessorInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\ProductMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProductMessageHandler
{
    public function __construct(private ProductProcessorInterface $productProcessor)
    {
    }

    public function __invoke(ProductMessage $message): void
    {
        $productDTO = $message->getContent();

        if ($productDTO instanceof AbstractProductDTO) {
            $this->productProcessor->processProduct($productDTO);
        } elseif ($productDTO instanceof ConcreteProductDTO) {
            echo(''); // TO DODODOO
        } else {
            throw new \InvalidArgumentException('Unknown product DTO type');
        }
    }
}
