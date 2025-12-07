<?php declare(strict_types=1);

namespace DeRidderDenHertog\SetCustomer\Type\Parameter;

final readonly class CustomerData
{
    private function __construct(
        public ?string $betaalmethode,
        public ?string $city,
        public ?int $company,
        public ?string $customerId,
        public ?string $emailAddress,
        public ?string $emailPakbonnen,
        public ?string $geboortedatum,
        public ?string $houseNumber,
        public ?string $houseNumberAddition,
        public ?int $klantViaKioskNogNaarKassa,
        public ?int $korting,
        public ?string $landCodeIso2,
        public ?string $name,
        public ?int $pakbonAantalKopieen,
        public ?string $phone,
        public ?int $prijslijn,
        public ?string $street,
        public ?string $vatNumber,
        public ?string $zipCode,
    ) {}

    public static function parameters(
        ?string $betaalmethode = null,
        ?string $city = null,
        ?int $company = null,
        ?string $customerId = null,
        ?string $emailAddress = null,
        ?string $emailPakbonnen = null,
        ?string $geboortedatum = null,
        ?string $houseNumber = null,
        ?string $houseNumberAddition = null,
        ?int $klantViaKioskNogNaarKassa = null,
        ?int $korting = null,
        ?string $landCodeIso2 = null,
        ?string $name = null,
        ?int $pakbonAantalKopieen = null,
        ?string $phone = null,
        ?int $prijslijn = null,
        ?string $street = null,
        ?string $vatNumber = null,
        ?string $zipCode = null,
    ): self {
        return new self(
            betaalmethode: $betaalmethode,
            city: $city,
            company: $company,
            customerId: $customerId,
            emailAddress: $emailAddress,
            emailPakbonnen: $emailPakbonnen,
            geboortedatum: $geboortedatum,
            houseNumber: $houseNumber,
            houseNumberAddition: $houseNumberAddition,
            klantViaKioskNogNaarKassa: $klantViaKioskNogNaarKassa,
            korting: $korting,
            landCodeIso2: $landCodeIso2,
            name: $name,
            pakbonAantalKopieen: $pakbonAantalKopieen,
            phone: $phone,
            prijslijn: $prijslijn,
            street: $street,
            vatNumber: $vatNumber,
            zipCode: $zipCode,
        );
    }

    public function toMessageArray(): array
    {
        return array_filter([
            'Betaalmethode' => $this->betaalmethode,
            'City' => $this->city,
            'Company' => $this->company,
            'CustomerID' => $this->customerId,
            'Emailaddress' => $this->emailAddress,
            'EmailPakbonnen' => $this->emailPakbonnen,
            'Geboortedatum' => $this->geboortedatum,
            'HouseNumber' => $this->houseNumber,
            'HouseNumberAddition' => $this->houseNumberAddition,
            'KlantViaKioskNogNaarKassa' => $this->klantViaKioskNogNaarKassa,
            'Korting' => $this->korting,
            'LandCodeISO2' => $this->landCodeIso2,
            'Name' => $this->name,
            'PakbonAantalKopieen' => $this->pakbonAantalKopieen,
            'Phone' => $this->phone,
            'Prijslijn' => $this->prijslijn,
            'Street' => $this->street,
            'VatNumber' => $this->vatNumber,
            'ZipCode' => $this->zipCode,
        ]);
    }
}
