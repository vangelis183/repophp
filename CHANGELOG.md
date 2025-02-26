# Changelog

All notable changes to `RepoPHP` will be documented in this file.

## 0.5.0 2025-02-27

### What's Changed

#### Added

- Added token encoding support with multiple options (cl100k_base, p50k_base, r50k_base, p50k_edit)
- Added encoding configuration to RepoPHPConfig class
- Implemented token counting functionality using external binary
- Added command-line option for specifying token encoding (-e, --encoding)

#### Changed

- Updated FileWriter to use configured encoding for token counting
- Modified PackCommand to include encoding parameter
- Improved command-line interface with proper option shortcuts
- Enhanced configuration handling for token counter binary path

#### Fixed

- Fixed missing encoding parameter in token counting calls
- Corrected option shortcut naming in PackCommand
- Fixed binary file detection in token counter
- Resolved path handling issues for token counter executable

#### Technical

- Added unit tests for TokenCounter class
- Implemented comprehensive tests for RepoPHPConfig
- Added validation for supported encodings
- Enhanced error handling for token counter binary

## 0.4.0 - 2025-02-25

### What's Changed

#### Added

- Extended git repository information display in the pack summary
- Added branch, commit hash, author and remote information to output
- Implemented new file statistics in pack summary (total files and chars)
- Added formatted output for repository pack summary
- Enhanced support for handling Windows paths in formatters

#### Changed

- Improved file collection process with better gitignore handling
- Reorganized formatter classes for better maintainability
- Enhanced error messages for path validation
- Updated file writing process for better performance
- Improved path normalization and validation

#### Fixed

- Fixed path handling issues on Windows systems
- Improved error handling for file read/write operations
- Fixed formatting inconsistencies in different output formats
- Corrected gitignore pattern matching

#### Technical

- Improved code organization with dedicated service classes
- Enhanced type safety throughout the codebase
- Added comprehensive unit tests for new features
- Updated PHP dependencies to latest stable versions

## [0.4.0] - 2025-02-26

### Added

- Extended git repository information display in the pack summary
- Added branch, commit hash, author and remote information to output
- Implemented new file statistics in pack summary (total files and chars)
- Added formatted output for repository pack summary
- Enhanced support for handling Windows paths in formatters

### Changed

- Improved file collection process with better gitignore handling
- Reorganized formatter classes for better maintainability
- Enhanced error messages for path validation
- Updated file writing process for better performance
- Improved path normalization and validation

### Fixed

- Fixed path handling issues on Windows systems
- Improved error handling for file read/write operations
- Fixed formatting inconsistencies in different output formats
- Corrected gitignore pattern matching

### Technical

- Improved code organization with dedicated service classes
- Enhanced type safety throughout the codebase
- Added comprehensive unit tests for new features
- Updated PHP dependencies to latest stable versions

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
