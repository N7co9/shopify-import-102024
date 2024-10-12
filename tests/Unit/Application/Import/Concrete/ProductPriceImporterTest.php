<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Import\Concrete;


use App\Application\Import\Concrete\ProductPriceImporter;
use App\Shared\DTO\ProductPriceDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ProductPriceImporterTest extends TestCase
{
    public function testImportWithValidFile(): void
    {
        $importer = new ProductPriceImporter();

        $filePath = __DIR__ . '/../../../../Fixtures/valid_product_prices.csv';
        $fileMock = $this->createMock(File::class);

        $this->assertInstanceOf(File::class, $fileMock);

        $result = $importer->import($filePath);
        $this->assertIsArray($result);
        $this->assertInstanceOf(ProductPriceDTO::class, $result[0]);
    }

    public function testImportWithInvalidFile(): void
    {
        $importer = new ProductPriceImporter();

        $this->expectException(FileException::class);
        $filePath = __DIR__ . '/fixtures/invalid_file.csv';

        $importer->import($filePath);
    }
}