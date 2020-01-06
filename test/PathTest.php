<?php

namespace Ortnit\Test;

use Exception;
use InvalidArgumentException;
use Ortnit\Path\Path;
use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    protected int $fileCount = 0;

    /** @test */
    public function canJoinPaths(): void
    {
        $path = Path::joinPath('/test', 'test123', 'text.txt');
        $this->assertEquals('/test/test123/text.txt', $path);
    }

    /** @test */
    public function canJoinArrayParts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Path::joinPath(['test', 'test123', 'text.txt']);
    }

    /** @test */
    public function canJoinEmptyPath()
    {
        $this->assertNull(Path::joinPath());
    }

    /** @test */
    public function canCleanParts()
    {
        $parts = Path::cleanParts(['/test', ' test123', 'text.txt ']);

        $this->assertEquals('test', $parts[0]);
        $this->assertEquals('test123', $parts[1]);
        $this->assertEquals('text.txt', $parts[2]);
    }

    /** @test */
    public function canSplitPath()
    {
        $parts = Path::splitPath('/etc/resolv.conf');

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
            $this->assertIsBool(file_exists($filePath));
            $count++;
        }

        $fileCount = intval(exec('find ' . $path . '| wc -l'));

        $this->assertEquals($fileCount - 1, $count);
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

    public function createTestDir()
    {
        chdir('/tmp');
        mkdir('test_dir');

        mkdir('test_dir/1');
        touch('test_dir/1/1');
        touch('test_dir/1/2');
        touch('test_dir/1/3');

        mkdir('test_dir/2');
        touch('test_dir/2/1');
        touch('test_dir/2/2');
        touch('test_dir/2/3');
    }

    /** @test
     * @throws Exception
     */
    public function canRemoveDir()
    {
        $path = '/tmp/test_dir';

        $this->createTestDir();

        Path::removeDirectory($path);

        $this->assertFalse(is_dir($path));
    }

    /** @test
     * @throws Exception
     */
    public function canCopyDir()
    {
        $path = '/tmp/test_dir';
        $copyPath = '/tmp/copy_dir';

        $this->createTestDir();

        Path::copyDirectory($path, $copyPath);
        $this->assertTrue(is_dir($copyPath));

        Path::removeDirectory($path);
        $this->assertFalse(is_dir($path));

        Path::removeDirectory($copyPath);
        $this->assertFalse(is_dir($copyPath));
    }
}
