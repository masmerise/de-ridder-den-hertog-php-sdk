<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetApiFunctions\Request;

use DeRidderDenHertog\Core\Http\Request;

/** @internal */
final class GetApiFunctions extends Request
{
    protected string $action = 'GetAPIFunctions';
}
