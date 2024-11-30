<?php
declare(strict_types=1);

namespace App\Application\Product\Import;

use App\Application\Logger\LoggerInterface;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class ImportProcessor implements ImportInterface
{
    public function __construct(
        private readonly ShopifyVariantImporter $variantImporter,
        private readonly ShopifyProductImporter $productImporter,
        private readonly LoggerInterface        $logger
    )
    {
    }

    public function processImport(string $directoryPath): array
    {
        $files = $this->getCsvFiles($directoryPath);

        $shopifyVariants = $this->variantImporter->import(
            $files['product_stock.csv'],
            $files['product_image.csv'],
            $files['product_price.csv'],
            $files['product_concrete.csv']
        );

        $shopifyProducts = $this->productImporter->import(
            $files['product_abstract.csv'],
            $files['product_price.csv'],
            $files['product_image.csv']
        );

        return $this->mapProductsToVariants($shopifyProducts, $shopifyVariants);
    }

    private function getCsvFiles(string $directoryPath): array
    {
        $requiredFiles = [
            'product_abstract.csv',
            'product_concrete.csv',
            'product_price.csv',
            'product_stock.csv',
            'product_image.csv',
        ];
        $finder = new Finder();
        $finder->files()->in($directoryPath)->name('*.csv');

        $files = [];
        foreach ($finder as $file) {
            $files[$file->getFilename()] = $file->getRealPath();
        }

        foreach ($requiredFiles as $requiredFile) {
            if (!isset($files[$requiredFile])) {
                $this->logger->logError(sprintf('Required CSV file "%s" is missing.', $requiredFile), 'import');
                throw new RuntimeException(sprintf('Required CSV file "%s" is missing.', $requiredFile));
            }
        }

        return $files;
    }

    private function mapProductsToVariants(array $products, array $variants): array
    {
        foreach ($products as $product) {
            $product->variants = array_filter($variants, static fn($variant) => $variant->abstractSku === $product->abstractSku);
        }

        return $products;
    }
}
