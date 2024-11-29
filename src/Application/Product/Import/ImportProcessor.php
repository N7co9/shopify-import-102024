<?php
declare(strict_types=1);

namespace App\Application\Product\Import;

use Symfony\Component\Finder\Finder;

class ImportProcessor implements ImportInterface
{
    public function __construct(
        private ShopifyVariantImporter $variantImporter,
        private ShopifyProductImporter $productImporter,
    ) {
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

        $shopifyVariants = $this->variantImporter->import($stockFilePath, $imageFilePath, $priceFilePath, $concreteFilePath);
        $shopifyProducts = $this->productImporter->import($abstractFilePath, $priceFilePath, $imageFilePath);

        $shopifyProductList = [];
        foreach ($shopifyProducts as $shopifyProduct) {
            $variants = [];
            foreach ($shopifyVariants as $shopifyVariant) {
                if ($shopifyVariant->abstractSku === $shopifyProduct->abstractSku) {
                    $variants[] = $shopifyVariant;
                }
            }
            $shopifyProduct->variants = $variants;
            $shopifyProductList[] = $shopifyProduct;
        }

        return $shopifyProductList;
    }
}