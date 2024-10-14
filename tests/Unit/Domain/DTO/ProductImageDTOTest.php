<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\ProductImageDTO;
use PHPUnit\Framework\TestCase;

class ProductImageDTOTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $dto = new ProductImageDTO('Main Image', 'http://large.com/img.jpg', 'http://small.com/img.jpg', 'en', '001', '001_12345', 1, 'IMG_001');

        $this->assertSame('Main Image', $dto->getImageSetName());
        $this->assertSame('http://large.com/img.jpg', $dto->getExternalUrlLarge());
        $this->assertSame('http://small.com/img.jpg', $dto->getExternalUrlSmall());
        $this->assertSame('en', $dto->getLocale());
        $this->assertSame('001', $dto->getAbstractSku());
        $this->assertSame('001_12345', $dto->getConcreteSku());
        $this->assertSame(1, $dto->getSortOrder());
        $this->assertSame('IMG_001', $dto->getProductImageKey());
    }
}