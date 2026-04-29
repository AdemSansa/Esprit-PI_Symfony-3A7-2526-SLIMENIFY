<?php

namespace App\Tests\Service;

use App\Entity\Supplier;
use App\Service\SupplierManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SupplierManager service.
 *
 * Validates the following business rules:
 *  1. Supplier name is required and must be >= 3 characters.
 *  2. Email is required and must be in a valid format.
 *  3. Phone must match the Tunisian format.
 *  4. Address is required.
 *  5. Status must be 'active' or 'inactive'.
 *  6. isActive() correctly reflects the supplier status.
 */
class SupplierManagerTest extends TestCase
{
    private SupplierManager $manager;

    protected function setUp(): void
    {
        $this->manager = new SupplierManager();
    }

    // -------------------------------------------------------------------------
    // Helper: build a fully valid supplier
    // -------------------------------------------------------------------------

    private function createValidSupplier(): Supplier
    {
        $supplier = new Supplier();
        $supplier->setName('MediVita Tunisia');
        $supplier->setEmail('contact@medivita.tn');
        $supplier->setPhone('20123456');
        $supplier->setAddress('12 Avenue Habib Bourguiba, Tunis');
        $supplier->setStatus('active');

        return $supplier;
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function testValidSupplierPassesValidation(): void
    {
        $supplier = $this->createValidSupplier();

        $this->assertTrue($this->manager->validate($supplier));
    }

    // -------------------------------------------------------------------------
    // Rule 1: Name is required and must be >= 3 characters
    // -------------------------------------------------------------------------

    public function testEmptyNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The supplier name cannot be empty.');

        $supplier = $this->createValidSupplier();
        $supplier->setName('');

        $this->manager->validate($supplier);
    }

    public function testWhitespaceOnlyNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The supplier name cannot be empty.');

        $supplier = $this->createValidSupplier();
        $supplier->setName('   ');

        $this->manager->validate($supplier);
    }

    public function testNameTooShortThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The supplier name must be at least 3 characters long.');

        $supplier = $this->createValidSupplier();
        $supplier->setName('AB');

        $this->manager->validate($supplier);
    }

    public function testNameWithExactly3CharactersPassesValidation(): void
    {
        $supplier = $this->createValidSupplier();
        $supplier->setName('Bio');

        $this->assertTrue($this->manager->validate($supplier));
    }

    // -------------------------------------------------------------------------
    // Rule 2: Email is required and must be valid
    // -------------------------------------------------------------------------

    public function testEmptyEmailThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid email address is required.');

        $supplier = $this->createValidSupplier();
        $supplier->setEmail('');

        $this->manager->validate($supplier);
    }

    public function testNullEmailThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid email address is required.');

        $supplier = $this->createValidSupplier();
        $supplier->setEmail(null);

        $this->manager->validate($supplier);
    }

    public function testInvalidEmailFormatThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid email format.');

        $supplier = $this->createValidSupplier();
        $supplier->setEmail('not-an-email');

        $this->manager->validate($supplier);
    }

    public function testEmailWithoutDomainThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid email format.');

        $supplier = $this->createValidSupplier();
        $supplier->setEmail('user@');

        $this->manager->validate($supplier);
    }

    // -------------------------------------------------------------------------
    // Rule 3: Phone must match Tunisian format
    // -------------------------------------------------------------------------

    public function testEmptyPhoneThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A contact phone number is required.');

        $supplier = $this->createValidSupplier();
        $supplier->setPhone('');

        $this->manager->validate($supplier);
    }

    public function testNullPhoneThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A contact phone number is required.');

        $supplier = $this->createValidSupplier();
        $supplier->setPhone(null);

        $this->manager->validate($supplier);
    }

    public function testInvalidPhoneFormatThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('valid Tunisian phone number');

        $supplier = $this->createValidSupplier();
        $supplier->setPhone('1234567');  // 7 digits — too short

        $this->manager->validate($supplier);
    }

    public function testPhoneStartingWithInvalidDigitThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('valid Tunisian phone number');

        $supplier = $this->createValidSupplier();
        $supplier->setPhone('10000000');  // starts with 1 — not in [234579]

        $this->manager->validate($supplier);
    }

    public function testPhoneWithPlusPrefixPassesValidation(): void
    {
        $supplier = $this->createValidSupplier();
        $supplier->setPhone('+21620123456');

        $this->assertTrue($this->manager->validate($supplier));
    }

    public function testPhoneWith00PrefixPassesValidation(): void
    {
        $supplier = $this->createValidSupplier();
        $supplier->setPhone('0021620123456');

        $this->assertTrue($this->manager->validate($supplier));
    }

    // -------------------------------------------------------------------------
    // Rule 4: Address is required
    // -------------------------------------------------------------------------

    public function testEmptyAddressThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The physical address is required.');

        $supplier = $this->createValidSupplier();
        $supplier->setAddress('');

        $this->manager->validate($supplier);
    }

    public function testNullAddressThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The physical address is required.');

        $supplier = $this->createValidSupplier();
        $supplier->setAddress(null);

        $this->manager->validate($supplier);
    }

    // -------------------------------------------------------------------------
    // Rule 5: Status must be 'active' or 'inactive'
    // -------------------------------------------------------------------------

    public function testInvalidStatusThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');

        $supplier = $this->createValidSupplier();
        $supplier->setStatus('pending');

        $this->manager->validate($supplier);
    }

    public function testInactiveStatusPassesValidation(): void
    {
        $supplier = $this->createValidSupplier();
        $supplier->setStatus('inactive');

        $this->assertTrue($this->manager->validate($supplier));
    }

    // -------------------------------------------------------------------------
    // Helper method: isActive()
    // -------------------------------------------------------------------------

    public function testIsActiveReturnsTrueForActiveSupplier(): void
    {
        $supplier = $this->createValidSupplier();
        $supplier->setStatus('active');

        $this->assertTrue($this->manager->isActive($supplier));
    }

    public function testIsActiveReturnsFalseForInactiveSupplier(): void
    {
        $supplier = $this->createValidSupplier();
        $supplier->setStatus('inactive');

        $this->assertFalse($this->manager->isActive($supplier));
    }
}
