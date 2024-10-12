<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Import\Concrete;


use App\Application\Import\Concrete\ProductLabelImporter;
use App\Shared\DTO\ProductLabelDTO;
use PHPUnit\Framework\TestCase;

class ProductLabelImporterTest extends TestCase
{
    public function testImportWithSampleData(): void
    {
        $importer = new ProductLabelImporter();
        $filePath = __DIR__ . '/../../../../Fixtures/valid_product_labels.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $dto) {
            $this->assertInstanceOf(ProductLabelDTO::class, $dto);
        }
    }
}
