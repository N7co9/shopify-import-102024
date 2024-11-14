<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Product\Import\Concrete;



use App\Application\Product\Import\Concrete\ProductImageImporter;
use App\Domain\DTO\ProductImageDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ProductImageImporterTest extends TestCase
{
    public function testImportWithSampleData(): void
    {
        $importer = new ProductImageImporter();
        $filePath = __DIR__ . '/../../../../../Fixtures/valid_product_images.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $dto) {
            $this->assertInstanceOf(ProductImageDTO::class, $dto);
        }
    }
    public function testImportWithInvalidFileThrowsException(): void
    {
        $this->expectException(FileException::class);

        $importer = new ProductImageImporter();
        $filePath = __DIR__ . '/../../Fixtures/invalid.csv'; 
        $importer->import($filePath);
    }

}