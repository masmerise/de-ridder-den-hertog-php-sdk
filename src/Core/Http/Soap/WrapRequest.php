<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use Saloon\Http\PendingRequest;
use Saloon\Repositories\Body\StringBodyRepository;

final readonly class WrapRequest
{
    public function __construct(private array $request) {}

    public function __invoke(PendingRequest $pendingRequest): void
    {
        $envelope = Envelope::wrap($this->request);

        $pendingRequest->setBody(new StringBodyRepository($envelope));
    }
}
