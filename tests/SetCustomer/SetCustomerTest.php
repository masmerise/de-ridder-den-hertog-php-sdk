<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\SetCustomer;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\SetCustomer\Request\SetCustomer;
use DeRidderDenHertog\SetCustomer\Type\Parameter\CustomerData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SetCustomerTest extends TestCase
{
    #[Test]
    public function falsy_values_are_sent_and_only_null_values_are_omitted(): void
    {
        // Arrange
        $data = CustomerData::parameters(
            betaalmethode: '0',
            company: 0,
            emailPakbonnen: '',
            name: 'Jane Doe',
            pakbonAantalKopieen: 0,
        );

        $request = new SetCustomer($data)->setGuid(ApiGuid::fromString('{b28ee2a1-ef5d-46e9-a1bc-4922e0070fe0}'));

        // Act
        $customer = $request->body()->get('Customer');

        // Assert
        $this->assertSame([
            'Betaalmethode' => '0',
            'Company' => 0,
            'EmailPakbonnen' => '',
            'Name' => 'Jane Doe',
            'PakbonAantalKopieen' => 0,
        ], $customer);
    }
}
