# Upmind Provision Providers - Website Builders

[![Latest Version on Packagist](https://img.shields.io/packagist/v/upmind/provision-provider-website-builders.svg?style=flat-square)](https://packagist.org/packages/upmind/provision-provider-website-builders)

This provision category contains the common functions used in provisioning flows for website builders and related tools.

- [Installation](#installation)
- [Usage](#usage)
  - [Quick-start](#quick-start)
- [Supported Providers](#supported-providers)
- [Functions](#functions)
  - [create()](#create)
  - [getInfo()](#getInfo)
  - [login()](#login)
  - [changePackage()](#changePackage)
  - [suspend()](#suspend)
  - [unSuspend()](#unSuspend)
  - [terminate()](#terminate)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)
- [Upmind](#upmind)

## Installation

```bash
composer require upmind/provision-provider-website-builders
```

## Usage

This library makes use of [upmind/provision-provider-base](https://packagist.org/packages/upmind/provision-provider-base) primitives which we suggest you familiarize yourself with by reading the usage section in the README.

### Quick-start

See the below example to create a cPanel account:

```php
<?php

use Illuminate\Support\Facades\App;
use Upmind\ProvisionBase\ProviderFactory;

$configuration = [
    'api_url' => 'https://rest.bk-partnersus.com/',
    'username' => '{username}',
    'password' => '{password}',
    'brand_ref' => '{brand ref}',
    'suspension_package_ref' => '{suspension package ref}',
];

$factory = App::make(ProviderFactory::class);
$provider = $factory->create('website-builders', 'base-kit', $configuration);

$createParameters = [
    'customer_id' => '1234',
    'customer_name' => 'Harry L',
    'customer_email' => 'harry@upmind.com',
    'domain_name' => 'example.com',
    'package_reference' => '2345',
    'billing_cycle_months' => 12, // annual billing cycle
];
$function = $provider->makeJob('create', $createParameters);

$createResult = $function->execute();

if ($createResult->isError()) {
    throw new RuntimeException($createResult->getMessage(), 0, $createResult->getException());
}

/** @var \Upmind\ProvisionProviders\WebsiteBuilders\Data\AccountInfo */
$accountInfo = $createResult->getData();

// $accountInfo->account_reference; // username/identifier of the created site builder account
// ...
```

## Supported Providers

The following providers are currently implemented:
  - BaseKit

## Functions

### create()

Creates a site builder account and returns the `account_identifier` which can be used to identify the account in subsequent requests, and other account information.

### getInfo()

Gets information about a site builder account such as the main domain name, whether or not it is suspended, usage data etc.

### login()

Obtains a signed URL which a user can be redirected to which automatically logs the customer into their account.

### changePackage()

Update the product/package a site builder account is set to.

### suspend()

Suspends services for a site builder account.

### unSuspend()

Un-suspends services for a site builder account.

### terminate()

Completely delete a site builder account.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

 - [Harry Lewis](https://github.com/uphlewis)
 - [All Contributors](../../contributors)

## License

GNU General Public License version 3 (GPLv3). Please see [License File](LICENSE.md) for more information.

## Upmind

Sell, manage and support web hosting, domain names, ssl certificates, website builders and more with [Upmind.com](https://upmind.com/start) - the ultimate web hosting billing and management solution.