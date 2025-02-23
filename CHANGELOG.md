# Changelog

All notable changes to `RepoPHP` will be documented in this file.

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
