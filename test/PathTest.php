<?php
declare(strict_types=1);

namespace Ortnit\Test;

use Ortnit\Path\Path;
use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    public function testCanJoinPaths(): void
    {
        $path = Path::joinPath('test', 'test123', 'text.txt');
        dump($path);
        $this->assertIsBool(true);
    }

    /** @test */
    public function canSplitPath()
    {
        $parts = Path::splitPath('/etc/resolv.conf');
        dump($parts);

        $this->assertIsArray($parts);
    }

    /** @test */
    public function canFilterForbiddenPart()
    {
        $this->assertTrue(Path::filterForbiddenPart(''));
        $this->assertTrue(Path::filterForbiddenPart('.'));
        $this->assertTrue(Path::filterForbiddenPart('..'));
        $this->assertFalse(Path::filterForbiddenPart('.test'));
        $this->assertFalse(Path::filterForbiddenPart('blablub.txt'));
    }

    /** @test */
    public function canFindIfAbsolutePath()
    {
        $this->assertTrue(Path::isAbsolutePath('/etc/passwd'));
        $this->assertFalse(Path::isAbsolutePath('./config'));
        $this->assertFalse(Path::isAbsolutePath('~/.sshd'));
    }
}
