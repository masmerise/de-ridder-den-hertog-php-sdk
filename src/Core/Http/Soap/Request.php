<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request as RequestBase;
use Saloon\Traits\Body\HasXmlBody;

/** @internal */
abstract class Request extends RequestBase implements HasBody
{
    use HasXmlBody;

    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '';
    }
}
