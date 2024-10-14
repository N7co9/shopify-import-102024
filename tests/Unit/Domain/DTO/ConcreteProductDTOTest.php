<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\ConcreteProductDTO;
use PHPUnit\Framework\TestCase;

class ConcreteProductDTOTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $dto = new ConcreteProductDTO(
            '001',
            '001_12345',
            'Canon Camera',
            'Kamera',
            'Best camera',
            'Beste Kamera',
            10,
            true,
            999.99,
            'EUR',
            'http://example.com/image.jpg',
            true,
            false
        );

        $this->assertSame('001', $dto->getAbstractSku());
        $this->assertSame('001_12345', $dto->getConcreteSku());
        $this->assertSame('Canon Camera', $dto->getNameEn());
        $this->assertSame('Kamera', $dto->getNameDe());
        $this->assertSame('Best camera', $dto->getDescriptionEn());
        $this->assertSame('Beste Kamera', $dto->getDescriptionDe());
        $this->assertSame(10, $dto->getQuantity());
        $this->assertTrue($dto->isNeverOutOfStock());
        $this->assertSame(999.99, $dto->getPriceGross());
        $this->assertSame('EUR', $dto->getCurrency());
        $this->assertSame('http://example.com/image.jpg', $dto->getImageUrl());
        $this->assertTrue($dto->isSearchableEn());
        $this->assertFalse($dto->isSearchableDe());

        $dto->setAbstractSku('002');
        $this->assertSame('002', $dto->getAbstractSku());

        $dto->setConcreteSku('002_54321');
        $this->assertSame('002_54321', $dto->getConcreteSku());

        $dto->setNameEn('Updated Camera');
        $this->assertSame('Updated Camera', $dto->getNameEn());

        $dto->setNameDe('Aktualisierte Kamera');
        $this->assertSame('Aktualisierte Kamera', $dto->getNameDe());

        $dto->setDescriptionEn('Updated best camera');
        $this->assertSame('Updated best camera', $dto->getDescriptionEn());

        $dto->setDescriptionDe('Aktualisierte beste Kamera');
        $this->assertSame('Aktualisierte beste Kamera', $dto->getDescriptionDe());

        $dto->setQuantity(5);
        $this->assertSame(5, $dto->getQuantity());

        $dto->setIsNeverOutOfStock(false);
        $this->assertFalse($dto->isNeverOutOfStock());

        $dto->setPriceGross(1099.99);
        $this->assertSame(1099.99, $dto->getPriceGross());

        $dto->setCurrency('USD');
        $this->assertSame('USD', $dto->getCurrency());

        $dto->setImageUrl('http://example.com/new-image.jpg');
        $this->assertSame('http://example.com/new-image.jpg', $dto->getImageUrl());

        $dto->setIsSearchableEn(false);
        $this->assertFalse($dto->isSearchableEn());

        $dto->setIsSearchableDe(true);
        $this->assertTrue($dto->isSearchableDe());
    }
}
