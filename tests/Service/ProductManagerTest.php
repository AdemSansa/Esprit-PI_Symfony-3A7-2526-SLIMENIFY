<?php

namespace App\Tests\Service;

use App\Entity\Product;
use App\Service\ProductManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProductManager service.
 *
 * Validates the following business rules:
 *  1. Product name is required.
 *  2. Price must be strictly greater than zero.
 *  3. Stock quantity cannot be negative.
 *  4. Category must belong to the allowed list.
 *  5. Expiration date must be in the future.
 *  6. isOutOfStock() correctly reflects stock level.
 *  7. applyDiscount() correctly calculates discounted price.
 */
class ProductManagerTest extends TestCase
{
    private ProductManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ProductManager();
    }

    // -------------------------------------------------------------------------
    // Rule 1: Product name is required
    // -------------------------------------------------------------------------

    public function testValidProductPassesValidation(): void
    {
        $product = new Product();
        $product->setName('Lavender Essential Oil');
        $product->setPrice('25.99');
        $product->setStockQuantity(10);
        $product->setCategory('Relaxing Products');

        $this->assertTrue($this->manager->validate($product));
    }

    public function testEmptyNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required.');

        $product = new Product();
        $product->setName('');
        $product->setPrice('15.00');
        $product->setStockQuantity(5);

        $this->manager->validate($product);
    }

    public function testWhitespaceOnlyNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required.');

        $product = new Product();
        $product->setName('   ');
        $product->setPrice('15.00');
        $product->setStockQuantity(5);

        $this->manager->validate($product);
    }

    // -------------------------------------------------------------------------
    // Rule 2: Price must be > 0
    // -------------------------------------------------------------------------

    public function testZeroPriceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The price must be strictly greater than zero.');

        $product = new Product();
        $product->setName('Meditation Guide');
        $product->setPrice('0');
        $product->setStockQuantity(3);

        $this->manager->validate($product);
    }

    public function testNegativePriceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The price must be strictly greater than zero.');

        $product = new Product();
        $product->setName('Meditation Guide');
        $product->setPrice('-5.00');
        $product->setStockQuantity(3);

        $this->manager->validate($product);
    }

    // -------------------------------------------------------------------------
    // Rule 3: Stock quantity cannot be negative
    // -------------------------------------------------------------------------

    public function testNegativeStockThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock quantity cannot be negative.');

        $product = new Product();
        $product->setName('Stress Relief Tea');
        $product->setPrice('12.50');
        $product->setStockQuantity(-1);

        $this->manager->validate($product);
    }

    public function testZeroStockIsAllowed(): void
    {
        $product = new Product();
        $product->setName('Calm Mind Book');
        $product->setPrice('18.00');
        $product->setStockQuantity(0);

        $this->assertTrue($this->manager->validate($product));
    }

    // -------------------------------------------------------------------------
    // Rule 4: Category must be valid
    // -------------------------------------------------------------------------

    public function testInvalidCategoryThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category');

        $product = new Product();
        $product->setName('Mystery Item');
        $product->setPrice('9.99');
        $product->setStockQuantity(5);
        $product->setCategory('Unknown Category');

        $this->manager->validate($product);
    }

    public function testValidCategoryPsychologyBooksPassesValidation(): void
    {
        $product = new Product();
        $product->setName('The Psychology of Happiness');
        $product->setPrice('22.00');
        $product->setStockQuantity(8);
        $product->setCategory('Psychology Books');

        $this->assertTrue($this->manager->validate($product));
    }

    public function testNullCategoryIsAllowed(): void
    {
        $product = new Product();
        $product->setName('Generic Wellness Product');
        $product->setPrice('10.00');
        $product->setStockQuantity(15);
        // category left null

        $this->assertTrue($this->manager->validate($product));
    }

    // -------------------------------------------------------------------------
    // Rule 5: Expiration date must be in the future
    // -------------------------------------------------------------------------

    public function testPastExpirationDateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The expiration date must be a future date.');

        $product = new Product();
        $product->setName('Vitamin C Supplement');
        $product->setPrice('14.99');
        $product->setStockQuantity(20);
        $product->setExpirationDate(new \DateTime('2020-01-01'));

        $this->manager->validate($product);
    }

    public function testFutureExpirationDatePassesValidation(): void
    {
        $product = new Product();
        $product->setName('Vitamin D3');
        $product->setPrice('19.99');
        $product->setStockQuantity(30);
        $product->setExpirationDate(new \DateTime('+2 years'));

        $this->assertTrue($this->manager->validate($product));
    }

    public function testNullExpirationDateIsAllowed(): void
    {
        $product = new Product();
        $product->setName('Herbal Tea');
        $product->setPrice('8.50');
        $product->setStockQuantity(12);
        // expirationDate left null

        $this->assertTrue($this->manager->validate($product));
    }

    // -------------------------------------------------------------------------
    // Helper method: isOutOfStock()
    // -------------------------------------------------------------------------

    public function testIsOutOfStockReturnsTrueWhenStockIsZero(): void
    {
        $product = new Product();
        $product->setStockQuantity(0);

        $this->assertTrue($this->manager->isOutOfStock($product));
    }

    public function testIsOutOfStockReturnsFalseWhenStockIsPositive(): void
    {
        $product = new Product();
        $product->setStockQuantity(5);

        $this->assertFalse($this->manager->isOutOfStock($product));
    }

    // -------------------------------------------------------------------------
    // Helper method: applyDiscount()
    // -------------------------------------------------------------------------

    public function testApplyDiscountCalculatesCorrectPrice(): void
    {
        $product = new Product();
        $product->setPrice('100.00');

        $discountedPrice = $this->manager->applyDiscount($product, 20);

        $this->assertEquals(80.00, $discountedPrice);
    }

    public function testApplyDiscountWithZeroPercentReturnsOriginalPrice(): void
    {
        $product = new Product();
        $product->setPrice('50.00');

        $discountedPrice = $this->manager->applyDiscount($product, 0);

        $this->assertEquals(50.00, $discountedPrice);
    }

    public function testApplyDiscountWith100PercentReturnsZero(): void
    {
        $product = new Product();
        $product->setPrice('75.00');

        $discountedPrice = $this->manager->applyDiscount($product, 100);

        $this->assertEquals(0.00, $discountedPrice);
    }

    public function testApplyDiscountWithInvalidPercentThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Discount must be between 0 and 100 percent.');

        $product = new Product();
        $product->setPrice('50.00');

        $this->manager->applyDiscount($product, 150);
    }
}
