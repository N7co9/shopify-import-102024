<?php
declare(strict_types=1);

namespace App\Tests\Integration\Domain\DTO;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use PHPUnit\Framework\TestCase;

class ProductDTOTest extends TestCase
{
    public function testAbstractAndConcreteProductDTOIntegration(): void
    {
        $abstractDTO = new AbstractProductDTO(
            '001',
            'Abstract Product EN',
            'Abstract Produkt DE',
            'Description EN',
            'Beschreibung DE',
            'digital-cameras',
            'TaxSet',
            'Meta Title EN',
            'Meta Title DE'
        );

        $concreteDTO = new ConcreteProductDTO(
            $abstractDTO->getAbstractSku(),
            '001_12345',
            'Concrete Product EN',
            'Concrete Produkt DE',
            'Description EN',
            'Beschreibung DE',
            10,
            true,
            19.99,
            'EUR',
            'http://example.com/image.jpg',
            true,
            false
        );

        $this->assertSame($abstractDTO->getAbstractSku(), $concreteDTO->getAbstractSku());
    }
}