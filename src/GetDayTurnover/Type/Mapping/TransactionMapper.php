<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Type\Mapping;

use DateTimeImmutable;
use DateTimeZone;
use DeRidderDenHertog\Core\Type\Mapping\CustomerNumber;
use DeRidderDenHertog\GetDayTurnover\Type\Item;
use DeRidderDenHertog\GetDayTurnover\Type\PayForm;
use DeRidderDenHertog\GetDayTurnover\Type\Transaction;

/** @internal */
final readonly class TransactionMapper
{
    /**
     * @param array{
     *     Transaction: array{
     *         branch: int,
     *         register: int,
     *         billno: string,
     *         cashier: int,
     *         custno: int|float,
     *         custname: string,
     *         time: string,
     *         total: float,
     *         VATNumber: string,
     *         DoNotChargeVAT: bool|int,
     *         Ordered?: array{
     *             count: int,
     *             gewicht: float,
     *             plu: int,
     *             price: float,
     *             brutoverkoopwaarde: float,
     *             korting: float,
     *             verkoopwaarde: float,
     *             inkoopwaarde: float,
     *             scancode: string,
     *             subgroep: int,
     *             tax: int,
     *             taxperc: int,
     *             hoofdgroep: int,
     *             afdeling: int,
     *             omzetsoort: int,
     *             gewichtartikel: bool|int,
     *             text: string,
     *             actieomzet: string
     *         }[],
     *         payform?: array{
     *             amount: float,
     *             payformid: int,
     *             payformname: string
     *         }[]
     *     }
     * } $transaction
     */
    public function __invoke(array $transaction): Transaction
    {
        $transaction = $transaction['Transaction'];

        return new Transaction(
            branch: $transaction['branch'],
            register: $transaction['register'],
            billno: $transaction['billno'],
            cashier: $transaction['cashier'],
            custno: CustomerNumber::fromMessageValue($transaction['custno']),
            custname: $transaction['custname'] ?: null,
            time: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', $transaction['time'], new DateTimeZone('Europe/Amsterdam')),
            total: $transaction['total'],
            vatNumber: $transaction['VATNumber'] ?: null,
            doNotChargeVat: $this->toBool($transaction['DoNotChargeVAT']),
            ordered: array_map($this->toItem(...), $transaction['Ordered'] ?? []),
            payforms: array_map($this->toPayForm(...), $transaction['payform'] ?? []),
        );
    }

    /**
     * @param array{
     *     count: int,
     *     gewicht: float,
     *     plu: int,
     *     price: float,
     *     brutoverkoopwaarde: float,
     *     korting: float,
     *     verkoopwaarde: float,
     *     inkoopwaarde: float,
     *     scancode: string,
     *     subgroep: int,
     *     tax: int,
     *     taxperc: int,
     *     hoofdgroep: int,
     *     afdeling: int,
     *     omzetsoort: int,
     *     gewichtartikel: bool|int,
     *     text: string,
     *     actieomzet: string
     * } $item
     */
    private function toItem(array $item): Item
    {
        return new Item(
            count: $item['count'],
            gewicht: $item['gewicht'],
            plu: $item['plu'],
            price: $item['price'],
            brutoverkoopwaarde: $item['brutoverkoopwaarde'],
            korting: $item['korting'],
            verkoopwaarde: $item['verkoopwaarde'],
            inkoopwaarde: $item['inkoopwaarde'],
            scancode: $item['scancode'],
            subgroep: $item['subgroep'],
            tax: $item['tax'],
            taxperc: $item['taxperc'],
            hoofdgroep: $item['hoofdgroep'],
            afdeling: $item['afdeling'],
            omzetsoort: $item['omzetsoort'],
            gewichtartikel: $this->toBool($item['gewichtartikel']),
            text: $item['text'],
            actieomzet: $item['actieomzet'],
        );
    }

    /**
     * RenH types its flags inconsistently across environments: one emits a boolean, another the
     * number `0`/`1` for the very same field. `filter_var()` rather than a cast, because a cast
     * makes `"0"` and `"false"` truthy.
     */
    private function toBool(bool|int|string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * @param array{
     *     amount: float,
     *     payformid: int,
     *     payformname: string
     * } $form
     */
    private function toPayForm(array $form): PayForm
    {
        return new PayForm(
            amount: $form['amount'],
            id: $form['payformid'],
            name: $form['payformname'],
        );
    }
}
