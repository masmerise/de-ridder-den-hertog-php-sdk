<?php declare(strict_types=1);

namespace DeRidderDenHertog\DeleteCustomer\Request;

use DeRidderDenHertog\Core\Http\Request;
use DeRidderDenHertog\Core\Type\Primitive\CustomerId;

/** @internal */
final class DeleteCustomer extends Request
{
    protected string $action = 'DeleteCustomer';

    public function __construct(private readonly CustomerId $id) {}

    protected function message(): array
    {
        return ['Customer' => $this->id->toMessageArray()];
    }
}
