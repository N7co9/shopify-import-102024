<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Product\Transport;

use App\Application\Product\Transport\ProductMessageProcessor;
use App\Application\Product\Transport\Tools\Mutation;
use App\Application\Product\Transport\Tools\ProductCreation;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;
use App\Domain\DTO\ShopifyVariant;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ProductMessageProcessorTest extends TestCase
{
    private ProductMessageProcessor $processor;
    private LoggerInterface $loggerMock;
    private GraphQLInterface $graphQLMock;
    private ProductCreation $productCreationMock;
    private Mutation $mutationMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->graphQLMock = $this->createMock(GraphQLInterface::class);
        $this->productCreationMock = $this->createMock(ProductCreation::class);
        $this->mutationMock = $this->createMock(Mutation::class);

        $this->processor = new ProductMessageProcessor(
            $this->productCreationMock,
            $this->mutationMock,
            $this->graphQLMock,
            $this->loggerMock
        );
    }

    private function createLocalizedString(string $value): LocalizedString
    {
        return new LocalizedString(
            en_US: $value,
            de_DE: $value
        );
    }

    public function testAttachLocationIdByNameWithValidVariant(): void
    {
        $variant = new ShopifyVariant(
            'abstract-sku',
            'concrete-sku',
            'Variant Title',
            1,
            '10',
            ['name' => 'Warehouse A'],
            'false',
            '15.99',
            'Shopify'
        );

        $product = new ShopifyProduct(
            'abstract-sku',
            $this->createLocalizedString('Product Title'),
            $this->createLocalizedString('Product Description'),
            'Test Vendor',
            '99.99',
            null,
            'Physical',
            false,
            $this->createLocalizedString('product-handle'),
            'active',
            'global',
            [$variant],
            'http://example.com/product.jpg',
            [],
            $this->createLocalizedString('tag1,tag2')
        );

        $graphqlResponse = [
            'locations' => [
                'edges' => [
                    ['node' => ['id' => 'location-id', 'name' => 'Warehouse A']]
                ]
            ]
        ];

        $this->graphQLMock
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($graphqlResponse);

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->processor->attachLocationIdByName($product);

        $this->assertSame('location-id', $product->variants[0]->inventoryLocation['id']);
        $this->assertSame('Warehouse A', $product->variants[0]->inventoryLocation['name']);
    }

    public function testAttachLocationIdByNameWithInvalidVariantMissingInventoryLocation(): void
    {
        $variant = new ShopifyVariant(
            'abstract-sku',
            'concrete-sku',
            'Variant Title',
            1,
            '10',
            [],
            'false',
            '15.99',
            'Shopify'
        );

        $product = new ShopifyProduct(
            'abstract-sku',
            $this->createLocalizedString('Product Title'),
            $this->createLocalizedString('Product Description'),
            'Test Vendor',
            '99.99',
            null,
            'Physical',
            false,
            $this->createLocalizedString('product-handle'),
            'active',
            'global',
            [$variant],
            'http://example.com/product.jpg',
            [],
            $this->createLocalizedString('tag1,tag2')
        );

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('No valid Inventory location found');

        $this->processor->attachLocationIdByName($product);
    }

    public function testSendTrackInventoryRequestSuccess(): void
    {
        $productSetResponse = [
            'productSet' => [
                'product' => [
                    'variants' => [
                        'nodes' => [
                            ['inventoryItem' => ['id' => 'inventory-item-id']]
                        ]
                    ]
                ]
            ]
        ];

        $graphqlResponse = [
            'inventoryItemUpdate' => [
                'userErrors' => []
            ]
        ];

        $this->mutationMock
            ->expects($this->once())
            ->method('getInventoryItemUpdateMutation')
            ->willReturn('mutation inventoryItemUpdate($id: ID!, $input: InventoryItemInput!) { ... }');

        $this->graphQLMock
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($graphqlResponse);


        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Inventory Item: inventory-item-id erfolgreich Tracking aktiviert'));

        $this->processor->sendTrackInventoryRequest($productSetResponse);
    }

    public function testSendTrackInventoryRequestWithErrors(): void
    {
        $productSetResponse = [
            'productSet' => [
                'product' => [
                    'variants' => [
                        'nodes' => [
                            ['inventoryItem' => ['id' => 'inventory-item-id']]
                        ]
                    ]
                ]
            ]
        ];

        $graphqlResponse = [
            'inventoryItemUpdate' => [
                'userErrors' => [
                    ['message' => 'Some error occurred']
                ]
            ]
        ];

        $this->mutationMock
            ->expects($this->once())
            ->method('getInventoryItemUpdateMutation')
            ->willReturn('mutation inventoryItemUpdate($id: ID!, $input: InventoryItemInput!) { ... }');

        $this->graphQLMock
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($graphqlResponse);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('An Exception occurred while sending a TrackInventoryRequest'));

        $this->processor->sendTrackInventoryRequest($productSetResponse);
    }

    public function testSendTrackInventoryRequestWithInvalidStructure(): void
    {
        $productSetResponse = [
            'productSet' => [
                'product' => [
                    'variants' => [
                        'nodes' => []
                    ]
                ]
            ]
        ];

        $this->graphQLMock
            ->expects($this->never())
            ->method('executeQuery');

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->processor->sendTrackInventoryRequest($productSetResponse);
    }

    public function testHandleGraphQLErrorThrowsRuntimeException(): void
    {
        $response = [
            'errors' => ['Some error occurred'],
            'productSet' => [
                'userErrors' => ['Some user error occurred'],
            ],
        ];

        $this->loggerMock
            ->expects($this->never())
            ->method($this->anything());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GraphQL response contains errors:');

        $this->processor->handleGraphQLError($response);
    }

    public function testHandleGraphQLErrorLogsJsonException(): void
    {
        $response = [
            'invalid' => "\xB1\x31"
        ];

        $this->loggerMock
            ->expects($this->once())
            ->method('critical')
            ->with($this->isType('string'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GraphQL response contains errors:');

        $this->processor->handleGraphQLError($response);
    }

    public function testLogSuccessWithProductId(): void
    {
        $response = [
            'productSet' => [
                'product' => [
                    'id' => 'product-id-123'
                ]
            ]
        ];

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Successfully created product with options, ID: product-id-123');

        $this->processor->logSuccess($response);
    }

    public function testLogSuccessWithUnknownProductId(): void
    {
        $response = [
            'productSet' => [
                'product' => []
            ]
        ];

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with('Successfully created product with options, ID: unknown');

        $this->processor->logSuccess($response);
    }

    public function testLogError(): void
    {
        $exception = new \Exception('Original exception message', 123);

        $this->loggerMock
            ->expects($this->once())
            ->method('critical')
            ->with($this->callback(function ($msg) use ($exception) {
                return str_contains($msg, 'Error sending product with options to Shopify: Original exception message');
            }));

        $this->processor->logError($exception);
    }


    public function testSendProductToShopifySuccess(): void
    {
        $input = ['key' => 'value'];
        $mutation = 'mutation { someGraphQLMutation }';

        $response = [
            'productSet' => [
                'product' => [
                    'id' => 'product-id',
                    'variants' => [
                        'nodes' => [
                            [
                                'inventoryItem' => ['id' => 'inventory-item-id']
                            ]
                        ]
                    ]
                ],
                'userErrors' => []
            ],
            'errors' => []
        ];

        $inventoryMutationResponse = [
            'inventoryItemUpdate' => [
                'userErrors' => []
            ]
        ];

        $this->graphQLMock
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->willReturnOnConsecutiveCalls($response, $inventoryMutationResponse);

        $this->mutationMock
            ->expects($this->once())
            ->method('getInventoryItemUpdateMutation')
            ->willReturn('mutation { someInventoryMutation }');

        $this->loggerMock
            ->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [$this->stringContains('Inventory Item: inventory-item-id erfolgreich Tracking aktiviert')],
                [$this->stringContains('Successfully created product with options, ID: product-id')]
            );

        $this->processor->sendProductToShopify($input, $mutation);
    }

    public function testSendProductToShopifyGraphQLError(): void
    {
        $input = ['key' => 'value'];
        $mutation = 'mutation { someGraphQLMutation }';
        $response = [
            'errors' => ['GraphQL error occurred']
        ];

        $this->graphQLMock
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($response);

        $this->loggerMock
            ->expects($this->never())
            ->method('info');

        $this->loggerMock
            ->expects($this->once())
            ->method('critical')
            ->with($this->callback(function ($msg) {
                return str_contains($msg, 'GraphQL response contains errors');
            }));

        $this->processor->sendProductToShopify($input, $mutation);
    }

    public function testSendProductToShopifyHandlesException(): void
    {
        $input = ['key' => 'value'];
        $mutation = 'mutation { someGraphQLMutation }';

        $this->graphQLMock
            ->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new \Exception('Unexpected error'));

        $this->loggerMock
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Unexpected error'));

        $this->processor->sendProductToShopify($input, $mutation);
    }

    public function testProcessProduct(): void
    {
        $product = $this->createMock(ShopifyProduct::class);
        $input = ['key' => 'value'];
        $mutation = 'mutation { someGraphQLMutation }';

        $this->processor = $this->getMockBuilder(ProductMessageProcessor::class)
            ->setConstructorArgs([
                $this->productCreationMock,
                $this->mutationMock,
                $this->graphQLMock,
                $this->loggerMock
            ])
            ->onlyMethods(['attachLocationIdByName', 'sendProductToShopify'])
            ->getMock();

        $this->processor
            ->expects($this->once())
            ->method('attachLocationIdByName')
            ->with($product);

        $this->productCreationMock
            ->expects($this->once())
            ->method('prepareInputData')
            ->with($product)
            ->willReturn($input);

        $this->mutationMock
            ->expects($this->once())
            ->method('getProductSetMutation')
            ->willReturn($mutation);

        $this->processor
            ->expects($this->once())
            ->method('sendProductToShopify')
            ->with($input, $mutation);

        $this->processor->processProduct($product);
    }
}
