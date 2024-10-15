<?php
declare(strict_types=1);

namespace App\Infrastructure\Shopify;


use Symfony\Contracts\HttpClient\HttpClientInterface;
class GraphQLConnector
{
    private string $apiUrl;
    private string $accessToken;

    public function __construct(private HttpClientInterface $client)
    {
        $this->apiUrl = $_ENV['SHOPIFY_URL'];
        $this->accessToken = $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN'];
    }

   // TO-DO
}