<?php
declare(strict_types=1);

namespace App\Application\Mapper;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ShopifyProductDTO;

class ProductToShopifyMapper
{
    public function __construct()
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

        $productOptions = $this->extractColorOption($abstractProductDTO->getManagementAttributes());

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
        $managementAttributes = $abstractProductDTO->getManagementAttributes();
        unset($managementAttributes['color']);

        return array_merge([
            'metaTitleEn' => $abstractProductDTO->getMetaTitleEn() ?? 'N/A',
            'metaTitleDe' => $abstractProductDTO->getMetaTitleDe() ?? 'N/A',
            'taxSetName' => $abstractProductDTO->getTaxSetName() ?? 'N/A',
            'translation' => [
                'nameEn' => $abstractProductDTO->getNameEn() ?? 'N/A',
                'descriptionEn' => $abstractProductDTO->getDescriptionEn() ?? 'N/A'
            ]
        ], $managementAttributes);
    }

    private function extractColorOption(array $managementAttributes): array
    {
        if (isset($managementAttributes['color']) && is_array($managementAttributes['color'])) {
            return [['name' => 'color', 'values' => $managementAttributes['color']]];
        }

        if (isset($managementAttributes['color'])) {
            return [['name' => 'color', 'values' => [(string)$managementAttributes['color']]]];
        }

        return [];
    }
}
