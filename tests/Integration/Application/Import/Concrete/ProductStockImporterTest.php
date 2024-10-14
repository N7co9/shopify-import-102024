<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Import\Concrete;


use App\Application\Import\Concrete\ProductStockImporter;
use App\Domain\DTO\ProductStockDTO;
use PHPUnit\Framework\TestCase;

class ProductStockImporterTest extends TestCase
{
    public function testImportWithSampleData(): void
    {
        $importer = new ProductStockImporter();
        $filePath = __DIR__ . '/../../../../Fixtures/valid_product_stock.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(ProductStockDTO::class, $result[0]);

        /** @var ProductStockDTO $dto */
        $dto = $result[0];
        $this->assertSame('SKU001', $dto->getSku());
        $this->assertSame(100, $dto->getQuantity());
        $this->assertTrue($dto->isNeverOutOfStock());

        /** @var ProductStockDTO $dto */
        $dto = $result[1];
        $this->assertSame('SKU002', $dto->getSku());
        $this->assertSame(50, $dto->getQuantity());
        $this->assertFalse($dto->isNeverOutOfStock());
    }
}