<?php
declare(strict_types=1);

namespace App\Tests\Unit\Domain\DTO;

use App\Domain\DTO\AbstractProductDTO;
use PHPUnit\Framework\TestCase;


class AbstractProductDTOTest extends TestCase
{
    public function testConstructorAndGetterMethods(): void
    {
        $dto = new AbstractProductDTO(
            '001',
            'Canon Camera',
            'Kamera',
            'Best camera',
            'Beste Kamera',
            'digital-cameras',
            'Electronics',
            'Meta Title EN',
            'Meta Title DE'
        );

        // Testing all getters
        $this->assertSame('001', $dto->getAbstractSku());
        $this->assertSame('Canon Camera', $dto->getNameEn());
        $this->assertSame('Kamera', $dto->getNameDe());
        $this->assertSame('Best camera', $dto->getDescriptionEn());
        $this->assertSame('Beste Kamera', $dto->getDescriptionDe());
        $this->assertSame('digital-cameras', $dto->getCategoryKey());
        $this->assertSame('Electronics', $dto->getTaxSetName());
        $this->assertSame('Meta Title EN', $dto->getMetaTitleEn());
        $this->assertSame('Meta Title DE', $dto->getMetaTitleDe());
    }

    public function testSetterMethods(): void
    {
        $dto = new AbstractProductDTO(
            '001',
            'Canon Camera',
            'Kamera',
            'Best camera',
            'Beste Kamera',
            'digital-cameras',
            'Electronics'
        );

        // Test setting new values and then retrieving them via getters
        $dto->setMetaTitleEn('Updated Meta Title EN');
        $this->assertSame('Updated Meta Title EN', $dto->getMetaTitleEn());

        $dto->setMetaTitleDe('Aktualisierter Meta Title DE');
        $this->assertSame('Aktualisierter Meta Title DE', $dto->getMetaTitleDe());

        $dto->setNameEn('Updated Name EN');
        $this->assertSame('Updated Name EN', $dto->getNameEn());

        $dto->setNameDe('Aktualisierter Name DE');
        $this->assertSame('Aktualisierter Name DE', $dto->getNameDe());

        $dto->setDescriptionEn('Updated Description EN');
        $this->assertSame('Updated Description EN', $dto->getDescriptionEn());

        $dto->setDescriptionDe('Aktualisierte Beschreibung DE');
        $this->assertSame('Aktualisierte Beschreibung DE', $dto->getDescriptionDe());

        $dto->setCategoryKey('new-category');
        $this->assertSame('new-category', $dto->getCategoryKey());

        $dto->setTaxSetName('New Tax Set');
        $this->assertSame('New Tax Set', $dto->getTaxSetName());
    }

    public function testSetAbstractSku(): void
    {
        $dto = new AbstractProductDTO(
            '001',
            'Canon Camera',
            'Kamera',
            'Best camera',
            'Beste Kamera',
            'digital-cameras',
            'Electronics'
        );

        // Test setting and getting Abstract SKU
        $dto->setAbstractSku('002');
        $this->assertSame('002', $dto->getAbstractSku());
    }

    public function testLabelsManagementAttributes(): void
    {
        $dto = new AbstractProductDTO(
            '001',
            'Canon Camera',
            'Kamera',
            'Best camera',
            'Beste Kamera',
            'digital-cameras',
            'Electronics'
        );

        // Test setting and getting labels
        $dto->setLabels(['Label1', 'Label2']);
        $this->assertSame(['Label1', 'Label2'], $dto->getLabels());

        // Test setting and getting management attributes
        $dto->setManagementAttributes(['Attribute1', 'Attribute2']);
        $this->assertSame(['Attribute1', 'Attribute2'], $dto->getManagementAttributes());
    }
}