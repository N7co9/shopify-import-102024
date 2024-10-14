<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;


use App\Domain\DTO\ProductLabelDTO;
use PHPUnit\Framework\TestCase;

class ProductLabelDTOTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $dto = new ProductLabelDTO(
            'Standard Label',
            true,
            false,
            false,
            null,
            'Label EN',
            'Label DE',
            ['001', '002'],
            1
        );

        $this->assertSame('Standard Label', $dto->getName());
        $this->assertTrue($dto->isActive());
        $this->assertFalse($dto->isDynamic());
        $this->assertFalse($dto->isExclusive());
        $this->assertNull($dto->getFrontEndReference());
        $this->assertSame('Label EN', $dto->getNameEn());
        $this->assertSame('Label DE', $dto->getNameDe());
        $this->assertSame(['001', '002'], $dto->getProductAbstractSkus());
        $this->assertSame(1, $dto->getPriority());
    }
}