<?php
declare(strict_types=1);

namespace App\Application\Mapper;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ShopifyProductDTO;

class ProductToShopifyMapper
{
    public function __construct
    ()
    {
    }

    public function mapToShopifyProductDTO(AbstractProductDTO $abstractProductDTO): ?ShopifyProductDTO
    {
        return $this->createShopifyProductDTO($abstractProductDTO);
    }

    private function createShopifyProductDTO(AbstractProductDTO $abstractProductDTO): ShopifyProductDTO
    {
        $title = $abstractProductDTO->getNameDe();
        $descriptionHtml = $abstractProductDTO->getDescriptionDe();
        $productType = $abstractProductDTO->getCategoryKey();
        $productOptions = $abstractProductDTO->getManagementAttributes();

        $metafields = $this->createProductMetafields($abstractProductDTO);

        return new ShopifyProductDTO(
            $title,
            $descriptionHtml,
            $productType,
            $metafields,
            $productOptions
        );
    }

    private function createProductMetafields(AbstractProductDTO $abstractProductDTO): array
    {
        return [
            'metaTitleEn' => $abstractProductDTO->getMetaTitleEn() ?? 'N/A',
            'metaTitleDe' => $abstractProductDTO->getMetaTitleDe() ?? 'N/A',
            'taxSetName' => $abstractProductDTO->getTaxSetName() ?? 'N/A',
            'translation' => [
                'nameEn' => $abstractProductDTO->getNameEn() ?? 'N/A',
                'descriptionEn' => $abstractProductDTO->getDescriptionEn() ?? 'N/A'
            ]
        ];
    }

}
