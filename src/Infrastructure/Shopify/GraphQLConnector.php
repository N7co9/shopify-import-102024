<?php
declare(strict_types=1);

namespace App\Infrastructure\Shopify;

use App\Domain\API\GraphQLInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GraphQLConnector implements GraphQLInterface
{
    private HttpClientInterface $client;
    private ?string $shopUrl;
    private ?string $accessToken;

    public function __construct(
        string $shopUrl = null,
        string $accessToken = null
    )
    {
        $this->shopUrl = $shopUrl ?? ($_ENV['SHOPIFY_URL'] ?? null);
        $this->accessToken = $accessToken ?? ($_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN'] ?? null);

        if (!$this->shopUrl || !$this->accessToken) {
            throw new \RuntimeException('Shopify URL and access token must be provided');
        }

        $this->client = HttpClient::create();
    }

    public function executeQuery(string $query, array $variables = []): array
    {
        $url = $this->shopUrl;
        $headers = [
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];

        $body = [
            'query' => $query,
            'variables' => $variables,
        ];

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => $headers,
                'json' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \RuntimeException("GraphQL query failed with status code $statusCode");
            }

            $data = $response->toArray();

            if (!empty($data['errors'])) {
                throw new \RuntimeException("GraphQL query returned errors: " . json_encode($data['errors']));
            }

            return $data['data'] ?? [];

        } catch (TransportExceptionInterface|
        ClientExceptionInterface|
        ServerExceptionInterface|
        RedirectionExceptionInterface $e) {
            throw new \RuntimeException('HTTP client exception: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                sprintf('Unexpected error during GraphQL query: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
