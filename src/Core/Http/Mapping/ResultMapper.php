<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Mapping;

use DeRidderDenHertog\Core\Http\Result;

final readonly class ResultMapper
{
    /**
     * @param array{
     *     Status: 'Ok'|'Not Ok',
     *     Answer: string,
     *     ErrorString: string,
     *     Records?: array,
     * } $result
     */
    public function __invoke(array $result): Result
    {
        return new Result(
            ok: $result['Status'] === 'Ok',
            answer: $result['Answer'],
            error: $result['ErrorString'],
            records: $result['Records'] ?? [],
            raw: $result,
        );
    }
}
