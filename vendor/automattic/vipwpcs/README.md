# VIP Coding Standards

This project contains [PHP_CodeSniffer (PHPCS) sniffs and rulesets](https://github.com/squizlabs/PHP_CodeSniffer) to validate code developed for [WordPress VIP](https://wpvip.com/).

This project contains two rulesets:

 - `WordPressVIPMinimum` - for use with projects on the (older) WordPress.com VIP platform.
 - `WordPress-VIP-Go` - for use with projects on the (newer) VIP Go platform.

These rulesets contain only the rules which are considered to be ["errors"](https://docs.wpvip.com/technical-references/code-review/vip-errors/) and ["warnings"](https://docs.wpvip.com/technical-references/code-review/vip-warnings/) according to the WordPress VIP Go documentation.

The rulesets use rules from the [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards) (WPCS) project, as well as the [VariableAnalysis](https://github.com/sirbrillig/phpcs-variable-analysis) standard.

Go to https://docs.wpvip.com/technical-references/code-review/phpcs-report/ to learn about why violations are flagged as errors vs warnings and what the levels mean.

## Minimal requirements

* PHP 5.4+
* [PHPCS 3.7.1+](https://github.com/squizlabs/PHP_CodeSniffer/releases)
* [WPCS 2.3.0+](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/releases)
* [VariableAnalysis 2.11.17+](https://github.com/sirbrillig/phpcs-variable-analysis/releases)

## Installation

To install the VIP Coding Standards, run:
```bash
composer config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer require automattic/vipwpcs
```

or if installing globally:
```bash
composer g config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer g require automattic/vipwpcs
```

This will install the latest compatible versions of PHPCS, WPCS and VariableAnalysis and register the external standards with PHP_CodeSniffer.

Please refer to the [installation instructions for installing PHP_CodeSniffer for WordPress.com VIP](https://docs.wpvip.com/how-tos/code-review/php_codesniffer/) for more details.

As of VIPCS version 2.3.0, there is no need to `require` the [PHP_CodeSniffer Standards Composer Installer Plugin](https://github.com/PHPCSStandards/composer-installer) anymore as it is now a requirement of VIPCS itself. Permission to run the plugin will still need to be granted though when using Composer 2.2 or higher.

## Contribution

Please see [CONTRIBUTION.md](.github/CONTRIBUTING.md).

## License

Licensed under [GPL-2.0-or-later](LICENSE.md).
