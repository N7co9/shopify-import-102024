<?php
declare(strict_types=1);

namespace App\Application\Product\Import;

use App\Application\Product\Import\Abstract\AbstractProductImporter;
use App\Application\Product\Import\Concrete\ConcreteProductImporter;
use App\Application\Product\Import\Concrete\ProductImageImporter;
use App\Application\Product\Import\Concrete\ProductPriceImporter;
use App\Application\Product\Import\Concrete\ProductStockImporter;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\DTO\ProductImageDTO;
use App\Domain\DTO\ProductPriceDTO;
use App\Domain\DTO\ProductStockDTO;
use Symfony\Component\Finder\Finder;

class ImportProcessor implements ImportInterface
{
    private AbstractProductImporter $abstractProductImporter;
    private ConcreteProductImporter $concreteProductImporter;
    private ProductPriceImporter $priceImporter;
    private ProductStockImporter $stockImporter;
    private ProductImageImporter $imageImporter;

    public function __construct(
        AbstractProductImporter $abstractProductImporter,
        ConcreteProductImporter $concreteProductImporter,
        ProductPriceImporter    $priceImporter,
        ProductStockImporter    $stockImporter,
        ProductImageImporter    $imageImporter,
    )
    {
        $this->abstractProductImporter = $abstractProductImporter;
        $this->concreteProductImporter = $concreteProductImporter;
        $this->priceImporter = $priceImporter;
        $this->stockImporter = $stockImporter;
        $this->imageImporter = $imageImporter;
    }

    public function processImport(string $directoryPath): array
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

        if (!$abstractFilePath || !$concreteFilePath || !$priceFilePath || !$stockFilePath || !$imageFilePath) {
            throw new \RuntimeException('One or more required CSV files are missing.');
        }

        $concreteProducts = $this->concreteProductImporter->import($concreteFilePath);
        $prices = $this->priceImporter->import($priceFilePath);
        $stocks = $this->stockImporter->import($stockFilePath);
        $images = $this->imageImporter->import($imageFilePath);

        $abstractProductDTOs = $this->abstractProductImporter->import($abstractFilePath);
        $concreteProductDTOs = $this->createConcreteProductDTOs($concreteProducts, $prices, $stocks, $images);

        return [
            'abstract_products' => $abstractProductDTOs,
            'concrete_products' => $concreteProductDTOs
        ];
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
        $abstractSku = explode('_', $sku)[0];

        foreach ($prices as $price) {
            if ($price->getSku() === $abstractSku) {
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
