<?php
declare(strict_types=1);

namespace App\Application\Message;


use App\Application\Message\Tools\LookupHelper;
use App\Application\Product\Transport\ProductProcessorInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\ProductMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProductMessageHandler
{
    public function __construct
    (
        private ProductProcessorInterface $productProcessor,
        private LookupHelper              $lookupHelper
    )
    {
    }

    public function __invoke(ProductMessage $message): void
    {
        $productDTO = $message->getContent();

        if ($productDTO instanceof AbstractProductDTO) {
            $this->productProcessor->processProduct($productDTO);
        } elseif ($productDTO instanceof ConcreteProductDTO) {
            if (!empty($this->checkParentProductExistence($productDTO))) {
                echo '';
                // TODO
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
