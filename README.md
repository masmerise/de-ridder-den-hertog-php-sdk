<p align="center">
<img src="https://media-01.imu.nl/storage/ridderenhertog.nl/2163/renh-kassasystemen-weegschalen.jpg" alt="RenH PHP SDK" height="100">
<img src="https://www.php.net/images/logos/php-logo.svg" alt="PHP" height="40">
</p>

<p align="center">
<a href="https://github.com/masmerise/de-ridder-den-hertog-php-sdk/actions"><img src="https://github.com/masmerise/de-ridder-den-hertog-php-sdk/actions/workflows/test.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/masmerise/de-ridder-den-hertog-php-sdk"><img src="https://img.shields.io/packagist/dt/masmerise/de-ridder-den-hertog-php-sdk" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/masmerise/de-ridder-den-hertog-php-sdk"><img src="https://img.shields.io/packagist/v/masmerise/de-ridder-den-hertog-php-sdk" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/masmerise/de-ridder-den-hertog-php-sdk"><img src="https://img.shields.io/packagist/l/masmerise/de-ridder-den-hertog-php-sdk" alt="License"></a>
</p>

## De Ridder & Den Hertog PHP SDK

This SDK provides convenient, fully-typed access to [De Ridder & Den Hertog's API](/docs/RHAPI.pdf).

## Installation

You can install the package via [composer](https://getcomposer.org):

```bash
composer require masmerise/de-ridder-den-hertog-php-sdk
```

### Adapters

You may also use one of the available adapters instead:

- [Laravel](https://github.com/masmerise/de-ridder-den-hertog-for-laravel)

## Getting started

You can always refer to the [documentation](/docs/RHAPI.pdf) to examine the various resources that are available.

### Setup

```php
use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\DeRidderDenHertog;

$guid = ApiGuid::fromString('{4844a45c-33d1-4937-83f4-366d36449eaf}');

$renh = DeRidderDenHertog::authenticate($guid);
```

### Data retrieval

```php
use DeRidderDenHertog\Core\Type\Parameter\Filter;

$filter = Filter::fromSql('Klantnummer=123456789');

[$customer] = $renh->getCustomers(filter: $filter);
```

### Resource creation / update

```php
use DeRidderDenHertog\SetCustomer\Type\Parameter\CustomerData;

$data = CustomerData::parameters(
    city: 'Ghent',
    emailAddress: 'php.sdk@github.action',
    houseNumber: '40',
    name: 'PHP SDK',
    phone: '0470123456',
    street: 'Teststraat',
    zipCode: '9000',
);

$customerId = $renh->setCustomer($data);
```



## Failures (exception handling)

The SDK uses exceptions as its medium to communicate failures.

```
DeRidderDenHertogException
├── CouldNotAuthenticate (Authentication Errors)
├── UnknownException (Connection Errors)
└── ValidationException (Request Errors)
    ├── CouldNotDeleteCustomer
    ├── CouldNotGetApiFunctions
    ├── CouldNotGetCustomers
    ├── CouldNotSetCustomer
    └── ...
```

```php
use DeRidderDenHertog\SetCustomer\Failure\CouldNotSetCustomer;

try {
    $customerId = $renh->setCustomer($data);
} catch (CouldNotSetCustomer $ex) {
    $logger->log($ex->getMessage());
}
```

## Timeouts

The SDK defaults to a 30 second timeout. 

## Implementation progress

| Action          |
|-----------------|
| DeleteCustomer  |
| GetAPIFunctions |
| GetCustomers    |
| SetCustomer     |

While the SDK is battle-tested and production-ready, only a handful of API interactions have been implemented thus far.
Please bear in mind this is an unofficial SDK, so we have to prioritize available resources at this time.

However, always feel free to submit a feature or pull request!

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email support@masmerise.be instead of using the issue tracker.

## Credits

- [Muhammed Sari](https://github.com/mabdullahsari)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
