# kitinerary-bin

Binary executable adapter for the [kitinerary extractor package](https://packagist.org/packages/christophwurst/kitinerary). This package provides an adapter that invokes a shipped [kitinerary-extractor](https://github.com/KDE/itinerary) executable on x86_64 Linux systems.

The statically linked binary is created [from source](https://invent.kde.org/vkrause/kitinerary-static-build).

## Installation

```sh
composer require christophwurst/kitinerary christophwurst/kitinerary-bin
```

## Usage

```php
use ChristophWurst\KItinerary\ItineraryExtractor;
use ChristophWurst\KItinerary\Bin\BinaryAdapter;
use ChristophWurst\KItinerary\Exception\KItineraryRuntimeException;

$adapter = new BinaryAdapter();
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
