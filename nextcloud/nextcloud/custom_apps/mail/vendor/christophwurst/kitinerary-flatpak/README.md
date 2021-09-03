# kitinerary-flatpak

Flatpak adapter for the [kitinerary extractor package](https://packagist.org/packages/christophwurst/kitinerary). This package provides an adapter that invokes [kitinerary-extractor](https://github.com/KDE/itinerary) via Flatpak.

## Installation

See [the KDE itinerary wiki for the Flatpak](https://community.kde.org/KDE_PIM/KDE_Itinerary#Plasma_Mobile.2C_Flatpak) installation instructions.

```sh
composer require christophwurst/kitinerary christophwurst/kitinerary-flatpak
```

## Usage

```php
use ChristophWurst\KItinerary\ItineraryExtractor;
use ChristophWurst\KItinerary\Flatpak\FlatpakAdapter;
use ChristophWurst\KItinerary\Exception\KItineraryRuntimeException;

$adapter = new FlatpakAdapter();
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
