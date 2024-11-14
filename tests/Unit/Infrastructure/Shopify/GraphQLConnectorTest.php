<?php
declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Shopify;

use App\Infrastructure\Shopify\GraphQLConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GraphQLConnectorTest extends TestCase
{
    public function testExecuteQuerySuccess(): void
    {
        $query = 'query { shop { name } }';
        $variables = [];

        $expectedResponseData = ['data' => ['shop' => ['name' => 'Test Shop']]];
        $jsonResponse = json_encode($expectedResponseData);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->once())
            ->method('getContent')
            ->willReturn($jsonResponse);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://test-shop.myshopify.com/api/graphql',
                $this->callback(function ($options) use ($query, $variables) {
                    $this->assertArrayHasKey('headers', $options);
                    $this->assertEquals('application/json', $options['headers']['Content-Type']);
                    $this->assertEquals('test-access-token', $options['headers']['X-Shopify-Access-Token']);

                    $this->assertArrayHasKey('json', $options);
                    $this->assertEquals($query, $options['json']['query']);
                    $this->assertEquals($variables, $options['json']['variables']);

                    return true;
                })
            )
            ->willReturn($responseMock);

        $_ENV['SHOPIFY_URL'] = 'https://test-shop.myshopify.com/api/graphql';
        $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN'] = 'test-access-token';

        $graphQLConnector = new GraphQLConnector($httpClientMock);

        $result = $graphQLConnector->executeQuery($query, $variables);

        $this->assertEquals($expectedResponseData, $result);
    }
    public function testExecuteQueryThrowsException(): void
    {
        $query = 'query { shop { name } }';
        $variables = [];

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->expects($this->once())
            ->method('request')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $_ENV['SHOPIFY_URL'] = 'https://test-shop.myshopify.com/api/graphql';
        $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN'] = 'test-access-token';

        $graphQLConnector = new GraphQLConnector($httpClientMock);

        $this->expectException(TransportExceptionInterface::class);

        $graphQLConnector->executeQuery($query, $variables);
    }
}