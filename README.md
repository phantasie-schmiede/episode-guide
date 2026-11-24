# PSBits Episode Guide

A TYPO3 extension for creating your own episode guide library. Organize series,
seasons and episodes, import JSON transcripts, and search episodes within a series.

The extension is currently in **alpha**.

## Requirements

- PHP 8.3 or newer, within the supported PHP 8.x range.
- TYPO3 13.4 or 14.3.
- The Composer dependencies declared in `composer.json`, including PSBits
  Foundation, Frontend Templates and View Helpers.

## Installation and setup

Install the extension in your TYPO3 Composer project:

```sh
composer require psbits/episode-guide
```

1. Update the database schema using TYPO3's database analyzer.
2. Include the extension's TypoScript and the configuration required by Frontend
   Templates in your site's TypoScript setup.
3. Create series, season and episode records in the TYPO3 backend.
4. Add the **Series** plugin to a page and select a series in its FlexForm.

The plugin opens the selected series by default. If no usable series is configured
or supplied in the request, it falls back to the series list.

The record storage page can be configured using
`plugin.tx_episodeguide.persistence.storagePid`. Template, partial and layout paths
are configurable through `plugin.tx_episodeguide.view` in
`Configuration/TypoScript/constants.typoscript`.

## Episode search

Search operates within a series and checks episode titles, summaries and JSON
transcripts. Matching is case-insensitive.

- Separate words are combined: all positive terms must match somewhere in the episode.
- Use quotes for phrases, for example `"first contact"`.
- Prefix a term or phrase with `-` to exclude matching episodes, for example
  `contact -"alternate timeline"`.

Results are ordered by season number, episode number and UID, and displayed in
pages of 20 results with highlighted matches.

## Importing episodes

Create the target series and season first, then run this interactive command from
your TYPO3 project root:

```sh
vendor/bin/typo3 episode-guide:import-episodes
```

The command asks for an import directory, a series UID and a season UID. Relative
directory paths are resolved against the TYPO3 project root; absolute paths are
also accepted. Only `*.json` files directly inside the directory are imported.

Use filenames in this format:

```text
1 - Pilot.json
2 - First Contact.json
```

The filename supplies the episode number and title. The complete file content is
stored as the episode transcript. Filenames that do not match the pattern receive
episode number `0` and use the complete filename as the title.

The importer does not parse or validate the JSON and always creates new episodes.
Use valid JSON for transcript search, and avoid re-importing the same files unless
duplicates are intended.

## Development

Run development commands from this extension repository, not from the TYPO3
project root. Install the development dependencies first:

```sh
composer install
```

Dependencies and generated test configuration are stored in `.Build/`. Foundation
generates TCA and the database schema from attributes in `Classes/Domain/Model/`;
the plugin is registered in `Classes/Data/ExtensionInformation.php`.

## Quality

QA tooling and test runners are provided by `psbits/qa`. The CI caller in
`.github/workflows/ci.yml` uses the shared
[`extension-ci.yml` workflow](https://github.com/phantasie-schmiede/qa/blob/main/.github/workflows/extension-ci.yml).

```sh
composer lint:all
composer test:unit
composer test:functional
```

The complete test command includes the direct suites, the local matrix and the
Docker matrix. Prepare Docker images before running it:

```sh
composer docker:images
composer test:all
```

- `composer test:local` runs TYPO3 13.4 and 14.3 with both current and lowest
  allowed dependencies using local PHP and SQLite.
- `composer test:docker` runs eight matrix legs with the same TYPO3 versions,
  including SQLite, MySQL and PostgreSQL. It requires Docker Compose and daemon
  access, and uses PHP 8.4 by default (`PHP_VERSION` selects another built image).
- Matrix runs require network access to resolve Composer dependencies.
- `composer test:coverage` runs coverage checks. With Xdebug, use
  `XDEBUG_MODE=coverage composer test:coverage`.

Validation attributes intentionally use the array syntax supported by both TYPO3
13 and 14. TYPO3 14 reports a deprecation for this syntax, which is scheduled for
removal in TYPO3 15. PHPUnit displays the deprecation but does not fail the test
run (`failOnDeprecation="false"`).

## License

GPL-3.0-or-later. See [LICENSE.txt](LICENSE.txt).
