<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Import\Abstract;


use App\Application\Import\Abstract\AbstractProductImporter;
use App\Domain\DTO\AbstractProductDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

class AbstractProductImporterTest extends TestCase
{
    public function testImportWithValidFile(): void
    {
        $importer = new AbstractProductImporter();

        $filePath = __DIR__ . '/../../../../Fixtures/valid_products.csv';
        $fileMock = $this->createMock(File::class);
        $this->assertInstanceOf(File::class, $fileMock);

        $result = $importer->import($filePath);
        $this->assertIsArray($result);
        $this->assertInstanceOf(AbstractProductDTO::class, $result[0]);
    }

    public function testImportWithInvalidFile(): void
    {
        $importer = new AbstractProductImporter();

        $this->expectException(FileException::class);
        $filePath = __DIR__ . '/fixtures/invalid_file.csv';

        $importer->import($filePath);
    }
}