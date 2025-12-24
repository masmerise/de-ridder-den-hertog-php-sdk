<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Type\Mapping;

use DateTimeImmutable;
use DateTimeZone;
use DeRidderDenHertog\GetDayTurnover\Type\Item;
use DeRidderDenHertog\GetDayTurnover\Type\PayForm;
use DeRidderDenHertog\GetDayTurnover\Type\Transaction;

/** @internal */
trait MapsTransactions
{
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
     *     gewichtartikel: bool,
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
            gewichtartikel: $item['gewichtartikel'],
            text: $item['text'],
            actieomzet: $item['actieomzet'],
        );
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

    /**
     * @param array{
     *     Transaction: array{
     *         branch: int,
     *         register: int,
     *         billno: string,
     *         cashier: int,
     *         custno: int,
     *         custname: string,
     *         time: string,
     *         total: float,
     *         VATNumber: string,
     *         DoNotChargeVAT: bool,
     *         Ordered: array{
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
     *             gewichtartikel: bool,
     *             text: string,
     *             actieomzet: string
     *         }[],
     *         payform: array{
     *             amount: float,
     *             payformid: int,
     *             payformname: string
     *         }[]
     *     }
     * } $transaction
     */
    private function toTransaction(array $transaction): Transaction
    {
        $transaction = $transaction['Transaction'];

        return new Transaction(
            branch: $transaction['branch'],
            register: $transaction['register'],
            billno: $transaction['billno'],
            cashier: $transaction['cashier'],
            custno: $transaction['custno'] === 0 ? null : $transaction['custno'],
            custname: $transaction['custname'] ?: null,
            time: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', $transaction['time'], new DateTimeZone('Europe/Amsterdam')),
            total: $transaction['total'],
            vatNumber: $transaction['VATNumber'] ?: null,
            doNotChargeVat: $transaction['DoNotChargeVAT'],
            ordered: array_map($this->toItem(...), $transaction['Ordered'] ?? []),
            payforms: array_map($this->toPayForm(...), $transaction['payform'] ?? []),
        );
    }
}