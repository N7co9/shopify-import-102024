<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Import\Concrete;

use App\Application\Import\Concrete\ProductManagementAttributeImporter;
use App\Shared\DTO\ProductManagementAttributeDTO;
use PHPUnit\Framework\TestCase;

class ProductManagementAttributeImporterTest extends TestCase
{
    public function testImportWithSampleData(): void
    {
        $importer = new ProductManagementAttributeImporter();
        $filePath = __DIR__ . '/../../../../Fixtures/valid_product_management_attributes.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $dto) {
            $this->assertInstanceOf(ProductManagementAttributeDTO::class, $dto);
        }
    }
}