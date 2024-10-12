<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Import\Concrete;


use App\Application\Import\Concrete\ProductLabelImporter;
use App\Shared\DTO\ProductLabelDTO;
use PHPUnit\Framework\TestCase;

class ProductLabelImporterTest extends TestCase
{
    public function testImportWithValidData(): void
    {
        $importer = new ProductLabelImporter();
        $filePath = __DIR__ . '/../../../../Fixtures/valid_product_labels.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(ProductLabelDTO::class, $result[0]);

        /** @var ProductLabelDTO $dto */
        $dto = $result[0];
        $this->assertSame('Standard label', $dto->getName());
        $this->assertTrue($dto->isActive());
        $this->assertFalse($dto->isDynamic());
        $this->assertFalse($dto->isExclusive());
        $this->assertNull($dto->getFrontEndReference());
        $this->assertSame('Standard Label', $dto->getNameEn());
        $this->assertSame('Standard Label', $dto->getNameDe());
        $this->assertIsArray($dto->getProductAbstractSkus());
        $this->assertSame(1, $dto->getPriority());
    }
}