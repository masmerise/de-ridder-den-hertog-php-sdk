<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests;

use DateTimeImmutable;
use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Authentication\Failure\CouldNotAuthenticate;
use DeRidderDenHertog\Core\Type\Parameter\Date;
use DeRidderDenHertog\Core\Type\Parameter\Filter;
use DeRidderDenHertog\Core\Type\Primitive\CustomerId;
use DeRidderDenHertog\DeRidderDenHertog;
use DeRidderDenHertog\GetApiFunctions\Type\ApiFunction;
use DeRidderDenHertog\GetCustomers\Type\Customer;
use DeRidderDenHertog\GetCustomers\Type\Parameter\Field;
use DeRidderDenHertog\GetCustomers\Type\Parameter\Fields;
use DeRidderDenHertog\GetDayTurnover\Type\Transaction;
use DeRidderDenHertog\SetCustomer\Failure\CouldNotSetCustomer;
use DeRidderDenHertog\SetCustomer\Type\Parameter\CustomerData;
use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DeRidderDenHertogTest extends TestCase
{
    private DeRidderDenHertog $renh;

    protected function setUp(): void
    {
        $env = Dotenv::createImmutable(__DIR__);
        $env->load();

        $this->renh = $this->newInstance($_ENV['API_GUID']);
    }

    #[Group('authentication')]
    #[Test]
    public function failed_authentication(): void
    {
        // Assert
        $this->expectException(CouldNotAuthenticate::class);
        $this->expectExceptionMessage('Database GUID is not valid!');

        // Arrange
        $renh = $this->newInstance('{b28ee2a1-ef5d-46e9-a1bc-4922e0070fe0}');

        // Act
        $renh->getApiFunctions();
    }

    #[Group('failure')]
    #[Test]
    public function api_errors(): void
    {
        // Assert
        $this->expectException(CouldNotSetCustomer::class);
        $this->expectExceptionMessage('JSON does not contain Customer.Zipcode');

        // Arrange
        $data = CustomerData::parameters(name: 'Incomplete Data');

        // Act
        $this->renh->setCustomer($data);
    }

    #[Group('delete-customer')]
    #[Group('effect')]
    #[Test]
    public function delete_customer(): void
    {
        // Arrange
        $customerId = (int) $_ENV['CUSTOMER_ID'];
        $customerId = CustomerId::fromInteger($customerId);

        // Act
        $result = $this->renh->deleteCustomer($customerId);

        // Assert
        $this->assertTrue($result);
    }

    #[Group('get-api-functions')]
    #[Test]
    public function get_api_functions(): void
    {
        // Act
        $apiFunctions = $this->renh->getApiFunctions();

        // Assert
        $this->assertNotEmpty($apiFunctions);
        $this->assertInstanceof(ApiFunction::class, array_first($apiFunctions));
    }

    #[Group('get-customers')]
    #[Test]
    public function get_customers(): void
    {
        // Act
        $customers = $this->renh->getCustomers();

        // Assert
        $this->assertNotEmpty($customers);
        $this->assertInstanceof(Customer::class, array_first($customers));
    }

    #[Group('get-customers')]
    #[Test]
    public function get_customers_with_fields(): void
    {
        // Arrange
        $fields = Fields::including(Field::Naam2);

        // Act
        $customers = $this->renh->getCustomers(
            fields: $fields,
        );

        // Assert
        $this->assertNotEmpty($customers);
        $this->assertInstanceof(Customer::class, $customer = array_first($customers));
        $this->assertNull($customer->tblKlantenID);
        $this->assertNull($customer->klantnummer);
        $this->assertNull($customer->naam1);
        $this->assertNotNull($customer->naam2);
        $this->assertNull($customer->naam3);
        $this->assertNull($customer->adres);
        $this->assertNull($customer->pc);
        $this->assertNull($customer->plaats);
        $this->assertNull($customer->isWerknemer);
        $this->assertNull($customer->factuurBoekenOpWinkel);
        $this->assertNull($customer->debiteurnrBoekhouding);
        $this->assertNull($customer->emailFacturatie);
        $this->assertNull($customer->korting);
        $this->assertNull($customer->btwnummer);
        $this->assertNull($customer->soort);
        $this->assertNull($customer->prijslijn);
        $this->assertNull($customer->pakbonAantalKopieen);
        $this->assertNull($customer->pakbonZonderPrijzen);
        $this->assertNull($customer->pakbonStreepHandtekening);
        $this->assertNull($customer->laatsteWijziging);
        $this->assertNull($customer->tblMettlerKortingenKode);
        $this->assertNull($customer->landCodeISO2);
        $this->assertNull($customer->routenr);
        $this->assertNull($customer->frequentieFactureren);
        $this->assertNull($customer->emballagefactureren);
        $this->assertNull($customer->emailPakbonnen);
        $this->assertNull($customer->tblKlantGrpID);
        $this->assertNull($customer->kaartnr);
        $this->assertNull($customer->geboortedatum);
        $this->assertNull($customer->standaardFactuurTekst);
        $this->assertNull($customer->bezorgAdres);
        $this->assertNull($customer->bezorgPC);
        $this->assertNull($customer->bezorgPlaats);
        $this->assertNull($customer->incassoTekst);
        $this->assertNull($customer->terattentievanvoordefactuur);
        $this->assertNull($customer->geenBTWBerekenen);
        $this->assertNull($customer->magNietMeerOpRekeningKopen);
        $this->assertNull($customer->bestellingen1);
        $this->assertNull($customer->bestellingen2);
        $this->assertNull($customer->bestellingen3);
        $this->assertNull($customer->bestellingen4);
        $this->assertNull($customer->bestellingen5);
        $this->assertNull($customer->bestellingen6);
        $this->assertNull($customer->bestellingen7);
        $this->assertNull($customer->klantViaKioskNogNaarKassa);
        $this->assertNull($customer->klantWilOokUBLBestand);
    }

    #[Group('get-customers')]
    #[Test]
    public function get_customers_with_filter(): void
    {
        // Arrange
        $customerId = (int) $_ENV['CUSTOMER_ID'];

        $filter = Filter::fromSql("Klantnummer={$customerId}");

        // Act
        $customers = $this->renh->getCustomers(
            filter: $filter,
        );

        // Assert
        $this->assertCount(1, $customers);
        $this->assertInstanceof(Customer::class, $customer = array_first($customers));
        $this->assertSame($customerId, $customer->klantnummer);
    }

    #[Group('get-customers')]
    #[Test]
    public function get_customers_with_from_date(): void
    {
        // Arrange
        $from = Date::fromDateTime(
            /**
             * Purposefully sending a future date so the result set is empty for sure.
             * Using a date in the past seems to do nothing, so it might be a temporary bug.
             * Since we only need to validate the date actually affects system behavior, this is fine.
             */
            new DateTimeImmutable()->modify('+1 week')
        );

        // Act
        $customers = $this->renh->getCustomers(
            from: $from,
        );

        // Assert
        $this->assertEmpty($customers);
    }

    #[Group('get-day-turnover')]
    #[Test]
    public function get_day_turnover(): void
    {
        // Arrange
        $from = Date::fromDateTime(new DateTimeImmutable());
        $till = $from;

        // Act
        $transactions = $this->renh->getDayTurnover(from: $from, till: $till);

        // Assert
        $this->assertNotEmpty($transactions);
        $this->assertInstanceof(Transaction::class, array_first($transactions));
    }

    #[Group('set-customer')]
    #[Group('effect')]
    #[Test]
    public function set_customer_private(): void
    {
        // Arrange
        $data = CustomerData::parameters(
            city: 'Gent',
            emailAddress: 'php.sdk@github.action',
            houseNumber: '40',
            name: 'PHP SDK Set Customer Test',
            phone: '0470123456',
            street: 'Teststraat',
            zipCode: '9000',
        );

        // Act
        $customerId = $this->renh->setCustomer($data);

        // Assert
        $this->assertStringStartsWith('5218201', (string) $customerId->toInteger());
    }

    #[Group('set-customer')]
    #[Group('effect')]
    #[Test]
    public function set_customer_professional(): void
    {
        // Arrange
        $data = CustomerData::parameters(
            city: 'Gent',
            emailAddress: 'php.sdk@github.action',
            houseNumber: '40',
            name: 'PHP SDK Set Customer Test',
            phone: '0470123456',
            street: 'Teststraat',
            vatNumber: 'BE123456789',
            zipCode: '9000',
        );

        // Act
        $customerId = $this->renh->setCustomer($data);

        // Assert
        $this->assertStringStartsWith('5218201', (string) $customerId->toInteger());
    }

    protected function newInstance(string $guid): DeRidderDenHertog
    {
        return DeRidderDenHertog::authenticate(
            ApiGuid::fromString($guid)
        );
    }
}
