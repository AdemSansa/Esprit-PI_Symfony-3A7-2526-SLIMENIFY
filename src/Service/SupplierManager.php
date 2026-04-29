<?php

namespace App\Service;

use App\Entity\Supplier;

/**
 * SupplierManager handles the business rule validation for Supplier entities.
 *
 * Rules enforced:
 *  1. The supplier name is required and must be at least 3 characters.
 *  2. The email must be present and in a valid format.
 *  3. The phone number must match the Tunisian format.
 *  4. The address is required (cannot be empty).
 *  5. The status must be either 'active' or 'inactive'.
 */
class SupplierManager
{
    private const VALID_STATUSES = ['active', 'inactive'];

    /**
     * Validates a Supplier against all defined business rules.
     *
     * @throws \InvalidArgumentException when any rule is violated
     */
    public function validate(Supplier $supplier): bool
    {
        // Rule 1: Name is required and must be at least 3 characters
        $name = trim((string) $supplier->getName());
        if (empty($name)) {
            throw new \InvalidArgumentException('The supplier name cannot be empty.');
        }
        if (strlen($name) < 3) {
            throw new \InvalidArgumentException('The supplier name must be at least 3 characters long.');
        }

        // Rule 2: Email is required and must be valid
        $email = $supplier->getEmail();
        if (empty($email)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('The email "%s" is not a valid email format.', $email));
        }

        // Rule 3: Phone must match Tunisian format (8 digits, optionally prefixed with +216 or 00216)
        $phone = $supplier->getPhone();
        if (empty($phone)) {
            throw new \InvalidArgumentException('A contact phone number is required.');
        }
        if (!preg_match('/^(\+216|00216)?[234579]\d{7}$/', $phone)) {
            throw new \InvalidArgumentException(
                'Please enter a valid Tunisian phone number (8 digits, optionally starting with +216).'
            );
        }

        // Rule 4: Address is required
        $address = $supplier->getAddress();
        if (empty(trim((string) $address))) {
            throw new \InvalidArgumentException('The physical address is required.');
        }

        // Rule 5: Status must be valid
        $status = $supplier->getStatus();
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid status "%s". Allowed values are: %s.',
                $status,
                implode(', ', self::VALID_STATUSES)
            ));
        }

        return true;
    }

    /**
     * Returns whether the supplier is currently active.
     */
    public function isActive(Supplier $supplier): bool
    {
        return $supplier->getStatus() === 'active';
    }
}
