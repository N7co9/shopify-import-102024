<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\ProductPriceDTO;
use PHPUnit\Framework\TestCase;

class ProductPriceDTOTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $dto = new ProductPriceDTO('001', 19.99, 'EUR');

        $this->assertSame('001', $dto->getSku());
        $this->assertSame(19.99, $dto->getPriceGross());
        $this->assertSame('EUR', $dto->getCurrency());

        $dto->setPriceGross(25.99);
        $this->assertSame(25.99, $dto->getPriceGross());

        $dto->setCurrency('USD');
        $this->assertSame('USD', $dto->getCurrency());

        $dto->setSku('002');
        $this->assertSame('002', $dto->getSku());
    }
}