# RepoPHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vangelis/repophp.svg?style=flat-square)](https://packagist.org/packages/vangelis/repophp)  
[![Tests](https://img.shields.io/github/actions/workflow/status/vangelis183/repophp/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/vangelis183/repophp/actions/workflows/run-tests.yml)  
[![Total Downloads](https://img.shields.io/packagist/dt/vangelis/repophp.svg?style=flat-square)](https://packagist.org/packages/vangelis/repophp)

RepoPHP is a PHP package that packs a repository into a single AI-friendly file for LLM processing. Similar to [repomix](https://github.com/yamadashy/repomix)

## Installation

You can install the package via composer:

```bash  
composer require vangelis/repophp --dev
```  
  
## Usage  
## Pack Command Usage  
  
You can use the `pack` command to package a local repository directory into a single file, suitable for processing by AI-based systems.  
  
### Available Options for the `pack` Command:  
- **repository** (Required):    
  The path to the repository directory that you want to pack.  
  
- **output** (Required):    
  The path to the output file where the packed content will be stored.  
  
- **--format \<plain|markdown|json|xml>** *(default: plain)*:    
  Specifies the format of the output file. Supported formats:  
    - `plain`: Plain text format.  
    - `markdown`: Markdown format for better readability.  
    - `json`: JSON format for structured data.  
    - `xml`: XML format for structured data.  
  
- **--exclude \<pattern1,pattern2,...>**:    
  Additional file patterns to exclude during the packing process.    
  These patterns are added to the default exclusions (e.g., `.env`, `composer.lock`, etc.).  
  
- **--no-gitignore**:    
  If this flag is provided, `.gitignore` files will not be used to exclude files.  
  
### Example Usage  
  
Use the following command to pack a local repository:  
  
```bash  
vendor/bin/repophp pack /path/to/repository /path/to/output --format=json --exclude="*.log,.env.local" --no-gitignore --compress  
```  

Remote Repository:

```bash
vendor/bin/repophp pack /path/to/output --remote=https://github.com/username/repo.git --branch=develop --format=markdown
```

#### Breakdown:
- Packs the repository located at `/path/to/repository`.
- Stores the packed content in `/path/to/output`.
- Uses `json` as the output format.
- Excludes files matching the `*.log` and `.env.local` patterns.
- Ignores `.gitignore` rules.
- Compresses the output file. Strip comments and empty lines.

### Additional Behavior
- **Overwrite Handling**:    
  If the output file already exists, you will be prompted to confirm whether you want to overwrite the file. If you choose not to overwrite, a new file will be created with a timestamp appended to its name.

- **Supported Formats**:    
  The following formats are supported (as defined in `RepoPHP`):
    - `plain`
    - `markdown`
    - `json`
    - `xml`

- **Default Exclusions**:    
  Some files are excluded automatically during the packing process (e.g., `.env`, `composer.lock`, and other commonly ignored files). The list of default exclusions can be found in the `RepoPHP` class.

### Error Handling

The `pack` command gracefully handles errors such as:
- Invalid repository paths.
- Invalid output paths.
- Unsupported output formats.
- Failures in creating or writing the output file.

If any error occurs, an appropriate error message will be shown in the console.

## Testing

```bash  
composer test
```  
  
## Changelog  
  
Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.  
  
## ToDos  
- [x] Move settings to configuration
- [x] Git Repositiory Information
- [x] Directory structure
- [x] More tests  
- [x] Token Count for each file and entire repo
- [x] Consider different encodings
- [x] Add compression (Comments etc.)
- [x] Add option for remote Git Repositories
- [x] Add option for specific branch
- [ ] Implement incremental/diff-based packing
- [ ] Add repository splitting for large codebases
- [ ] Create advanced filtering options (by date, content)
- [ ] Add repository analytics and metrics
- [ ] Implement model-specific optimization profiles
- [ ] Develop CI/CD integration options
- [ ] Build interactive CLI mode

## Ideas   


  
## Contributing  
  
Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.  
  
## Security Vulnerabilities  
  
If you've found a bug regarding security please use the issue tracker.  
  
## Credits  
  
- [Evangelos Dimitriadis](https://github.com/vangelis183)  
  
## License  
  
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
