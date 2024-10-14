<?php
declare(strict_types=1);

namespace App\Tests\Integration\Domain\Message;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use App\Domain\Message\ProductMessage;
use PHPUnit\Framework\TestCase;

class ProductMessageTest extends TestCase
{
    public function testMessageWithAbstractAndConcreteProductDTO(): void
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

        $message = new ProductMessage($abstractDTO);
        $this->assertInstanceOf(AbstractProductDTO::class, $message->getContent());

        $concreteDTO = new ConcreteProductDTO(
            '001',
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

        $message = new ProductMessage($concreteDTO);
        $this->assertInstanceOf(ConcreteProductDTO::class, $message->getContent());
    }
}