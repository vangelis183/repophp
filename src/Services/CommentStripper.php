<?php

namespace Vangelis\RepoPHP\Services;

class CommentStripper
{
    /**
     * Clean a single file's content
     *
     * @param  string  $content  Path to the source file
     * @return string|null Cleaned content or null if file not allowed/invalid
     */
    public function cleanFile(string $content): ?string
    {
        return $this->removeCommentsAndEmptyLines($content);
    }

    private function removeCommentsAndEmptyLines(string $code): string
    {
        $tokens = token_get_all($code);
        $output = '';
        $inMultilineComment = false;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                [
                    $tokenType,
                    $tokenValue,
                ] = $token;

                switch ($tokenType) {
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        // Skip single-line and doc comments
                        break;

                    case T_END_HEREDOC:
                    case T_START_HEREDOC:
                        $output .= $tokenValue;
                        break;

                    default:
                        if (!$inMultilineComment) {
                            $output .= $tokenValue;
                        }
                }
            } else {
                // Handle multi-line comment start/end
                if ($token === '/*') {
                    $inMultilineComment = true;
                } elseif ($token === '*/' && $inMultilineComment) {
                    $inMultilineComment = false;
                } elseif (!$inMultilineComment) {
                    $output .= $token;
                }
            }
        }

        // Split into lines and remove empty ones (preserving whitespace)
        $lines = explode("\n", $output);
        $cleanedLines = array_filter($lines, function ($line) {
            return trim($line) !== '';
        });

        // Remove trailing spaces from each line
        $cleanedLines = array_map('rtrim', $cleanedLines);

        return implode("\n", $cleanedLines) . "\n";
    }
}
