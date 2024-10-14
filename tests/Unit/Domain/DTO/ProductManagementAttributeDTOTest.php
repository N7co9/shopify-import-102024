<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\ProductManagementAttributeDTO;
use PHPUnit\Framework\TestCase;

class ProductManagementAttributeDTOTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $dto = new ProductManagementAttributeDTO('Key', 'Text', true, false, 'Value', 'Key EN', 'Key DE');

        $this->assertSame('Key', $dto->getKey());
        $this->assertSame('Text', $dto->getInputType());
        $this->assertTrue($dto->allowsInput());
        $this->assertFalse($dto->isMultiple());
        $this->assertSame('Value', $dto->getValues());
        $this->assertSame('Key EN', $dto->getKeyTranslationEn());
        $this->assertSame('Key DE', $dto->getKeyTranslationDe());
    }
}