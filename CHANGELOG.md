# Changelog

All notable changes to `RepoPHP` will be documented in this file.

## 0.3.0 - 2025-02-25

### Release Notes

#### Bug Fixes

- Fixed formatter inheritance
- Move formatting logic to RepoHelper
- Simplified FileWritter
- Maintain separation of concerns
- Updated console command to use proper `QuestionHelper` type instead of generic `HelperInterface`
- Fixed PHPStan configuration by adding `phpstan.neon.dist` with proper paths and settings
- Added proper baseline configuration for static analysis in CI pipeline

#### Technical Improvements

- Added proper type-hinting for Symfony Console helpers
- Improved static analysis configuration for better code quality checks
- Standardized PHPStan configuration between local and CI environments

### What's Changed

* Feature/refactor by @vangelis183 in https://github.com/vangelis183/repophp/pull/2

**Full Changelog**: https://github.com/vangelis183/repophp/compare/0.2.0...0.3.0

## 0.2.0 - 2025-02-23

**Full Changelog**: https://github.com/vangelis183/repophp/commits/0.2.0

## [0.2.0] - 2025-02-23

### Added

- Introduced the `pack` command, allowing users to package a repository into a single AI-friendly file.
- Support for multiple output formats: `plain`, `markdown`, `json`, and `xml`.
- Option to specify file patterns to exclude using the `--exclude` flag.
- Added `--no-gitignore` option to bypass `.gitignore` file rules.
- Overwrite prevention for output files with a confirmation prompt. Automatically appends a timestamp to the filename if the user chooses not to overwrite.
- Included default exclusions for sensitive or unnecessary files (e.g., `.env`, log files, lock files).

### Fixed

- Improved error handling for invalid repository paths, unsupported formats, and non-writable output directories.
- Validation for repository paths, output directories, and output formats.

### Changed

- Updated runtime to allow direct execution of the `pack` command through the console.


---
