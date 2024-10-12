<?php
declare(strict_types=1);

namespace App\Application\Import;

use App\Application\Import\Abstract\AbstractProductImporter;
use App\Application\Import\Concrete\ConcreteProductImporter;
use App\Application\Import\Concrete\ProductImageImporter;
use App\Application\Import\Concrete\ProductLabelImporter;
use App\Application\Import\Concrete\ProductManagementAttributeImporter;
use App\Application\Import\Concrete\ProductPriceImporter;
use App\Application\Import\Concrete\ProductStockImporter;
use App\Shared\DTO\AbstractProductDTO;
use App\Shared\DTO\ConcreteProductDTO;
use App\Shared\DTO\ProductImageDTO;
use App\Shared\DTO\ProductLabelDTO;
use App\Shared\DTO\ProductManagementAttributeDTO;
use App\Shared\DTO\ProductPriceDTO;
use App\Shared\DTO\ProductStockDTO;
use Symfony\Component\Finder\Finder;

class ImportProcessor
{
    private AbstractProductImporter $abstractProductImporter;
    private ConcreteProductImporter $concreteProductImporter;
    private ProductPriceImporter $priceImporter;
    private ProductStockImporter $stockImporter;
    private ProductImageImporter $imageImporter;
    private ProductLabelImporter $labelImporter;
    private ProductManagementAttributeImporter $managementAttributeImporter;

    public function __construct(
        AbstractProductImporter            $abstractProductImporter,
        ConcreteProductImporter            $concreteProductImporter,
        ProductPriceImporter               $priceImporter,
        ProductStockImporter               $stockImporter,
        ProductImageImporter               $imageImporter,
        ProductLabelImporter               $labelImporter,
        ProductManagementAttributeImporter $managementAttributeImporter
    )
    {
        $this->abstractProductImporter = $abstractProductImporter;
        $this->concreteProductImporter = $concreteProductImporter;
        $this->priceImporter = $priceImporter;
        $this->stockImporter = $stockImporter;
        $this->imageImporter = $imageImporter;
        $this->labelImporter = $labelImporter;
        $this->managementAttributeImporter = $managementAttributeImporter;
    }

    public function processDirectory(string $directoryPath): array
    {
        $finder = new Finder();
        $finder->files()->in($directoryPath)->name('*.csv');

        $files = [];
        foreach ($finder as $file) {
            $files[$file->getFilename()] = $file->getRealPath();
        }

        $abstractFilePath = $files['product_abstract.csv'] ?? null;
        $concreteFilePath = $files['product_concrete.csv'] ?? null;
        $priceFilePath = $files['product_price.csv'] ?? null;
        $stockFilePath = $files['product_stock.csv'] ?? null;
        $imageFilePath = $files['product_image.csv'] ?? null;
        $labelFilePath = $files['product_label.csv'] ?? null;
        $attributeFilePath = $files['product_management_attribute.csv'] ?? null;

        if (!$abstractFilePath || !$concreteFilePath || !$priceFilePath || !$stockFilePath || !$imageFilePath || !$labelFilePath || !$attributeFilePath) {
            throw new \RuntimeException('One or more required CSV files are missing.');
        }

        $abstractProducts = $this->abstractProductImporter->import($abstractFilePath);
        $concreteProducts = $this->concreteProductImporter->import($concreteFilePath);
        $prices = $this->priceImporter->import($priceFilePath);
        $stocks = $this->stockImporter->import($stockFilePath);
        $images = $this->imageImporter->import($imageFilePath);
        $labels = $this->labelImporter->import($labelFilePath);
        $attributes = $this->managementAttributeImporter->import($attributeFilePath);

        $abstractProductDTOs = $this->createAbstractProductDTOs($abstractProducts, $labels, $attributes);
        $concreteProductDTOs = $this->createConcreteProductDTOs($concreteProducts, $prices, $stocks, $images);

        return [
            'abstract_products' => $abstractProductDTOs,
            'concrete_products' => $concreteProductDTOs
        ];
    }

    private function createAbstractProductDTOs(array $abstractProducts, array $labels, array $attributes): array
    {
        /** @var AbstractProductDTO[] $abstractProductDTOs */
        $abstractProductDTOs = [];

        foreach ($abstractProducts as $abstractProduct) {
            $sku = $abstractProduct->getAbstractSku();

            $productLabels = array_filter($labels, fn(ProductLabelDTO $label) => in_array($sku, $label->getProductAbstractSkus() ?? []));
            $productAttributes = array_filter($attributes, fn(ProductManagementAttributeDTO $attribute) => $attribute->getKey() === $abstractProduct->getCategoryKey());

            $abstractProductDTO = new AbstractProductDTO(
                $abstractProduct->getAbstractSku(),
                $abstractProduct->getNameEn(),
                $abstractProduct->getNameDe(),
                $abstractProduct->getDescriptionEn(),
                $abstractProduct->getDescriptionDe(),
                $abstractProduct->getCategoryKey(),
                $abstractProduct->getTaxSetName(),
                $abstractProduct->getMetaTitleEn(),
                $abstractProduct->getMetaTitleDe()
            );

            $abstractProductDTO->setLabels($productLabels);
            $abstractProductDTO->setManagementAttributes($productAttributes);

            $abstractProductDTOs[] = $abstractProductDTO;
        }

        return $abstractProductDTOs;
    }

    private function createConcreteProductDTOs(array $concreteProducts, array $prices, array $stocks, array $images): array
    {
        /** @var ConcreteProductDTO[] $concreteProductDTOs */
        $concreteProductDTOs = [];

        foreach ($concreteProducts as $concreteProduct) {
            $sku = $concreteProduct->getConcreteSku();

            $productPrice = $this->findProductPrice($sku, $prices);
            $productStock = $this->findProductStock($sku, $stocks);
            $productImage = $this->findProductImage($sku, $images);

            $concreteProductDTO = new ConcreteProductDTO(
                $concreteProduct->getAbstractSku(),
                $concreteProduct->getConcreteSku(),
                $concreteProduct->getNameEn(),
                $concreteProduct->getNameDe(),
                $concreteProduct->getDescriptionEn(),
                $concreteProduct->getDescriptionDe(),
                $productStock?->getQuantity(),
                $productStock?->isNeverOutOfStock(),
                $productPrice?->getPriceGross(),
                $productPrice?->getCurrency(),
                $productImage?->getExternalUrlLarge(),
                (bool)$concreteProduct->isSearchableEn(),
                (bool)$concreteProduct->isSearchableDe()
            );

            $concreteProductDTOs[] = $concreteProductDTO;
        }

        return $concreteProductDTOs;
    }

    private function findProductPrice(string $sku, array $prices): ?ProductPriceDTO
    {
        foreach ($prices as $price) {
            if ($price->getSku() === $sku) {
                return $price;
            }
        }
        return null;
    }

    private function findProductStock(string $sku, array $stocks): ?ProductStockDTO
    {
        foreach ($stocks as $stock) {
            if ($stock->getSku() === $sku) {
                return $stock;
            }
        }
        return null;
    }

    private function findProductImage(string $sku, array $images): ?ProductImageDTO
    {
        foreach ($images as $image) {
            if ($image->getConcreteSku() === $sku) {
                return $image;
            }
        }
        return null;
    }
}
