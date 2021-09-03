# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.0] - 2019-10-10

### Added

- Update the IANA TLD list with 20 domains less
- Add support for PHP 7.3

### Changed

- Drop support for PHP 5.6, 7.0 and 7.1
- Change Code Style to PSR-2

## [1.2.0] - 2017-08-24

### Added

- config `htmlLinkCreator` in `__construct()` can be a callable
- config `emailLinkCreator` in `__construct()` can be a callable

### Changed

- Updated the IANA TLD list with ~50 more domains
- The test files following PSR-4

### Fixed

- Added a missing `use` for `InvalidArgumentException`
- Don't use deprecated methods internally

## [1.1.0] - 2017-04-10

### Added

- new constructor in `Youthweb\UrlLinker\UrlLinker` for configuration
- add `.gitattributes` file

### Deprecated

- Deprecated `Youthweb\UrlLinker\UrlLinker::setAllowFtpAddresses()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::getAllowFtpAddresses()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::setAllowUpperCaseUrlSchemes()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::getAllowUpperCaseUrlSchemes()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::setHtmlLinkCreator()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::getHtmlLinkCreator()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::setEmailLinkCreator()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::getEmailLinkCreator()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::setValidTlds()`
- Deprecated `Youthweb\UrlLinker\UrlLinker::getValidTlds()`

## [1.0.0] - 2016-09-05

### Added

- Forked from https://bitbucket.org/kwi/urllinker
- This CHANGELOG.md
- Automated testing and code coverage with travis-ci.org and coveralls.io
- Updated the IANA TLD list
- Add your own supported TLDs
- Add a closure to modify the html link creation
- Add a closure to modify or disable the email link creation

### Changed

- Updated min. requirements to PHP 5.6
- Moved the config from the constructor to their own getter and setter methods
- Do not encode html characters twice
- Licensed under GPL3

[Unreleased]: https://github.com/youthweb/urllinker/compare/1.3.0...HEAD
[1.3.0]: https://github.com/youthweb/urllinker/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/youthweb/urllinker/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/youthweb/urllinker/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/youthweb/urllinker/compare/a173dfe2f6ff5a4423612b423323e94b5d2f58e2...1.0.0
