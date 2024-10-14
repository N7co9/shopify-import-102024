<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\ProductStockDTO;
use PHPUnit\Framework\TestCase;

class ProductStockDTOTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $dto = new ProductStockDTO('001', 100, true);

        $this->assertSame('001', $dto->getSku());
        $this->assertSame(100, $dto->getQuantity());
        $this->assertTrue($dto->isNeverOutOfStock());

        $dto->setSku('002');
        $this->assertSame('002', $dto->getSku());

        $dto->setQuantity(150);
        $this->assertSame(150, $dto->getQuantity());

        $dto->setIsNeverOutOfStock(false);
        $this->assertFalse($dto->isNeverOutOfStock());
    }
}