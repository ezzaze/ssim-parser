
# IATA SSIM schedules parser

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ezzaze/ssim-parser.svg?style=flat-square)](https://packagist.org/packages/ezzaze/ssim-parser)
[![Tests](https://github.com/ezzaze/ssim-parser/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/ezzaze/ssim-parser/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/ezzaze/ssim-parser.svg?style=flat-square)](https://packagist.org/packages/ezzaze/ssim-parser)

This package allows the developers working with airline companies to extract the flight schedule directly off the Standard Schedules Information (SSIM) from IATA.

## Requirements

- PHP 8.1 or higher

## Installation

You can install the package via composer:

```bash
composer require ezzaze/ssim-parser
```

## Usage

```php
use Ezzaze\SsimParser\SsimParser;

// From a raw SSIM string
$parser = new SsimParser();
$schedule = $parser->load($ssimData)->parse();

// From a file
$schedule = (new SsimParser())->load('/path/to/schedule.ssim')->parse();
```

Each flight in the returned array contains:

```php
var_dump($schedule[0]);
/*
    array:12 [
        "uid" => "30330703070000501"
        "airline_designator" => "ME"
        "service_type" => "J"
        "flight_number" => "501"
        "departure_datetime" => "2022-07-03 07:00:00"
        "arrival_datetime" => "2022-07-03 08:40:00"
        "departure_utc_datetime" => "2022-07-03 03:00:00"
        "arrival_utc_datetime" => "2022-07-03 06:40:00"
        "departure_iata" => "EVN"
        "arrival_iata" => "HRG"
        "aircraft_type" => "320"
        "aircraft_configuration" => "Y174"
    ]
*/
```

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Marwane Ezzaze](https://github.com/ezzaze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
