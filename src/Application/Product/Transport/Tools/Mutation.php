<?php
declare(strict_types=1);

namespace App\Application\Product\Transport\Tools;

class Mutation
{
    public function getProductCreateMutation(): string
    {
        return <<<'GRAPHQL'
            mutation CreateProductWithoutOptions($input: ProductInput!, $media: [CreateMediaInput!]) {
                productCreate(input: $input, media: $media) {
                    product {
                        id
                        title
                        media(first: 10) {
                            nodes {
                                id
                                alt
                                mediaContentType
                                status
                            }
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;
    }

    public function getProductSetMutation(): string
    {
        return <<<'GRAPHQL'
            mutation createProductWithMedia($productSet: ProductSetInput!, $synchronous: Boolean!) {
                productSet(synchronous: $synchronous, input: $productSet) {
                    product {
                        id
                        media(first: 5) {
                            nodes {
                                id
                                alt
                                mediaContentType
                                status
                            }
                        }
                        variants(first: 5) {
                            nodes {                      
                                title
                                price
                                compareAtPrice
                                media(first: 5) {
                                    nodes {
                                        id
                                        alt
                                        mediaContentType
                                        status
                                    }
                                }
                            }
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;
    }

}