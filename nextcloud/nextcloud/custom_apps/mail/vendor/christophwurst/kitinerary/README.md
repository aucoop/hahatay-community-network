# kitinerary

[KDE itinerary extractor](https://github.com/KDE/itinerary) for php. This package does not provide the bindings to the C++ applications. Use the [binary adapter](https://packagist.org/packages/christophwurst/kitinerary-bin) and [Flatpak adapter](https://packagist.org/packages/christophwurst/kitinerary-flatpak) in combination with this package.

## Installation

```sh
composer require christophwurst/kitinerary
```

## Usage

```php
use ChristophWurst\KItinerary\ItineraryExtractor;
use ChristophWurst\KItinerary\Exception\KItineraryRuntimeException;

$extractor = new Extractor(/* adapter instance */);

try {
    $itinerary = $extractor->extractFromString('...');
} catch (KItineraryRuntimeException $e) {
    // ...
}
```
