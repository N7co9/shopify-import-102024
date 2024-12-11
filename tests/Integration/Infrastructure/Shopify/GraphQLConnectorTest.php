<?php
declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Shopify;

use App\Infrastructure\Shopify\GraphQLConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GraphQLConnectorTest extends TestCase
{

    public function testExecuteQueryHandlesUnexpectedError1(): void
    {
        $exceptionMessage = 'Unexpected Error';

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->method('request')
            ->willThrowException(new \Exception($exceptionMessage));

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        try {
            $connector->executeQuery('query { shop { name } }');
        } catch (\RuntimeException $e) {
            $this->assertSame(0, $e->getCode());
            $this->assertStringContainsString('Unexpected error during GraphQL query:', $e->getMessage());
        }
    }
    public function testExecuteQueryDecrementsExceptionCode(): void
    {
        $exceptionMessage = 'Simulated Transport Error';

        $transportExceptionMock = $this->createMock(TransportExceptionInterface::class);
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->method('request')
            ->willThrowException($transportExceptionMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        try {
            $connector->executeQuery('query { shop { name } }');
        } catch (\RuntimeException $e) {
            $this->assertSame(0, $e->getCode());
            $this->assertStringContainsString('HTTP client exception:', $e->getMessage());
        }
    }

    public function testExecuteQueryConcatenatesExceptionMessage(): void
    {
        $exceptionMessage = 'Simulated Transport Error';

        $transportExceptionMock = new MockTransportException($exceptionMessage);

        $httpClientMock = $this->createMock(\Symfony\Contracts\HttpClient\HttpClientInterface::class);
        $httpClientMock
            ->method('request')
            ->willThrowException($transportExceptionMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP client exception: ' . $exceptionMessage);

        $connector->executeQuery('query { shop { name } }');
    }

    public function testExecuteQueryHandlesRedirectionException(): void
    {
        $query = 'query { shop { name } }';
        $exceptionMock = $this->createMock(RedirectionExceptionInterface::class);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exceptionMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP client exception:');

        $connector->executeQuery($query);
    }

    public function testExecuteQueryHandlesSpecificExceptions(): void
    {
        $query = 'query { shop { name } }';
        $exceptionMock = $this->createMock(ServerExceptionInterface::class);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exceptionMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP client exception:');

        $connector->executeQuery($query);
    }

    public function testExecuteQueryHandlesClientException(): void
    {
        $query = 'query { shop { name } }';

        $clientExceptionMock = $this->createMock(ClientExceptionInterface::class);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willThrowException($clientExceptionMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP client exception:');

        $connector->executeQuery($query);
    }


    public function testExecuteQueryThrowsExceptionWithCorrectMessageOnGraphQLErrors(): void
    {
        $query = 'query { shop { name } }';
        $responseBody = ['errors' => [['message' => 'GraphQL error occurred']]];

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('toArray')->willReturn($responseBody);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GraphQL query returned errors: [{"message":"GraphQL error occurred"}]');

        $connector->executeQuery($query);
    }

    public function testExecuteQueryThrowsExceptionIfContentTypeHeaderMissing(): void
    {
        $query = 'query { shop { name } }';
        $responseBody = ['data' => ['shop' => ['name' => 'Test Shop']]];

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('toArray')->willReturn($responseBody);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->isType('string'),
                $this->callback(function ($options) {
                    // Assert the presence of the Content-Type header
                    return isset($options['headers']['Content-Type']) &&
                        $options['headers']['Content-Type'] === 'application/json';
                })
            )
            ->willReturn($responseMock);

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'test-access-token');
        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'client');
        $reflection->setValue($connector, $httpClientMock);

        $result = $connector->executeQuery($query);

        $this->assertSame($responseBody['data'], $result);
    }

    public function testConstructorThrowsExceptionWhenOnlyShopUrlProvided(): void
    {
        unset($_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shopify URL and access token must be provided');

        new GraphQLConnector('https://test-shop.myshopify.com', null);
    }

    public function testConstructorThrowsExceptionWhenOnlyAccessTokenProvided(): void
    {
        unset($_ENV['SHOPIFY_URL']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shopify URL and access token must be provided');

        new GraphQLConnector(null, 'test-access-token');
    }

    public function testConstructorUsesShopUrlParameterOverEnv(): void
    {
        $_ENV['SHOPIFY_URL'] = 'https://env-shop.myshopify.com';

        $connector = new GraphQLConnector('https://param-shop.myshopify.com', 'test-access-token');

        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'shopUrl');

        $this->assertSame('https://param-shop.myshopify.com', $reflection->getValue($connector));
    }

    public function testConstructorUsesAccessTokenParameterOverEnv(): void
    {
        $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN'] = 'env-access-token';

        $connector = new GraphQLConnector('https://test-shop.myshopify.com', 'param-access-token');

        $reflection = new \ReflectionProperty(GraphQLConnector::class, 'accessToken');

        $this->assertSame('param-access-token', $reflection->getValue($connector));
    }

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