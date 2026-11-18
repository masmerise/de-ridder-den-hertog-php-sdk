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
     * RenH types `DoNotChargeVAT` inconsistently: some environments emit a boolean, others the
     * number `0`/`1` for every single receipt. Under `strict_types=1` the latter used to throw a
     * TypeError for the whole page, because the throw happens inside the `array_map()` over it.
     */
    #[Test]
    #[Group('get-day-turnover')]
    public function maps_a_receipt_whose_do_not_charge_vat_flag_arrives_as_a_number(): void
    {
        $transaction = (new TransactionMapper())($this->receiptWithLines());

        $this->assertFalse($transaction->doNotChargeVat);
        $this->assertCount(1, $transaction->ordered);
        $this->assertFalse($transaction->ordered[0]->gewichtartikel);
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith(['0', false])]
    #[TestWith(['1', true])]
    #[TestWith(['false', false])]
    #[TestWith(['true', true])]
    public function maps_the_do_not_charge_vat_flag_whichever_type_it_arrives_as(string $flag, bool $expected): void
    {
        $transaction = (new TransactionMapper())($this->receiptWithLines($flag));

        $this->assertSame($expected, $transaction->doNotChargeVat);
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith(['0', false])]
    #[TestWith(['1', true])]
    #[TestWith(['false', false])]
    #[TestWith(['true', true])]
    public function maps_the_weighed_article_flag_of_an_item_whichever_type_it_arrives_as(string $flag, bool $expected): void
    {
        $transaction = (new TransactionMapper())($this->receiptWithLines('false', $flag));

        $this->assertSame($expected, $transaction->ordered[0]->gewichtartikel);
    }

    /** An empty receipt carries no `Ordered` and no `payform` key at all, not even an empty one. */
    #[Test]
    #[Group('get-day-turnover')]
    public function maps_a_receipt_that_carries_neither_lines_nor_payforms(): void
    {
        $transaction = (new TransactionMapper())($this->receiptWithoutLines());

        $this->assertFalse($transaction->doNotChargeVat);
        $this->assertSame('0/30', $transaction->billno);
        $this->assertSame(0.0, $transaction->total);
        $this->assertSame([], $transaction->ordered);
        $this->assertSame([], $transaction->payforms);
    }

    /**
     * Receipt 0/30 as a live RenH environment returns it: a zero-total receipt with `DoNotChargeVAT`
     * as the number `0` and neither of the two collection keys present.
     *
     * @return array<string, mixed>
     */
    private function receiptWithoutLines(): array
    {
        $json = <<<'JSON'
            {
                "Transaction": {
                    "branch": 1,
                    "register": 4,
                    "billno": "0/30",
                    "cashier": 1,
                    "custno": 0,
                    "custname": "",
                    "time": "2026-11-17T08:35:12.000",
                    "total": 0,
                    "VATNumber": "",
                    "DoNotChargeVAT": 0
                }
            }
            JSON;

        return JsonMessage::decode(base64_encode($json));
    }

    /**
     * Receipt 0/32 as a live RenH environment returns it: `DoNotChargeVAT` arrives as the number
     * `0` while `gewichtartikel` on the very same record arrives as a genuine boolean.
     *
     * @param non-empty-string $doNotChargeVat the JSON literal, so the test exercises the wire type
     * @param non-empty-string $gewichtartikel the JSON literal, so the test exercises the wire type
     *
     * @return array<string, mixed>
     */
    private function receiptWithLines(string $doNotChargeVat = '0', string $gewichtartikel = 'false'): array
    {
        $json = <<<JSON
            {
                "Transaction": {
                    "branch": 1,
                    "register": 3,
                    "billno": "0/32",
                    "cashier": 3,
                    "custno": 0,
                    "custname": "",
                    "time": "2026-11-18T12:52:46.000",
                    "total": 0.69,
                    "VATNumber": "",
                    "DoNotChargeVAT": {$doNotChargeVat},
                    "Ordered": [
                        {
                            "count": 1,
                            "gewicht": 0,
                            "plu": 1,
                            "price": 0.69,
                            "brutoverkoopwaarde": 0.69,
                            "korting": 0,
                            "verkoopwaarde": 0.69,
                            "inkoopwaarde": 0.45,
                            "scancode": "1",
                            "subgroep": 8,
                            "tax": 1,
                            "taxperc": 6,
                            "hoofdgroep": 0,
                            "afdeling": 1,
                            "omzetsoort": 0,
                            "gewichtartikel": {$gewichtartikel},
                            "text": "KIWI PIECE",
                            "actieomzet": "0"
                        }
                    ],
                    "payform": [
                        {
                            "amount": 0.7,
                            "payformid": 17,
                            "payformname": ""
                        }
                    ]
                }
            }
            JSON;

        return JsonMessage::decode(base64_encode($json));
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
