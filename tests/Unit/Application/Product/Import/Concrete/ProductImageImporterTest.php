<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Import\Concrete;


use App\Application\Product\Import\Concrete\ProductImageImporter;
use App\Domain\DTO\ProductImageDTO;
use PHPUnit\Framework\TestCase;

class ProductImageImporterTest extends TestCase
{
    public function testImportWithValidData(): void
    {
        $importer = new ProductImageImporter();
        $filePath = __DIR__ . '/../../../../../Fixtures/valid_product_images.csv';

        $result = $importer->import($filePath);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(ProductImageDTO::class, $result[0]);

        /** @var ProductImageDTO $dto */
        $dto = $result[0];
        $this->assertSame('default', $dto->getImageSetName());
        $this->assertSame('https://example.com/large1.jpg', $dto->getExternalUrlLarge());
        $this->assertSame('https://example.com/small1.jpg', $dto->getExternalUrlSmall());
        $this->assertSame('de_DE', $dto->getLocale());
        $this->assertNull($dto->getAbstractSku());
        $this->assertSame('concrete_sku_1', $dto->getConcreteSku());
        $this->assertSame(1, $dto->getSortOrder());
        $this->assertSame('product_image_1', $dto->getProductImageKey());
    }
}