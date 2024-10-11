<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Import\Concrete;

use App\Application\Import\Concrete\ConcreteProductImporter;
use App\Shared\DTO\ConcreteProductDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class ConcreteProductImporterTest extends TestCase
{
    public function testImportWithValidFile(): void
    {
        $importer = new ConcreteProductImporter();

        $filePath = __DIR__ . '/../../../../Fixtures/valid_concrete_products.csv';
        $fileMock = $this->createMock(File::class);
        $this->assertInstanceOf(File::class, $fileMock);

        $result = $importer->import($filePath);
        $this->assertIsArray($result);
        $this->assertInstanceOf(ConcreteProductDTO::class, $result[0]);
    }

    public function testImportWithInvalidFile(): void
    {
        $importer = new ConcreteProductImporter();

        $this->expectException(FileException::class);
        $filePath = __DIR__ . '/fixtures/invalid_file.csv';

        $importer->import($filePath);
    }
}