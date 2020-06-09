# Release Notes for Isolate

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
