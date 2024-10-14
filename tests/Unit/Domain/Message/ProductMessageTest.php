<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\Message;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\Message\ProductMessage;
use PHPUnit\Framework\TestCase;

class ProductMessageTest extends TestCase
{
    public function testProductMessage(): void
    {
        $dto = new AbstractProductDTO(
            '001',
            'Test Product',
            'Test Produkt',
            'Description EN',
            'Beschreibung DE',
            'digital-cameras',
            'TaxSet',
            'Meta Title EN',
            'Meta Title DE'
        );

        $message = new ProductMessage($dto);
        $this->assertSame($dto, $message->getContent());
    }
}