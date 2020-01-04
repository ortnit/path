<?php

namespace Ortnit\Test;

use Ortnit\Path\Path;
use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    protected int $fileCount = 0;

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

    /** @test */
    public function canFilterParts()
    {
        $parts = [
            'test',
            'etc',
            '.abc',
            '..',
            '',
            '.',
            'text.txt',
            '123.',
        ];
        $parts = Path::sanitizeParts($parts);
        $this->assertTrue((array_search('test', $parts) !== false ? true : false));
        $this->assertFalse((array_search('..', $parts) !== false ? true : false));
        $this->assertFalse((array_search('', $parts) !== false ? true : false));
        $this->assertFalse((array_search('.', $parts) !== false ? true : false));
        $this->assertTrue((array_search('123.', $parts) !== false ? true : false));
        $this->assertTrue((array_search('.abc', $parts) !== false ? true : false));
    }

    /** @test */
    public function canCyclePath()
    {
        $path = getcwd();

        $count = 0;
        foreach (Path::cycle($path) as $filePath) {
            echo $filePath . PHP_EOL;
            $count++;
        }

        $fileCount = intval(exec('find ' . $path . '| wc -l'));

        $this->assertEquals($fileCount, $count);
    }

    /** @test */
    public function getPathSize()
    {
        $path = getcwd();

        $size = Path::pathSize($path);

        $this->assertIsInt($size);
        $this->assertGreaterThan(0, $size);
    }

    /** @test */
    public function canGetFileExtension()
    {
        $this->assertEquals('conf', Path::getFileExtension('/etc/sysctl.conf'));
        $this->assertEquals('txt', Path::getFileExtension('~/test.txt'));
        $this->assertEquals('gz', Path::getFileExtension('~/archive.tar.gz'));

        $this->assertNull(Path::getFileExtension('~/test.txt.'));
        $this->assertNull(Path::getFileExtension('~/.bashrc'));
        $this->assertNull(Path::getFileExtension('.profile'));
        $this->assertNull(Path::getFileExtension('/etc/hostname'));
    }
}
