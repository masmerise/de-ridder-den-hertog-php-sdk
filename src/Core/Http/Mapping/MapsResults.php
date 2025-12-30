<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Mapping;

use DeRidderDenHertog\Core\Http\Result;

/** @internal */
<<<<<<<< HEAD:src/Core/Http/Mapping/ResponseMapper.php
final readonly class ResponseMapper
========
trait MapsResults
>>>>>>>> f4b6137 (wip):src/Core/Http/Mapping/MapsResults.php
{
    /**
     * @param array{
     *     Status: 'Ok'|'Not Ok',
     *     Answer: string,
     *     ErrorString: string,
     *     Records?: array,
     * } $response
     */
<<<<<<<< HEAD:src/Core/Http/Mapping/ResponseMapper.php
    public function __invoke(array $response): Response
========
    private function toResult(array $response): Result
>>>>>>>> f4b6137 (wip):src/Core/Http/Mapping/MapsResults.php
    {
        return new Result(
            ok: $response['Status'] === 'Ok',
            answer: $response['Answer'],
            error: $response['ErrorString'],
            records: $response['Records'] ?? [],
            raw: $response,
        );
    }
}
