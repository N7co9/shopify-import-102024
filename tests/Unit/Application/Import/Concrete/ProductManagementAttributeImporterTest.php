<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Import\Concrete;

use App\Application\Import\Concrete\ProductManagementAttributeImporter;
use App\Shared\DTO\ProductManagementAttributeDTO;
use PHPUnit\Framework\TestCase;

class ProductManagementAttributeImporterTest extends TestCase
{
    public function testImportWithValidData(): void
    {
        $importer = new ProductManagementAttributeImporter();
        $filePath = __DIR__ . '/../../../../Fixtures/valid_product_management_attributes.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(ProductManagementAttributeDTO::class, $result[0]);

        /** @var ProductManagementAttributeDTO $dto */
        $dto = $result[0];
        $this->assertSame('storage_capacity', $dto->getKey());
        $this->assertSame('text', $dto->getInputType());
        $this->assertFalse($dto->isMultiple());
        $this->assertSame('16 GB, 32 GB, 64 GB, 128 GB', $dto->getValues());
        $this->assertSame('Storage Capacity', $dto->getKeyTranslationEn());
        $this->assertSame('Speichergröße', $dto->getKeyTranslationDe());
    }
}