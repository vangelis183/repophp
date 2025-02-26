<?php

declare(strict_types=1);

namespace Vangelis\RepoPHP\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Vangelis\RepoPHP\Services\CommentStripper;

class CommentStripperTest extends TestCase
{
    private CommentStripper $commentStripper;

    protected function setUp(): void
    {
        $this->commentStripper = new CommentStripper();
    }

    public function testRemovesSingleLineComments(): void
    {
        $code = <<<'PHP'
<?php
// This is a single line comment
$foo = 'bar'; // Inline comment
# Shell-style comment
$baz = 'qux';
PHP;

        $expected = <<<'PHP'
<?php
$foo = 'bar';
$baz = 'qux';
PHP;

        $result = $this->commentStripper->cleanFile($code);
        $this->assertEquals($expected . "\n", $result);
    }

    public function testRemovesMultiLineComments(): void
    {
        $code = <<<'PHP'
<?php
/* This is a
multi-line comment */
$foo = 'bar';
/* Another
multi-line
comment */
$baz = 'qux';
PHP;

        $expected = <<<'PHP'
<?php
$foo = 'bar';
$baz = 'qux';
PHP;

        $result = $this->commentStripper->cleanFile($code);
        $this->assertEquals($expected . "\n", $result);
    }

    public function testRemovesDocBlocks(): void
    {
        $code = <<<'PHP'
<?php
/**
 * Class description
 */
class Example {
    /**
     * @var string
     */
    private $foo;

    /**
     * Method description
     */
    public function bar() {
        return 'bar';
    }
}
PHP;

        $expected = <<<'PHP'
<?php
class Example {
    private $foo;
    public function bar() {
        return 'bar';
    }
}
PHP;

        $result = $this->commentStripper->cleanFile($code);
        $this->assertEquals($expected . "\n", $result);
    }

    public function testPreservesHeredoc(): void
    {
        $code = <<<'PHP'
<?php
$str = <<<EOD
This is a heredoc
// This should not be treated as a comment
/* Neither should this */
EOD;
PHP;

        $expected = <<<'PHP'
<?php
$str = <<<EOD
This is a heredoc
// This should not be treated as a comment
/* Neither should this */
EOD;
PHP;

        $result = $this->commentStripper->cleanFile($code);
        $this->assertEquals($expected . "\n", $result);
    }

    public function testPreservesIndentation(): void
    {
        $code = <<<'PHP'
<?php
class Example {
    // Comment to remove
    public function test() {
        $foo = 'bar';
        // Another comment
        return $foo;
    }
}
PHP;

        $expected = <<<'PHP'
<?php
class Example {
    public function test() {
        $foo = 'bar';
        return $foo;
    }
}
PHP;

        $result = $this->commentStripper->cleanFile($code);
        $this->assertEquals($expected . "\n", $result);
    }
}
