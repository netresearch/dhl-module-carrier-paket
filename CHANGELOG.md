# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 2.7.0

### Added

- Display additional locker location information on the map in checkout.
- Select endorsement (abandon, return) for international, undeliverable shipments.
- Select _Bulky Goods_ service with DHL Paket International shipments.
- Select _Named Person Only_ service with DHL Paket National shipments.
- Select _No Neighbor Delivery_ service with DHL Paket National shipments.

### Changed

- The _Print Receiver Contact Details_ configuration setting is replaced by a
  _Send Receiver Phone Number_ setting that applies to cross-border shipments only.

### Fixed

- Improve handling of apartment addresses when splitting the recipient street.
- Prevent broken styling in module configuration, reported via [#50](https://github.com/netresearch/dhl-shipping-m2/issues/50).

## 2.6.2

### Fixed

- Apply min length validator to post number input in checkout.

## 2.6.1

### Fixed

- Fix typing error in checkout service box (DE).

## 2.6.0

Magento 2.4.4 compatibility release

### Added

- Support for Magento 2.4.4

### Removed

- Support for PHP 7.1

## 2.5.0

### Added

- Select and book delivery to DHL Parcel Shops.
- Set or change the _DHL Paket Parcel Outlet Routing_ email notification address individually per shipment.
- Reset service fee configuration setting to module default ("use system value").
- Set shipping product name as track title.

### Changed

- Configure checkout shipping method name in module configuration.

## 2.4.0

### Added

- Warenpost International shipping product.

## 2.3.0

### Added

- Europaket shipping product for EU countries.

## 2.2.1

### Changed

- Establish compatibility with shipping core 2.4.

## 2.2.0

Magento 2.4.3 compatibility release

### Added

- Support for Magento 2.4.3

## 2.1.0

### Added

- Offer _Premium_ service for cross-border shipments.
- Print a logo image on the shipment label by using a shipper address from the DHL business customer portal address book.

## 2.0.1

### Fixed

- Update version constraint for the `netresearch/module-shipping-core` package.

## 2.0.0

### Changed

- Replace shipping core package dependency.

### Fixed

- Include house number addition for Baden-Württemberg shipping addresses.

## 1.4.2

### Added

- Print receiver phone number on the shipping label if enabled via module configuration.

### Changed

- Error message on API communication exceptions is updated as suggested in issue [#18](https://github.com/netresearch/dhl-shipping-m2/issues/18).

## 1.4.1

Bugfix Release

### Fixed

- Update link to the DHL track & trace portal.
- Include exponent number on the shipping label for Italian shipping addresses.

## 1.4.0

Customs Regulations Release

### Added

- Transmit the sender's/addressee's customs reference number with label requests.

### Changed

- Add postal charges to label requests with customs declaration.
- Mark package dimensions required for label requests with customs declaration.
- Mark item description and postal charges required for label requests with customs declaration.

### Fixed

- Round cash on delivery amount if value has more than two digits after the decimal point.

## 1.3.0

### Added

- Possibility to configure an alternative notification email for parcel outlet routing service.
- Text block in module configuration advertising Deutsche Post Direkt services.

### Changed

- Rename Wunschpaket (Preferred Delivery) services to align with official DHL naming.

### Removed

- Contact person name no longer printed on shipping label.

## 1.2.0

Magento 2.4 compatibility release

### Added

- Support for Magento 2.4

### Removed

- Support for Magento 2.2

### Fixed

- Prevent Parcel Management API calls if not required.
- Prevent attempts to bulk create Warenpost labels for orders with Cash On Delivery payment. 
- Allow letters in _Participation Numbers_ configuration.
- Display correct version number in module configuration info box.

## 1.1.0

Deutsche Post Warenpost release

### Added

- Support [Warenpost](https://www.dhl.de/en/geschaeftskunden/paket/leistungen-und-services/dhl-warenpost.html)
  shipping product (merchandise shipment)

### Changed

- Switch to [Unified Location Finder API](https://developer.dhl.com/api-reference/location-finder)
  for displaying parcel stations/post offices in checkout
- Improve translations

### Fixed

- Use correct log file for shipment tracking web service communication
- Do not offer _Visual Check of Age_ service for cross-border shipments

## 1.0.0

Initial release
