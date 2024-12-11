<?php
declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Shopify;


use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MockTransportException extends \RuntimeException implements TransportExceptionInterface
{
}