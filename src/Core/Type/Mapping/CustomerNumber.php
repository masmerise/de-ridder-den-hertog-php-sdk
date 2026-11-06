<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Type\Mapping;

/** @internal */
final readonly class CustomerNumber
{
    /**
     * Normalises a customer number as RenH reports it.
     *
     * RenH writes `0` or `-1` when a record carries no customer number, and `-1` goes over the
     * wire as the unsigned 64-bit literal `18446744073709551615` (2**64 - 1). That literal is
     * above PHP_INT_MAX, so json_decode() widens it to the float 1.8446744073709552E+19 and the
     * exact value is gone before any mapper sees it.
     *
     * A customer number is always positive (see CustomerId) and always fits in a PHP integer, so
     * a value that is not a positive integer is an absent customer number rather than one we
     * failed to read, and is reported as null.
     */
    public static function fromMessageValue(int|float|null $value): ?int
    {
        if (! is_int($value)) {
            return null;
        }

        return $value > 0 ? $value : null;
    }
}
