<?php declare(strict_types=1);

namespace DeRidderDenHertog\Environments;

/** @internal */
enum Environment
{
    case Production;
    case Test;

    public function url(): string
    {
        return match ($this) {
            self::Test => 'https://test.renh.online/rhapi_web/awws/rhapi.awws',
            default => 'https://renh.online/RHAPI_WEB/awws/RHAPI.awws',
        };
    }
}
