<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Import\Concrete;


use App\Application\Import\Concrete\ConcreteProductImporter;
use App\Shared\DTO\ConcreteProductDTO;
use PHPUnit\Framework\TestCase;

class ConcreteProductImporterTest extends TestCase
{
    public function testImportWithSampleData(): void
    {
        $importer = new ConcreteProductImporter();
        $filePath = __DIR__ . '/../../../../Fixtures/valid_concrete_products.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(ConcreteProductDTO::class, $result[0]);

        /** @var ConcreteProductDTO $dto */
        $dto = $result[0];
        $this->assertSame('SKU001', $dto->getAbstractSku());
        $this->assertSame('SKU001-V1', $dto->getConcreteSku());
        $this->assertSame('Test Product Variant EN', $dto->getNameEn());
        $this->assertSame('Testprodukt Variante DE', $dto->getNameDe());
        $this->assertSame('This is a test variant', $dto->getDescriptionEn());
        $this->assertSame('Dies ist eine Testvariante', $dto->getDescriptionDe());
        $this->assertNull($dto->getQuantity());
        $this->assertNull($dto->isNeverOutOfStock());
        $this->assertNull($dto->getPriceGross());
        $this->assertNull($dto->getCurrency());
        $this->assertTrue($dto->isSearchableEn());
        $this->assertFalse($dto->isSearchableDe());
    }
}