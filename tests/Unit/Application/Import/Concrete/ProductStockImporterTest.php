<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Import\Concrete;

use App\Application\Import\Concrete\ProductStockImporter;
use App\Shared\DTO\ProductStockDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ProductStockImporterTest extends TestCase
{
    public function testImportWithValidFile(): void
    {
        $importer = new ProductStockImporter();

        $filePath = __DIR__ . '/../../../../Fixtures/valid_product_stock.csv';

        $fileMock = $this->createMock(File::class);

        $this->assertInstanceOf(File::class, $fileMock);

        $result = $importer->import($filePath);
        $this->assertIsArray($result);
        $this->assertInstanceOf(ProductStockDTO::class, $result[0]);
    }

    public function testImportWithInvalidFile(): void
    {
        $importer = new ProductStockImporter();

        $this->expectException(FileException::class);
        $filePath = __DIR__ . '/fixtures/invalid_file.csv';

        $importer->import($filePath);
    }
}
