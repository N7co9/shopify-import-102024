<?php
declare(strict_types=1);

namespace App\Infrastructure\ElasticSearch;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;

class ElasticSearchApiConnector
{
    private string $endpoint;
    private HttpClientInterface $httpClient;

    public function __construct(
        string $host = 'http://127.0.0.1:9200',
        ?HttpClientInterface $httpClient = null
    ) {
        $this->endpoint = rtrim($host, '/');
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function indexDocument(string $indexName, array $document): bool
    {
        $url = sprintf('%s/%s/_doc', $this->endpoint, $indexName);

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => $document,
        ]);

        $statusCode = $response->getStatusCode();
        return ($statusCode >= 200 && $statusCode < 300);
    }
}
