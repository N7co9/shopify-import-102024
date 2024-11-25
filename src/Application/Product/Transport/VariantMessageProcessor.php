<?php
declare(strict_types=1);

namespace App\Application\Product\Transport;

use App\Application\Product\Transport\Tools\Mutation;
use App\Domain\API\GraphQLInterface;
use App\Domain\DTO\ConcreteProductDTO;

class VariantMessageProcessor implements VariantProcessorInterface
{
    public function __construct(
        private Mutation         $mutationHelper,
        private GraphQLInterface $graphQL
    )
    {
    }

    public function processVariant(ConcreteProductDTO $concreteProductDTO, array $inheritanceInformation): void
    {
        foreach ($inheritanceInformation['Variants'] as $variant) {
            if ($variant['sku'] === $concreteProductDTO->getAbstractSku()) {
                $locationID = $this->fetchRespectiveLocationID($concreteProductDTO);
                $variantData = $this->prepareVariantData($concreteProductDTO, $variant['id'], $locationID);
                $this->sendVariantToShopify($variantData, $inheritanceInformation['PID']);
            }
        }
    }

    public function fetchRespectiveLocationID(ConcreteProductDTO $concreteProductDTO): ?string
    {

        $query = <<<'GRAPHQL'
        query GetLocationByName($name: String!) {
          locations(first: 10, query: $name) {
            edges {
              node {
                id
                name
              }
            }
          }
        }
        GRAPHQL;

        $variables = [
            'name' => $concreteProductDTO->getLocation(),
        ];

        try {
            $response = $this->graphQL->executeQuery($query, $variables);

            if (!empty($response['errors'])) {
                throw new \RuntimeException('Shopify responded with errors: ' . json_encode($response['errors'], JSON_THROW_ON_ERROR));
            }

            foreach ($response['locations']['edges'] as $location) {
                if ($location['node']['name'] === $concreteProductDTO->getLocation()) {
                    return $location['node']['id'];
                }
            }

        } catch (\Exception $e) {
            throw new \RuntimeException('Error retrieving location by name: ' . $e->getMessage());
        }

        return null;
    }

    private function sendVariantToShopify(array $variantData, string $productId): array
    {
        $variables = [
            'productId' => $productId,
            'variants' => [$variantData],
        ];

        $mutation = $this->mutationHelper->getProductVariantsBulkUpdateMutation();

        try {
            $response = $this->graphQL->executeQuery($mutation, $variables);

            if (!empty($response['errors'])) {
                throw new \RuntimeException('Shopify responded with errors: ' . json_encode($response['errors'], JSON_THROW_ON_ERROR));
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Error sending variant to Shopify: ' . $e->getMessage());
        }
        return $response;
    }

    private function prepareVariantData(ConcreteProductDTO $concreteProductDTO, string $variantId, ?string $locationID): array
    {
        return [
            'id' => $variantId,
            'price' => $concreteProductDTO->getPriceGross(),
            'mediaSrc' => $concreteProductDTO->getImageUrl(),
        ];
    }
}
