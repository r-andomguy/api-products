<?php

namespace ContatoSeguro\Tests\Unit\Model;

use Contatoseguro\TesteBackend\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Contatoseguro\TesteBackend\Model\Product;

class ProductTest extends TestCase
{
  public function testConstructor(): void
  {
    $product = new Product(1, 2, 'Test Product', 99.99, true, '2023-10-01', 10);

    $this->assertEquals(1, $product->id);
    $this->assertEquals(2, $product->companyId);
    $this->assertEquals('Test Product', $product->title);
    $this->assertEquals(99.99, $product->price);
    $this->assertTrue($product->active);
    $this->assertEquals('2023-10-01', $product->createdAt);
    $this->assertEquals(10, $product->stock);
  }

  public function testHydrateByFetch(): void
  {
    $fetch = new \stdClass();
    $fetch->id = 1;
    $fetch->company_id = 2;
    $fetch->title = 'Test Product';
    $fetch->price = 99.99;
    $fetch->active = true;
    $fetch->created_at = '2023-10-01';
    $fetch->stock = 10;

    $product = Product::hydrateByFetch($fetch);
    
    $this->assertEquals(1, $product->id);
    $this->assertEquals(2, $product->companyId);
    $this->assertEquals('Test Product', $product->title);
    $this->assertEquals(99.99,$product->price);
    $this->assertTrue($product->active);
    $this->assertEquals('2023-10-01', $product->createdAt);
    $this->assertEquals(10, $product->stock);
  }

  public function testSetCategory(): void
  {
    $product = new Product(1, 2, 'Test Product', 99.99, true, '2023-10-01', null);
    $product->setCategory('Electronics');

    $this->assertEquals('Electronics', $product->category);
  }

  public function testInsertComment(): void
  {
    $productService = new ProductService();

    $body = [
      'productId' => 1,
      'content' => 'Ótimo produto!',
      'parentId' => null,
      'createdAt' => date("Y-m-d H:i:s")
    ];

    $userId = 1;

    $insertResult = $productService->insertComment($body, $userId);

    $this->assertIsObject($insertResult);
    $this->assertInstanceOf(\PDOStatement::class, $insertResult);
  }

  public function testGetComments(): void {
    $productService = new ProductService();
    $comments = $productService->getComments(1);

    $this->assertIsArray($comments);
    $this->assertNotEmpty($comments);

    $comment = $comments[0];

    $this->assertEquals(1, $comment->product_id);
    $this->assertEquals(1, $comment->user_id);
    $this->assertEquals('Ótimo produto!', $comment->content);
    $this->assertNull($comment->parent_id);
    $this->assertEquals('rivers', $comment->user_name);
  }
  
}
