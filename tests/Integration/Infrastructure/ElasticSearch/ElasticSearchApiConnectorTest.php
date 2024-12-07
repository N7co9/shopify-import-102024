<?php
declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\ElasticSearch;

use App\Infrastructure\ElasticSearch\ElasticSearchApiConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ElasticSearchApiConnectorTest extends TestCase
{
    public function testIndexDocumentReturnsTrueOnSuccess(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->method('getStatusCode')
            ->willReturn(201);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', $this->stringContains('/test_index/_doc'), $this->callback(function ($options) {
                return isset($options['json'])
                    && $options['json'] === ['title' => 'Test Document']
                    && isset($options['headers'])
                    && $options['headers']['Content-Type'] === 'application/json';
            }))
            ->willReturn($responseMock);

        $connector = new ElasticSearchApiConnector('http://127.0.0.1:9200', $httpClientMock);
        $this->assertTrue($connector->indexDocument('test_index', ['title' => 'Test Document']));
    }

    public function testIndexDocumentReturnsFalseOnServerError(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->method('getStatusCode')
            ->willReturn(500);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->method('request')
            ->willReturn($responseMock);

        $connector = new ElasticSearchApiConnector('http://127.0.0.1:9200', $httpClientMock);
        $this->assertFalse($connector->indexDocument('test_index', ['foo' => 'bar']));
    }
}