# UrlLinker

[![Latest Version](https://img.shields.io/github/release/youthweb/urllinker.svg)](https://github.com/youthweb/urllinker/releases)
[![Software License](https://img.shields.io/badge/license-GPL3-brightgreen.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/youthweb/urllinker.svg?branch=master)](https://travis-ci.org/youthweb/urllinker)
[![Coverage Status](https://coveralls.io/repos/github/youthweb/urllinker/badge.svg?branch=master)](https://coveralls.io/github/youthweb/urllinker?branch=master)

UrlLinker converts any web addresses in plain text into HTML hyperlinks.

This is a fork of the great work of [Kwi\UrlLinker](https://bitbucket.org/kwi/urllinker).

## Install

Via Composer

```bash
$ composer require youthweb/urllinker
```

## Usage

```php
$urlLinker = new Youthweb\UrlLinker\UrlLinker();

$urlLinker->linkUrlsAndEscapeHtml($text);

$urlLinker->linkUrlsInTrustedHtml($html);
```

You can optional configure different options for parsing URLs by passing them to `UrlLinker::__construct()`:

```php
$config = [
    // Ftp addresses like "ftp://example.com" will be allowed, default false
    'allowFtpAddresses' => true,

    // Uppercase URL schemes like "HTTP://exmaple.com" will be allowed:
    'allowUpperCaseUrlSchemes' => true,

    // Add a Closure to modify the way the urls will be linked:
    'htmlLinkCreator' => function($url, $content)
    {
        return '<a href="' . $url . '" target="_blank">' . $content . '</a>';
    },

    // Add a Closure to modify the way the emails will be linked:
    'emailLinkCreator' => function($email, $content)
    {
        return '<a href="mailto:' . $email . '" class="email">' . $content . '</a>';
    },

    // You can also disable the links for email with a closure:
    'emailLinkCreator' => function($email, $content) { return $email; },

    // You can customize the recognizable Top Level Domains:
    'validTlds' => ['.localhost' => true],
];

$urlLinker = new Youthweb\UrlLinker\UrlLinker($config);
```

## Recognized addresses

- Web addresses
  - Recognized URL schemes: "http" and "https"
    - The `http://` prefix is optional.
    - Support for additional schemes, e.g. "ftp", can easily be added by
      setting `allowFtpAddresses` to `true`.
    - The scheme must be written in lower case. This requirement can be lifted
      by setting `allowUpperCaseUrlSchemes` to `true`.
  - Hosts may be specified using domain names or IPv4 addresses.
    - IPv6 addresses are not supported.
  - Port numbers are allowed.
  - Internationalized Resource Identifiers (IRIs) are allowed. Note that the
    job of converting IRIs to URIs is left to the user's browser.
  - To reduce false positives, UrlLinker verifies that the top-level domain is
    on the official IANA list of valid TLDs.
    - UrlLinker is updated from time to time as the TLD list is expanded.
    - In the future, this approach may collapse under ICANN's ill-advised new
      policy of selling arbitrary TLDs for large amounts of cash, but for now
      it is an effective method of rejecting invalid URLs.
    - Internationalized *top-level* domain names must be written in Punycode in
      order to be recognized.
    - If you want to support only some specific TLD you can set them with
      `validTlds` e.g. `['.com' => true, '.net' => true]`.
    - If you need to support unqualified domain names, such as `localhost`,
      you can also set them with `['.localhost' => true]` in `validTlds`.
- Email addresses
  - Supports the full range of commonly used address formats, including "plus
    addresses" (as popularized by Gmail).
  - Does not recognized the more obscure address variants that are allowed by
    the RFCs but never seen in practice.
  - Simplistic spam protection: The at-sign is converted to a HTML entity,
    foiling naive email address harvesters.
  - If you don't want to link emails you can set closure that simply returns the
    raw email with a closure `function($email, $content) { return $email; }` in `emailLinkCreator`.
- Addresses are recognized correctly in normal sentence contexts. For instance,
  in "Visit stackoverflow.com.", the final period is not part of the URL.
- User input is properly sanitized to prevent [cross-site scripting](http://en.wikipedia.org/wiki/Cross-site_scripting) (XSS),
  and ampersands in URLs are [correctly escaped](http://www.htmlhelp.com/tools/validator/problems.html#amp) as `&amp;` (this does not
  apply to the `linkUrlsInTrustedHtml()` function, which assumes its input to
  be valid HTML).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Tests

Unit tests are written using [PHPUnit](https://phpunit.de).

```bash
$ phpunit
```

## Contributing

Please feel free to submit bugs or to fork and sending Pull Requests. This project follows [Semantic Versioning 2](http://semver.org) and [PSR-2](https://www.php-fig.org/psr/psr-2/).

## License

GPL3. Please see [License File](LICENSE.md) for more information.
