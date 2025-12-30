<?php declare(strict_types=1);

namespace DeRidderDenHertog\SetCustomer\Request;

use DeRidderDenHertog\Core\Http\Request;
use DeRidderDenHertog\SetCustomer\Type\Parameter\CustomerData;

/** @internal */
final class SetCustomer extends Request
{
    protected string $action = 'SetCustomer';

    public function __construct(private readonly CustomerData $data) {}

    protected function message(): array
    {
        return [
            'Customer' => array_filter($this->data->toMessageArray()),
        ];
    }
}
