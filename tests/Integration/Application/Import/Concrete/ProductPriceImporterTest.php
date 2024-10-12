<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Import\Concrete;


use App\Application\Import\Concrete\ProductPriceImporter;
use App\Shared\DTO\ProductPriceDTO;
use PHPUnit\Framework\TestCase;

class ProductPriceImporterTest extends TestCase
{
    public function testImportWithSampleData(): void
    {
        $importer = new ProductPriceImporter();
        $filePath = __DIR__ . '/../../../../Fixtures/valid_product_prices.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(ProductPriceDTO::class, $result[0]);

        /** @var ProductPriceDTO $dto */
        $dto = $result[0];
        $this->assertSame('SKU001', $dto->getSku());
        $this->assertSame(19.99, $dto->getPriceGross());
        $this->assertSame('EUR', $dto->getCurrency());

        /** @var ProductPriceDTO $dto */
        $dto = $result[1];
        $this->assertSame('SKU002', $dto->getSku());
        $this->assertSame(24.99, $dto->getPriceGross());
        $this->assertSame('USD', $dto->getCurrency());
    }
}