<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetApiFunctions\Type\Mapping;

use DeRidderDenHertog\GetApiFunctions\Type\ApiFunction;

/** @internal */
trait MapsApiFunctions
{
    /**
     * @param array{
     *     Function: string,
     *     Description: string,
     *     Remarks: string,
     *     JSONExample: string,
     * } $apiFunction
     */
    private function toApiFunction(array $apiFunction): ApiFunction
    {
        return new ApiFunction(
            description: $apiFunction['Description'],
            function: $apiFunction['Function'],
            remarks: $apiFunction['Remarks'],
            example: $apiFunction['JSONExample'],
        );
    }
}
