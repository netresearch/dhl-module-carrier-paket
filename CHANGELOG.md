# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

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
