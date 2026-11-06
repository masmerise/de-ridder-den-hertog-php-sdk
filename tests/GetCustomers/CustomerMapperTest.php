<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\GetCustomers;

use DeRidderDenHertog\Core\Http\JsonMessage;
use DeRidderDenHertog\GetCustomers\Type\Mapping\CustomerMapper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class CustomerMapperTest extends TestCase
{
    #[Test]
    #[Group('get-customers')]
    #[TestWith(['18446744073709551615'])]
    #[TestWith(['-1'])]
    #[TestWith(['0'])]
    public function maps_a_sentinel_customer_number_to_null(string $klantnummer): void
    {
        $customer = (new CustomerMapper())($this->record($klantnummer));

        $this->assertNull($customer->klantnummer);
        $this->assertSame('Nese Tekin', $customer->naam1);
    }

    #[Test]
    #[Group('get-customers')]
    public function maps_an_ordinary_customer_number_to_an_integer(): void
    {
        $customer = (new CustomerMapper())($this->record('521820160'));

        $this->assertSame(521820160, $customer->klantnummer);
    }

    #[Test]
    #[Group('get-customers')]
    public function maps_a_missing_customer_number_to_null(): void
    {
        $customer = (new CustomerMapper())(['Naam1' => 'Nese Tekin']);

        $this->assertNull($customer->klantnummer);
    }

    /** @return array<string, mixed> */
    private function record(string $klantnummer): array
    {
        $json = <<<JSON
            {
                "TblKlantenID": 91,
                "Klantnummer": {$klantnummer},
                "Naam1": "Nese Tekin",
                "Plaats": "Antwerpen",
                "IsWerknemer": false
            }
            JSON;

        return JsonMessage::decode(base64_encode($json));
    }
}
