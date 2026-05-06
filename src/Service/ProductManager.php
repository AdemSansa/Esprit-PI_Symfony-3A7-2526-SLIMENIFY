<?php

namespace App\Service;

use App\Entity\Product;

/**
 * ProductManager handles the business rule validation for Product entities.
 *
 * Rules enforced:
 *  1. The product name is required (cannot be empty).
 *  2. The price must be strictly greater than zero.
 *  3. The stock quantity cannot be negative.
 *  4. The category, if provided, must belong to the allowed list.
 *  5. The expiration date, if provided, must be in the future.
 */
class ProductManager
{
    private const VALID_CATEGORIES = [
        'Authorized Vitamins',
        'Psychology Books',
        'Relaxing Products',
        'Therapeutic Games & Activities',
    ];

    /**
     * Validates a Product against all defined business rules.
     *
     * @throws \InvalidArgumentException when any rule is violated
     */
    public function validate(Product $product): bool
    {
        // Rule 1: Name is required
        if (empty(trim((string) $product->getName()))) {
            throw new \InvalidArgumentException('Product name is required.');
        }

        // Rule 2: Price must be greater than zero
        $price = (float) $product->getPrice();
        if ($price <= 0) {
            throw new \InvalidArgumentException('The price must be strictly greater than zero.');
        }

        // Rule 3: Stock quantity cannot be negative
        if ($product->getStockQuantity() < 0) {
            throw new \InvalidArgumentException('Stock quantity cannot be negative.');
        }

        // Rule 4: Category must be valid (if provided)
        $category = $product->getCategory();
        if ($category !== null && !in_array($category, self::VALID_CATEGORIES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid category "%s". Must be one of: %s.',
                $category,
                implode(', ', self::VALID_CATEGORIES)
            ));
        }

        // Rule 5: Expiration date must be in the future (if provided)
        $expirationDate = $product->getExpirationDate();
        if ($expirationDate !== null) {
            $today = new \DateTime('today');
            if ($expirationDate <= $today) {
                throw new \InvalidArgumentException('The expiration date must be a future date.');
            }
        }

        return true;
    }

    /**
     * Returns whether the product is considered out of stock.
     */
    public function isOutOfStock(Product $product): bool
    {
        return $product->getStockQuantity() <= 0;
    }

    /**
     * Applies a discount percentage to the product price.
     *
     * @throws \InvalidArgumentException if discount is not between 0 and 100
     */
    public function applyDiscount(Product $product, float $discountPercent): float
    {
        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new \InvalidArgumentException('Discount must be between 0 and 100 percent.');
        }

        $originalPrice = (float) $product->getPrice();
        return round($originalPrice * (1 - $discountPercent / 100), 2);
    }
}
