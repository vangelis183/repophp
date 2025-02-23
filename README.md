# RepoPHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vangelis/repophp.svg?style=flat-square)](https://packagist.org/packages/vangelis/repophp)
[![Tests](https://img.shields.io/github/actions/workflow/status/vangelis/repophp/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/vangelis183/repophp/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/vangelis/repophp.svg?style=flat-square)](https://packagist.org/packages/vangelis/repophp)

RepoPHP is a PHP package that packs a repository into a single AI-friendly file for LLM processing.

## Installation

You can install the package via composer:

```bash
composer require vangelis/repophp
```

## Usage

```php
$skeleton = new Vangelis\RepoPHP();
echo $skeleton->echoPhrase('Hello, Vangelis!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you've found a bug regarding security please use the issue tracker.

## Credits

- [Evangelos Dimitriadis](https://github.com/vangelis183)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
