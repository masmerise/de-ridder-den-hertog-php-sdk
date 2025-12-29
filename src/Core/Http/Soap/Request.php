<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use DeRidderDenHertog\Authentication\ApiGuid;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request as RequestBase;
use Saloon\Traits\Body\HasXmlBody;

/** @internal */
abstract class Request extends RequestBase implements HasBody
{
    use HasXmlBody;

    protected string $action = '';

    protected ApiGuid $guid;

    protected Method $method = Method::POST;

    protected array $message = [];

    protected function message(): array
    {
        return [];
    }

    protected function defaultBody(): string
    {
        $this->message = [
            'APIGuid' => $this->guid->toMessageString(),
            'Action' => $this->action,
            ...$this->message(),
        ];

        return Envelope::wrap(
            array_filter($this->message)
        );
    }

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function setGuid(ApiGuid $guid): static
    {
        $this->guid = $guid;

        return $this;
    }
}
