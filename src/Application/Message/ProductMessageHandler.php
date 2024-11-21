<?php
declare(strict_types=1);

namespace App\Application\Message;


use App\Application\Message\Tools\LookupHelper;
use App\Application\Product\Transport\ProductProcessorInterface;
use App\Application\Product\Transport\VariantProcessorInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\ProductMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ProductMessageHandler
{
    public function __construct
    (
        private ProductProcessorInterface $productProcessor,
        private VariantProcessorInterface $variantProcessor,
        private LookupHelper              $lookupHelper,
        private MessageBusInterface       $bus
    )
    {
    }

    public function __invoke(ProductMessage $message): void
    {
        $productDTO = $message->getContent();

        if ($productDTO instanceof AbstractProductDTO) {
            $this->productProcessor->processProduct($productDTO);
        } elseif ($productDTO instanceof ConcreteProductDTO) {
            $inheritanceInformation = $this->checkParentProductExistence($productDTO);
            if (!empty($inheritanceInformation)) {
                $this->variantProcessor->processVariant($productDTO, $inheritanceInformation);
            } else {
                $this->bus->dispatch(new ProductMessage($productDTO));
            }
        } else {
            throw new \InvalidArgumentException('Unknown product DTO type');
        }
    }

    public function checkParentProductExistence(ConcreteProductDTO $productDTO): array
    {
        return $this->lookupHelper->fetchExistingProductInformation($productDTO->getNameEn());
    }
}
