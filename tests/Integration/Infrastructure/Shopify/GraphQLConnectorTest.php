<?php
declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Shopify;

use App\Infrastructure\Shopify\GraphQLConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GraphQLConnectorTest extends TestCase
{
    public function testConstructorWithParameters(): void
    {
        $shopUrl = 'https://test-shop.myshopify.com';
        $accessToken = 'test-access-token';

        $connector = new GraphQLConnector($shopUrl, $accessToken);

        $this->assertInstanceOf(GraphQLConnector::class, $connector);
    }

    public function testConstructorWithEnvironmentVariables(): void
    {
        $_ENV['SHOPIFY_URL'] = 'https://test-shop.myshopify.com';
        $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN'] = 'test-access-token';

        $connector = new GraphQLConnector();

        $this->assertInstanceOf(GraphQLConnector::class, $connector);
    }

    public function testConstructorThrowsExceptionWhenMissingValues(): void
    {
        unset($_ENV['SHOPIFY_URL'], $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shopify URL and access token must be provided');

        new GraphQLConnector();
    }

    public function testExecuteQuerySuccess(): void
    {
        $query = 'query { shop { name } }';
        $variables = ['key' => 'value'];
        $responseBody = ['data' => ['shop' => ['name' => 'Test Shop']]];

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->method('getStatusCode')
            ->willReturn(200);
        $responseMock
            ->method('toArray')
            ->willReturn($responseBody);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', $this->isType('string'), $this->callback(function ($options) use ($query, $variables) {
                return $options['headers']['X-Shopify-Access-Token'] === 'test-access-token'
                    && $options['json']['query'] === $query
                    && $options['json']['variables'] === $variables;
            }))
            ->willReturn($responseMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $result = $connector->executeQuery($query, $variables);

        $this->assertSame($responseBody['data'], $result);
    }

    public function testExecuteQueryHandlesHttpError(): void
    {
        $query = 'query { shop { name } }';

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP client exception:');

        $connector->executeQuery($query);
    }

    public function testExecuteQueryHandlesGraphQLError(): void
    {
        $query = 'query { shop { name } }';
        $responseBody = ['errors' => [['message' => 'GraphQL error']]];

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->method('getStatusCode')
            ->willReturn(200);
        $responseMock
            ->method('toArray')
            ->willReturn($responseBody);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GraphQL query returned errors:');

        $connector->executeQuery($query);
    }

    public function testExecuteQueryHandlesUnexpectedError(): void
    {
        $query = 'query { shop { name } }';

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Unexpected error'));

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected error during GraphQL query:');

        $connector->executeQuery($query);
    }

    public function testExecuteQueryHandlesNon200StatusCode(): void
    {
        $query = 'query { shop { name } }';

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock
            ->method('getStatusCode')
            ->willReturn(500);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', $this->isType('string'), $this->isType('array'))
            ->willReturn($responseMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GraphQL query failed with status code 500');

        $connector->executeQuery($query);
    }

}