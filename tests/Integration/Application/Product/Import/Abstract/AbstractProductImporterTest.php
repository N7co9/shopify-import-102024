<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Product\Import\Abstract;


use App\Application\Product\Import\AbstractProductImporter;
use App\Domain\DTO\AbstractProductDTO;
use PHPUnit\Framework\TestCase;

class AbstractProductImporterTest extends TestCase
{
    public function testImportWithSampleData(): void
    {
        $importer = new AbstractProductImporter();
        $filePath = __DIR__ . '/../../../../../Fixtures/valid_products.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(AbstractProductDTO::class, $result[0]);

        /** @var AbstractProductDTO $dto */
        $dto = $result[0];
        $this->assertSame('SKU001', $dto->getAbstractSku());
        $this->assertSame('Test Product EN', $dto->getNameEn());
        $this->assertSame('Testprodukt DE', $dto->getNameDe());
        $this->assertSame('This is a test product', $dto->getDescriptionEn());
        $this->assertSame('Dies ist ein Testprodukt', $dto->getDescriptionDe());
        $this->assertSame('category-1', $dto->getCategoryKey());
        $this->assertSame('Standard', $dto->getTaxSetName());
        $this->assertSame('Test Meta EN', $dto->getMetaTitleEn());
        $this->assertSame('Test Meta DE', $dto->getMetaTitleDe());
    }
}