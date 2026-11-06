<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\GetDayTurnover;

use DeRidderDenHertog\Core\Http\JsonMessage;
use DeRidderDenHertog\GetDayTurnover\Type\Mapping\TransactionMapper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class TransactionMapperTest extends TestCase
{
    /**
     * `-1` reaches us as the unsigned 64-bit literal 2**64 - 1, which is above PHP_INT_MAX and so
     * arrives widened to a float. Asserted here because the whole fix hangs on that premise.
     */
    #[Test]
    #[Group('get-day-turnover')]
    public function decodes_the_unsigned_sentinel_customer_number_as_a_float(): void
    {
        $record = $this->record('18446744073709551615');

        $this->assertIsFloat($record['Transaction']['custno']);
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function maps_the_unsigned_sentinel_customer_number_to_null(): void
    {
        $transaction = (new TransactionMapper())($this->record('18446744073709551615'));

        $this->assertNull($transaction->custno);
    }

    /** The name is the only customer information these receipts carry, so it has to survive. */
    #[Test]
    #[Group('get-day-turnover')]
    public function keeps_the_customer_name_of_a_named_but_unregistered_customer(): void
    {
        $transaction = (new TransactionMapper())($this->record('18446744073709551615'));

        $this->assertSame('raziye', $transaction->custname);
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith(['0'])]
    #[TestWith(['-1'])]
    public function maps_a_sentinel_customer_number_to_null(string $custno): void
    {
        $transaction = (new TransactionMapper())($this->record($custno));

        $this->assertNull($transaction->custno);
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith(['4711', 4711])]
    #[TestWith(['521820160', 521820160])]
    public function maps_an_ordinary_customer_number_to_an_integer(string $custno, int $expected): void
    {
        $transaction = (new TransactionMapper())($this->record($custno));

        $this->assertSame($expected, $transaction->custno);
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function maps_the_rest_of_the_record_alongside_the_sentinel(): void
    {
        $transaction = (new TransactionMapper())($this->record('18446744073709551615'));

        $this->assertSame(2, $transaction->branch);
        $this->assertSame(4, $transaction->register);
        $this->assertSame('0/184619', $transaction->billno);
        $this->assertSame(18, $transaction->cashier);
        $this->assertSame('2026-11-03 09:38:25', $transaction->time->format('Y-m-d H:i:s'));
        $this->assertSame('Europe/Amsterdam', $transaction->time->getTimezone()->getName());
        $this->assertSame(14.17, $transaction->total);
        $this->assertNull($transaction->vatNumber);
        $this->assertFalse($transaction->doNotChargeVat);

        $this->assertCount(2, $transaction->ordered);
        $this->assertSame(1234, $transaction->ordered[0]->plu);
        $this->assertSame('Lamsvlees', $transaction->ordered[0]->text);
        $this->assertSame(4.5, $transaction->ordered[0]->price);
        $this->assertSame(0.738, $transaction->ordered[1]->gewicht);
        $this->assertTrue($transaction->ordered[1]->gewichtartikel);

        $this->assertCount(2, $transaction->payforms);
        $this->assertSame(1, $transaction->payforms[0]->id);
        $this->assertSame('Contant', $transaction->payforms[0]->name);
        $this->assertSame(10.0, $transaction->payforms[0]->amount);
        $this->assertSame(4.17, $transaction->payforms[1]->amount);
    }

    /**
     * Receipt 0/184619 as RenH returns it on the wire, decoded through the real transport so the
     * test sees whatever json_decode() actually makes of the customer number literal.
     *
     * @return array<string, mixed>
     */
    private function record(string $custno): array
    {
        $json = <<<JSON
            {
                "Transaction": {
                    "branch": 2,
                    "register": 4,
                    "billno": "0/184619",
                    "cashier": 18,
                    "custno": {$custno},
                    "custname": "raziye",
                    "time": "2026-11-03T09:38:25.000",
                    "total": 14.17,
                    "VATNumber": "",
                    "DoNotChargeVAT": false,
                    "Ordered": [
                        {
                            "count": 1,
                            "gewicht": 0.0,
                            "plu": 1234,
                            "price": 4.5,
                            "brutoverkoopwaarde": 4.5,
                            "korting": 0.0,
                            "verkoopwaarde": 4.5,
                            "inkoopwaarde": 2.1,
                            "scancode": "2000123",
                            "subgroep": 12,
                            "tax": 1,
                            "taxperc": 6,
                            "hoofdgroep": 3,
                            "afdeling": 1,
                            "omzetsoort": 0,
                            "gewichtartikel": false,
                            "text": "Lamsvlees",
                            "actieomzet": "0"
                        },
                        {
                            "count": 1,
                            "gewicht": 0.738,
                            "plu": 5678,
                            "price": 13.1,
                            "brutoverkoopwaarde": 9.67,
                            "korting": 0.0,
                            "verkoopwaarde": 9.67,
                            "inkoopwaarde": 5.4,
                            "scancode": "2000456",
                            "subgroep": 14,
                            "tax": 1,
                            "taxperc": 6,
                            "hoofdgroep": 3,
                            "afdeling": 1,
                            "omzetsoort": 0,
                            "gewichtartikel": true,
                            "text": "Sucuk",
                            "actieomzet": "0"
                        }
                    ],
                    "payform": [
                        {
                            "amount": 10.0,
                            "payformid": 1,
                            "payformname": "Contant"
                        },
                        {
                            "amount": 4.17,
                            "payformid": 2,
                            "payformname": "Bancontact"
                        }
                    ]
                }
            }
            JSON;

        return JsonMessage::decode(base64_encode($json));
    }
}
