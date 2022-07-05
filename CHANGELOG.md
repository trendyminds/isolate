# Release Notes for Isolate

## 2.0.0 - 2022-07-05

### Added
- Support for Craft 4

## 1.4.4 - 2021-11-10

### Fixed
- Fix error when displaying entries an isolated user has access to when using a Postgres database ([#41](https://github.com/trendyminds/isolate/pull/41))

## 1.4.3 - 2020-11-16

### Fixed
- Fix case-sensitivity issue on the `sourcePath` when loading the AssetBundle

## 1.4.2 - 2020-10-28

### Fixed
- Fix error that occurred if an Isolated user is accessing the control panel homepage and `$segments` contains no array items. ([#24](https://github.com/trendyminds/isolate/pull/24))

## 1.4.1 - 2020-10-26

### Fixed
- Renamed directory to ensure Composer 2.0 compatibility. ([#22](https://github.com/trendyminds/isolate/pull/22)). Thanks @brandonkelly!

## 1.4.0 - 2020-10-13

### Changed
- Isolate permissions now follow Craft's convention to correct issues with multi-site and non-English languages. ([#21](https://github.com/trendyminds/isolate/pull/21))

## 1.3.1 - 2020-06-10

### Fixed
- Don't run Isolate event when a user is not signed in or it is invoked via console commands/migrations

## 1.3.0 - 2020-06-09

### Changed
- Isolate 1.3.0 now requires Craft 3.2+

### Fixed
- Submitting a draft as an isolated user automatically adds the entry as one of your "isolated" entries

### Added
- User drafts now show up below on an isolated user's dashboard

## 1.2.0 - 2020-05-13

### Changed
- Replace various custom queries with native Craft queries to ensure better compatibility with future Craft versions

### Fixed
- Address Craft 3.2 compatibility issues with drafts/revisions showing up in entry lists
- Correct visual issues with newer version of Craft

## 1.1.2 - 2019-05-09

### Fixed
- Redirect users attempting to access the entry listing panel instead of throwing an error. This prevents an exception from being thrown if a user saves an entry and is redirected back to the entry listing area

## 1.1.1 - 2019-05-01

### Fixed
- Add another check to determine if a user is attempting to create a new entry versus editing an existing one

## 1.1.0 - 2019-04-30

This version is completely owed to the great work by Benjamin ([@N0ps32](https://github.com/N0ps32) on GitHub). Huge props to him and his contribution here!

### Changed
- Improvements to the entry selection interface when displaying Structures and multi-site entries ([#2](https://github.com/trendyminds/isolate/pull/2/files))
- New "Check All" button to select all entries and uncheck the ones you'd like to remove access to ([#2](https://github.com/trendyminds/isolate/pull/2/files))
- Improvements to PHP DocBlocks used within the Isolate services ([#2](https://github.com/trendyminds/isolate/pull/2/files))

## 1.0.1 - 2019-03-11

### Changed
- Ensure isolated users are automatically given permissions to use the Isolate dashboard

## 1.0.0 - 2019-03-11

Initial release.
