<?php
declare(strict_types=1);

namespace App\Tests\Integration\Application\Product\Transport;

use App\Application\Logger\LoggerInterface;
use App\Application\Product\Transport\ProductMessageProcessor;
use App\Application\Product\Transport\Tools\Mutation;
use App\Application\Product\Transport\Tools\ProductCreation;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\LocalizedString;
use App\Domain\DTO\ShopifyProduct;
use App\Domain\DTO\ShopifyVariant;
use PHPUnit\Framework\TestCase;
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

    public function testAttachLocationIdByNameWithValidVariant(): void
    {
        $variant = new ShopifyVariant(
            abstractSku: 'abstract-sku',
            concreteSku: 'concrete-sku',
            title: 'Variant Title',
            position: 1,
            inventoryQuantity: '10',
            inventoryLocation: ['name' => 'Warehouse A'],
            isNeverOutOfStock: 'false',
            price: '15.99',
            imageUrl: 'http://example.com/image.jpg'
        );

        $product = new ShopifyProduct(
            abstractSku: 'abstract-sku',
            title: $this->createLocalizedString('Product Title'),
            bodyHtml: $this->createLocalizedString('Product Description'),
            vendor: 'Test Vendor',
            price: '99.99',
            compareAtPrice: null,
            productType: 'Physical',
            isGiftCard: false,
            handle: $this->createLocalizedString('product-handle'),
            status: 'active',
            publishedScope: 'global',
            variants: [$variant],
            imageUrl: 'http://example.com/product.jpg',
            attributes: [],
            tags: $this->createLocalizedString('tag1,tag2')
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
            ->with(
                $this->stringContains('query($locationName: String!)'),
                $this->equalTo(['locationName' => 'Warehouse A'])
            )
            ->willReturn($graphqlResponse);

        $this->processor->attachLocationIdByName($product);

        $this->assertSame('location-id', $product->variants[0]->inventoryLocation['id']);
        $this->assertSame('Warehouse A', $product->variants[0]->inventoryLocation['name']);
    }

    public function testAttachLocationIdByNameWithInvalidVariantMissingInventoryLocation(): void
    {
        $variant = new ShopifyVariant(
            abstractSku: 'abstract-sku',
            concreteSku: 'concrete-sku',
            title: 'Variant Title',
            position: 1,
            inventoryQuantity: '10',
            inventoryLocation: [],
            isNeverOutOfStock: 'false',
            price: '15.99',
            imageUrl: 'http://example.com/image.jpg'
        );

        $product = new ShopifyProduct(
            abstractSku: 'abstract-sku',
            title: $this->createLocalizedString('Product Title'),
            bodyHtml: $this->createLocalizedString('Product Description'),
            vendor: 'Test Vendor',
            price: '99.99',
            compareAtPrice: null,
            productType: 'Physical',
            isGiftCard: false,
            handle: $this->createLocalizedString('product-handle'),
            status: 'active',
            publishedScope: 'global',
            variants: [$variant],
            imageUrl: 'http://example.com/product.jpg',
            attributes: [],
            tags: $this->createLocalizedString('tag1,tag2')
        );

        $this->loggerMock->expects($this->once())
            ->method('logException')
            ->with(new RuntimeException('No valid Inventory location found'), 'api');

        $this->processor->attachLocationIdByName($product);
    }

    public function testSendTrackInventoryRequestSuccess(): void
    {
        $productSetResponse = [
            'productSet' => [
                'product' => [
                    'variants' => [
                        'nodes' => [
                            [
                                'inventoryItem' => ['id' => 'inventory-item-id']
                            ]
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
            ->with(
                $this->stringContains('mutation inventoryItemUpdate'),
                $this->equalTo([
                    'id' => 'inventory-item-id',
                    'input' => ['tracked' => true],
                ])
            )
            ->willReturn($graphqlResponse);

        $this->loggerMock
            ->expects($this->once())
            ->method('logSuccess')
            ->with($this->stringContains('Inventory Item: inventory-item-id erfolgreich Tracking aktiviert'), 'api');

        $this->processor->sendTrackInventoryRequest($productSetResponse);
    }

    public function testSendTrackInventoryRequestWithErrors(): void
    {
        $productSetResponse = [
            'productSet' => [
                'product' => [
                    'variants' => [
                        'nodes' => [
                            [
                                'inventoryItem' => ['id' => 'inventory-item-id']
                            ]
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
            ->with(
                $this->stringContains('mutation inventoryItemUpdate'),
                $this->equalTo([
                    'id' => 'inventory-item-id',
                    'input' => ['tracked' => true],
                ])
            )
            ->willReturn($graphqlResponse);

        $this->loggerMock
            ->expects($this->once())
            ->method('logException')
            ->with(
                $this->isInstanceOf(RuntimeException::class),
                'api'
            );

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
            ->method('logSuccess');

        $this->loggerMock
            ->expects($this->never())
            ->method('logException');

        $this->processor->sendTrackInventoryRequest($productSetResponse);
    }

    public function testPrepareVariables(): void
    {
        $input = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $expected = [
            'synchronous' => true,
            'productSet' => $input,
        ];

        $result = $this->processor->prepareVariables($input);

        $this->assertSame($expected, $result, 'The prepareVariables method did not return the expected output.');
    }
    public function testHasGraphQLErrorsWithErrorsKey(): void
    {
        $response = [
            'errors' => ['Some error occurred'],
        ];

        $result = $this->processor->hasGraphQLErrors($response);

        $this->assertTrue($result, 'The method should return true when the "errors" key is not empty.');
    }

    public function testHasGraphQLErrorsWithUserErrorsKey(): void
    {
        $response = [
            'productSet' => [
                'userErrors' => ['Some user error occurred'],
            ],
        ];

        $result = $this->processor->hasGraphQLErrors($response);

        $this->assertTrue($result, 'The method should return true when the "userErrors" key is not empty.');
    }

    public function testHasGraphQLErrorsWithNoErrors(): void
    {
        $response = [
            'productSet' => [
                'userErrors' => [],
            ],
            'errors' => [],
        ];

        $result = $this->processor->hasGraphQLErrors($response);

        $this->assertFalse($result, 'The method should return false when neither "errors" nor "userErrors" keys contain errors.');
    }

    public function testHasGraphQLErrorsWithMissingKeys(): void
    {
        $response = [];

        $result = $this->processor->hasGraphQLErrors($response);

        $this->assertFalse($result, 'The method should return false when "errors" and "userErrors" keys are missing.');
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
            ->method('logException');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('GraphQL response contains errors: %s', json_encode($response, JSON_THROW_ON_ERROR)));

        $this->processor->handleGraphQLError($response);
    }

    public function testHandleGraphQLErrorLogsJsonException(): void
    {
        $response = [
            'invalid' => "\xB1\x31"
        ];

        $this->loggerMock
            ->expects($this->once())
            ->method('logException')
            ->with($this->isInstanceOf(\JsonException::class), 'api');

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
            ->method('logSuccess')
            ->with(
                'Successfully created product with options, ID: product-id-123',
                'api'
            );

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
            ->method('logSuccess')
            ->with(
                'Successfully created product with options, ID: unknown',
                'api'
            );

        $this->processor->logSuccess($response);
    }

    public function testLogError(): void
    {
        $exception = new \Exception('Original exception message', 123);

        $this->loggerMock
            ->expects($this->once())
            ->method('logException')
            ->with(
                $this->callback(function (RuntimeException $runtimeException) use ($exception) {
                    return $runtimeException->getMessage() === 'Error sending product with options to Shopify: Original exception message'
                        && $runtimeException->getCode() === $exception->getCode()
                        && $runtimeException->getPrevious() === $exception;
                }),
                'api'
            );

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
            ->willReturnMap([
                [$mutation, $this->processor->prepareVariables($input), $response], // First call
                ['mutation { someInventoryMutation }', [
                    'id' => 'inventory-item-id',
                    'input' => ['tracked' => true],
                ], $inventoryMutationResponse],
            ]);

        $this->mutationMock
            ->expects($this->once())
            ->method('getInventoryItemUpdateMutation')
            ->willReturn('mutation { someInventoryMutation }');

        $this->loggerMock
            ->expects($this->exactly(2))
            ->method('logSuccess');

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
            ->with($mutation, $this->processor->prepareVariables($input))
            ->willReturn($response);

        $this->loggerMock
            ->expects($this->never())
            ->method('logSuccess');

        $this->loggerMock
            ->expects($this->once())
            ->method('logException')
            ->with($this->isInstanceOf(RuntimeException::class), 'api');

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
            ->method('logException')
            ->with(
                $this->callback(function (RuntimeException $exception) {
                    return str_contains($exception->getMessage(), 'Unexpected error');
                }),
                'api'
            );

        $this->processor->sendProductToShopify($input, $mutation);
    }

    public function testProcessProduct(): void
    {
        $product = $this->createMock(ShopifyProduct::class);
        $input = ['key' => 'value'];
        $mutation = 'mutation { someGraphQLMutation }';

        // Mock attachLocationIdByName to be called once with the product
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

        // Mock prepareInputData to return input
        $this->productCreationMock
            ->expects($this->once())
            ->method('prepareInputData')
            ->with($product)
            ->willReturn($input);

        // Mock getProductSetMutation to return the mutation
        $this->mutationMock
            ->expects($this->once())
            ->method('getProductSetMutation')
            ->willReturn($mutation);

        // Mock sendProductToShopify to be called once with the correct arguments
        $this->processor
            ->expects($this->once())
            ->method('sendProductToShopify')
            ->with($input, $mutation);

        $this->processor->processProduct($product);
    }

    private function createLocalizedString(string $value): LocalizedString
    {
        return new LocalizedString(
            en_US: $value,
            de_DE: $value
        );
}

}