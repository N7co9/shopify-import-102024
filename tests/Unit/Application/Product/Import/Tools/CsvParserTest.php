<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Import\Tools;

use App\Application\Product\Import\Tools\CsvParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class CsvParserTest extends TestCase
{
    private CsvParser $parser;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new CsvParser();
        $this->tempDir = sys_get_temp_dir() . '/csv_parser_tests_' . uniqid('', true);

        if (!mkdir($this->tempDir) && !is_dir($this->tempDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->tempDir));
        }
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($this->tempDir);

        parent::tearDown();
    }

    public function testParseValidCsvFile(): void
    {
        $csvContent = "name,age,city\nAlice,30,New York\nBob,25,Boston";
        $filePath = $this->tempDir . '/valid.csv';
        file_put_contents($filePath, $csvContent);

        $result = $this->parser->parse($filePath);

        $expected = [
            ['name' => 'Alice', 'age' => '30', 'city' => 'New York'],
            ['name' => 'Bob', 'age' => '25', 'city' => 'Boston'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testParseCsvFileWithOnlyHeader(): void
    {
        // Arrange
        $csvContent = "name,age,city\n";
        $filePath = $this->tempDir . '/only_header.csv';
        file_put_contents($filePath, $csvContent);

        // Act
        $result = $this->parser->parse($filePath);

        // Assert
        $this->assertSame([], $result);
    }

    public function testParseCsvFileNotFound(): void
    {
        // Arrange
        $filePath = $this->tempDir . '/non_existent.csv';

        // Expect exception
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessageMatches('/The file ".*non_existent\.csv" does not exist/');

        // Act
        $this->parser->parse($filePath);
    }

    public function testParseCsvFileNotReadable(): void
    {
        // Arrange
        $csvContent = "name,age,city\nAlice,30,New York";
        $filePath = $this->tempDir . '/not_readable.csv';
        file_put_contents($filePath, $csvContent);

        // Make file not readable
        chmod($filePath, 0200); // Write-only

        // Expect exception
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/The file ".*not_readable\.csv" is not readable/');

        // Act
        $this->parser->parse($filePath);
    }

    public function testParseEmptyCsvFile(): void
    {
        // Arrange
        $filePath = $this->tempDir . '/empty.csv';
        file_put_contents($filePath, '');

        // Act
        $result = $this->parser->parse($filePath);

        // Assert
        $this->assertSame([], $result);
    }

    public function testParseCsvFileWithDuplicateHeaderColumns(): void
    {
        // Arrange
        $csvContent = "name,age,name\nAlice,30,AliceAlias\nBob,25,BobAlias";
        $filePath = $this->tempDir . '/duplicate_header.csv';
        file_put_contents($filePath, $csvContent);

        // Act
        $result = $this->parser->parse($filePath);

        // Assert
        $expected = [
            ['name' => 'AliceAlias', 'age' => '30'],
            ['name' => 'BobAlias', 'age' => '25'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testParseCsvFileWithSpecialCharacters(): void
    {
        // Arrange
        $csvContent = "name,age,city\n\"Alice, the Great\",30,\"New York\"\nBob,25,\"Boston, MA\"";
        $filePath = $this->tempDir . '/special_characters.csv';
        file_put_contents($filePath, $csvContent);

        // Act
        $result = $this->parser->parse($filePath);

        // Assert
        $expected = [
            ['name' => 'Alice, the Great', 'age' => '30', 'city' => 'New York'],
            ['name' => 'Bob', 'age' => '25', 'city' => 'Boston, MA'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testParseCsvFileWithEmptyRows(): void
    {
        // Arrange
        $csvContent = "name,age,city\n,,\nAlice,30,New York\n,,\nBob,25,Boston";
        $filePath = $this->tempDir . '/empty_rows.csv';
        file_put_contents($filePath, $csvContent);

        // Act
        $result = $this->parser->parse($filePath);

        // Assert
        $expected = [
            ['name' => '', 'age' => '', 'city' => ''],
            ['name' => 'Alice', 'age' => '30', 'city' => 'New York'],
            ['name' => '', 'age' => '', 'city' => ''],
            ['name' => 'Bob', 'age' => '25', 'city' => 'Boston'],
        ];

        $this->assertSame($expected, $result);
    }

    public function testParseCsvFileWithNoHeaderRow(): void
    {
        $csvContent = "Alice,30,New York\nBob,25,Boston";
        $filePath = $this->tempDir . '/no_header.csv';
        file_put_contents($filePath, $csvContent);

        $result = $this->parser->parse($filePath);

        $expected = [
            ['Alice' => 'Bob', '30' => '25', 'New York' => 'Boston'],
        ];

        $this->assertSame($expected, $result);
    }
}
