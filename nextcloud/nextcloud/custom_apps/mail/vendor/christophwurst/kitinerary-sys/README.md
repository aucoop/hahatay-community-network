# kitinerary-sys

System executable adapter for the [kitinerary extractor package](https://packagist.org/packages/christophwurst/kitinerary). This package provides an adapter that invokes the binary installed on the system, e.g. with a Linux distribution's package manager.

## Installation

```sh
composer require christophwurst/kitinerary christophwurst/kitinerary-sys
```

## Usage

```php
use ChristophWurst\KItinerary\ItineraryExtractor;
use ChristophWurst\KItinerary\Sys\SysAdapter;
use ChristophWurst\KItinerary\Exception\KItineraryRuntimeException;

$adapter = new SysAdapter();
if (!$adapter->isAvailable()) {
    // ...
}
$extractor = new Extractor($adapter);

try {
    $itinerary = $extractor->extractFromString('...');
} catch (KItineraryRuntimeException $e) {
    // ...
}
```
