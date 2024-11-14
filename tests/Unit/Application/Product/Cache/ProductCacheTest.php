<?php
declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Cache;

use App\Application\Product\Cache\ProductCache;
use App\Application\Product\Cache\ProductCacheInterface;
use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ProductCacheTest extends TestCase
{
    private ProductCacheInterface $productCache;

    protected function setUp(): void
    {
        $cacheAdapter = new ArrayAdapter();
        $this->productCache = new ProductCache($cacheAdapter);
    }

    public function testSaveAndGetAbstractProduct(): void
    {
        $abstractSku = 'TEST_SKU';
        $abstractProductDTO = new AbstractProductDTO(
            $abstractSku,
            'Abstract Product Name EN',
            'Abstract Product Name DE',
            'Abstract Product Description EN',
            'Abstract Product Description DE',
            'CategoryKey',
            'TaxSetName',
            'Meta Title EN',
            'Meta Title DE'
        );

        $this->productCache->saveAbstractProduct($abstractSku, $abstractProductDTO, 300);

        $retrievedDTO = $this->productCache->getAbstractProduct($abstractSku);

        $this->assertEquals($abstractProductDTO, $retrievedDTO);
    }

    public function testSaveAndGetConcreteProducts(): void
    {
        $abstractSku = 'TEST_SKU';

        $concreteProductDTO1 = new ConcreteProductDTO(
            $abstractSku,
            'CONCRETE_SKU1',
            'Product Name EN 1',
            'Product Name DE 1',
            'Product Description EN 1',
            'Product Description DE 1',
            10,
            false,
            99.99,
            'USD',
            'http://example.com/image1.jpg',
            true,
            false
        );

        $concreteProductDTO2 = new ConcreteProductDTO(
            $abstractSku,
            'CONCRETE_SKU2',
            'Product Name EN 2',
            'Product Name DE 2',
            'Product Description EN 2',
            'Product Description DE 2',
            5,
            true,
            89.99,
            'EUR',
            'http://example.com/image2.jpg',
            false,
            true
        );

        $this->productCache->saveConcreteProduct($abstractSku, $concreteProductDTO1, 300);
        $this->productCache->saveConcreteProduct($abstractSku, $concreteProductDTO2, 300);

        $retrievedDTOs = $this->productCache->getConcreteProducts($abstractSku);

        $this->assertCount(2, $retrievedDTOs);
        $this->assertEquals($concreteProductDTO1, $retrievedDTOs[0]);
        $this->assertEquals($concreteProductDTO2, $retrievedDTOs[1]);
    }

    public function testClearProductCache(): void
    {
        $abstractSku = 'TEST_SKU';
        $abstractProductDTO = new AbstractProductDTO(
            $abstractSku,
            'Abstract Product Name EN',
            'Abstract Product Name DE',
            'Abstract Product Description EN',
            'Abstract Product Description DE',
            'CategoryKey',
            'TaxSetName'
        );

        $concreteProductDTO = new ConcreteProductDTO(
            $abstractSku,
            'CONCRETE_SKU1',
            'Product Name EN',
            'Product Name DE',
            'Product Description EN',
            'Product Description DE'
        );

        $this->productCache->saveAbstractProduct($abstractSku, $abstractProductDTO, 300);
        $this->productCache->saveConcreteProduct($abstractSku, $concreteProductDTO, 300);

        $this->productCache->clearProductCache($abstractSku);

        $this->assertNull($this->productCache->getAbstractProduct($abstractSku));
        $this->assertEmpty($this->productCache->getConcreteProducts($abstractSku));
    }
}