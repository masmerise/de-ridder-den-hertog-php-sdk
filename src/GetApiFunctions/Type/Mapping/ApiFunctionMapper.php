<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetApiFunctions\Type\Mapping;

use DeRidderDenHertog\GetApiFunctions\Type\ApiFunction;

/** @internal */
final readonly class ApiFunctionMapper
{
    /**
     * @param array{
     *     Function: string,
     *     Description: string,
     *     Remarks: string,
     *     JSONExample: string,
     * } $apiFunction
     */
    public function __invoke(array $apiFunction): ApiFunction
    {
        return new ApiFunction(
            description: $apiFunction['Description'],
            function: $apiFunction['Function'],
            remarks: $apiFunction['Remarks'],
            example: $apiFunction['JSONExample'],
        );
    }
}
