<?php declare(strict_types=1);

namespace DeRidderDenHertog\Authentication\Failure;

use DeRidderDenHertog\Core\Failure\DeRidderDenHertogException;

final class CouldNotAuthenticate extends DeRidderDenHertogException
{
    private const string MESSAGE = 'Database GUID is not valid!';

    public static function isSatisfiedBy(string $answer): bool
    {
        return str_starts_with($answer, self::MESSAGE);
    }

    public static function becauseTheDatabaseGuidIsNotValid(): self
    {
        return new self(self::MESSAGE);
    }
}
