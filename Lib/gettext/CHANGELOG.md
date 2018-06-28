# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

Previous releases are documented in [github releases](https://github.com/oscarotero/Gettext/releases)

## [4.5.0] - 2018-04-23

### Added

- Support for disabled translations

### Fixed

- Added php-7.2 to travis
- Fixed po tests on bigendian [#159](https://github.com/oscarotero/Gettext/issues/159)
- Improved comment estraction [#166](https://github.com/oscarotero/Gettext/issues/166)
- Fixed incorrect docs to dn__ function [#170](https://github.com/oscarotero/Gettext/issues/170)
- Ignored phpcs.xml file on export [#168](https://github.com/oscarotero/Gettext/issues/168)
- Improved `@method` docs in `Translations` [#175](https://github.com/oscarotero/Gettext/issues/175)

## [4.4.4] - 2018-02-21

### Fixed

- Changed the comment extraction to be compatible with gettext behaviour: the comment must be placed in the line preceding the function [#161](https://github.com/oscarotero/Gettext/issues/161).

### Security

- Validate eval input from plural forms [#156](https://github.com/oscarotero/Gettext/pull/156)

## [4.4.3] - 2017-08-09

### Fixed

- Handle `NULL` arguments on extract entries in php. For example `dn__(null, 'singular', 'plural')`.
- Fixed the `PhpCode` and `JsCode` extractors that didn't extract `dn__` and `dngettext` entries [#155](https://github.com/oscarotero/Gettext/pull/155).
- Fixed the `PhpCode` and `JsCode` extractors that didn't extract `dnpgettext` correctly.

## [4.4.2] - 2017-07-27

### Fixed

- Clone the translations in `Translations::mergeWith` to prevent that the translation is referenced in both places. [#152](https://github.com/oscarotero/Gettext/issues/152)
- Fixed escaped quotes in the javascript extractor [#154](https://github.com/oscarotero/Gettext/pull/154)

## [4.4.1] - 2017-05-20

### Fixed

- Fixed a bug where the options was not passed correctly to the merging Translations object [#147](https://github.com/oscarotero/Gettext/issues/147)
- Unified the plural behaviours between PHP gettext and Translator when the plural translation is unknown [#148](https://github.com/oscarotero/Gettext/issues/148)
- Removed the deprecated function `create_function()` and use `eval()` instead

## [4.4.0] - 2017-05-10

### Added

- New option `noLocation` to po generator, to omit the references [#143](https://github.com/oscarotero/Gettext/issues/143)
- New options `delimiter`, `enclosure` and `escape_char` to Csv and CsvDictionary extractors and generators [#145](https://github.com/oscarotero/Gettext/pull/145/)
- Added the missing `dn__()` function [#146](https://github.com/oscarotero/Gettext/pull/146/)

### Fixed

- Improved the code style including php_codesniffer in development

## [4.3.0] - 2017-03-04

### Added

- Added support for named placeholders (using `strtr`). For example:
  ```php
  __('Hello :name', [':name' => 'World']);
  ```
- Added support for Twig v2
- New function `BaseTranslator::includeFunctions()` to include the functions file without register any translator

### Fixed

- Fixed a bug related with the javascript source extraction with single quotes


[4.5.0]: https://github.com/oscarotero/Gettext/compare/v4.4.4...v4.5.0
[4.4.4]: https://github.com/oscarotero/Gettext/compare/v4.4.3...v4.4.4
[4.4.3]: https://github.com/oscarotero/Gettext/compare/v4.4.2...v4.4.3
[4.4.2]: https://github.com/oscarotero/Gettext/compare/v4.4.1...v4.4.2
[4.4.1]: https://github.com/oscarotero/Gettext/compare/v4.4.0...v4.4.1
[4.4.0]: https://github.com/oscarotero/Gettext/compare/v4.3.0...v4.4.0
[4.3.0]: https://github.com/oscarotero/Gettext/compare/v4.2.0...v4.3.0