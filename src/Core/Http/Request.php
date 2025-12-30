<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Core\Http\Mapping\MapsResults;
use DeRidderDenHertog\Core\Http\Soap\UnwrapResponse;
use DeRidderDenHertog\Core\Http\Soap\WrapRequest;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request as RequestBase;
use Saloon\Http\Response;
use Saloon\Repositories\Body\JsonBodyRepository;

/** @internal */
abstract class Request extends RequestBase
{
    use MapsResults;

    protected string $action = '';

    protected JsonBodyRepository $body;

    protected ApiGuid $guid;

    protected Method $method = Method::POST;

    public function body(): JsonBodyRepository
    {
        return $this->body ??= new JsonBodyRepository($this->request());
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->middleware()->onRequest(new WrapRequest($this->body()->all()));
    }

    public function createDtoFromResponse(Response $response): Result
    {
        return $response |> new UnwrapResponse() |> $this->toResult(...);
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

    protected function request(): array
    {
        return array_filter([
            'APIGuid' => $this->guid->toMessageString(),
            'Action' => $this->action,
            ...$this->message(),
        ]);
    }

    protected function message(): array
    {
        return [];
    }
}
