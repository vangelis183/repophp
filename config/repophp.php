<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Format
    |--------------------------------------------------------------------------
    |
    | This option controls the default output format for packed repositories.
    | Supported: "plain", "markdown", "json", "xml"
    |
    */
    'format' => env('REPOPHP_FORMAT', 'plain'),

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | The default path where packed repositories will be saved.
    |
    */
    'output_path' => storage_path('app/repophp/output.txt'),

    /*
    |--------------------------------------------------------------------------
    | Repository Path
    |--------------------------------------------------------------------------
    |
    | The default repository path to pack. Defaults to the base path of your
    | Laravel application.
    |
    */
    'repository_path' => base_path(),

    /*
    |--------------------------------------------------------------------------
    | Exclude Patterns
    |--------------------------------------------------------------------------
    |
    | Define patterns for files that should be excluded from packing.
    | These are in addition to the default exclusions.
    |
    */
    'exclude_patterns' => [
        'node_modules',
        'vendor',
        'storage',
        '.git',
    ],

    /*
    |--------------------------------------------------------------------------
    | Respect .gitignore
    |--------------------------------------------------------------------------
    |
    | Determines whether .gitignore files should be respected when packing.
    |
    */
    'respect_gitignore' => true,

    /*
    |--------------------------------------------------------------------------
    | Token Counter Path
    |--------------------------------------------------------------------------
    |
    | Custom path to the token counter binary. If null, the default path will be used.
    |
    */
    'token_counter_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Encoding
    |--------------------------------------------------------------------------
    |
    | The token encoding to use for calculating token counts.
    | Supported: "cl100k_base", "p50k_base", "r50k_base", "p50k_edit"
    |
    */
    'encoding' => env('REPOPHP_ENCODING', 'cl100k_base'),

    /*
    |--------------------------------------------------------------------------
    | Compress Output
    |--------------------------------------------------------------------------
    |
    | Whether to remove comments and empty lines from packed files.
    |
    */
    'compress' => env('REPOPHP_COMPRESS', false),

    /*
    |--------------------------------------------------------------------------
    | Maximum Tokens Per File
    |--------------------------------------------------------------------------
    |
    | The maximum number of tokens per output file. When exceeded, the repository
    | will be split into multiple files. Set to 0 for no limit.
    |
    */
    'max_tokens_per_file' => env('REPOPHP_MAX_TOKENS', 0),

    /*
    |--------------------------------------------------------------------------
    | Incremental Mode
    |--------------------------------------------------------------------------
    |
    | Whether to create incremental diffs based on changes since the last pack.
    |
    */
    'incremental' => env('REPOPHP_INCREMENTAL', false),

    /*
    |--------------------------------------------------------------------------
    | Base File
    |--------------------------------------------------------------------------
    |
    | The base file to compare against for incremental packing.
    |
    */
    'base_file' => null,
];
