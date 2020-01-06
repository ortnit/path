<?php

namespace Ortnit\Path;

use Exception;
use Generator;
use InvalidArgumentException;

class Path
{
    /**
     * separates the parts of a path
     */
    const DELIMITER = DIRECTORY_SEPARATOR;

    /**
     * list of strings which will be filtered by sanitize function
     *
     * @var array
     */
    protected static array $forbiddenParts = [
        '',
        '.',
        '..',
    ];

    /**
     * @param string $path
     * @return array|null
     */
    public static function splitPath(string $path): ?array
    {
        $parts = explode(static::DELIMITER, $path);

        return ($parts === false) ? null : $parts;
    }

    /**
     * return if a function is absolute path which is not dependent from its working directory
     *
     * @param string $path
     * @return bool
     */
    public static function isAbsolutePath(string $path): bool
    {
        return (substr($path, 0, 1) == static::DELIMITER);
    }

    /**
     * filters parts which should not be part of an url
     *
     * @param String[] $parts
     * @return array
     */
    public static function sanitizeParts(array $parts): array
    {
        $sanitizedParts = [];

        foreach ($parts as $part) {
            if (static::filterForbiddenPart($part)) {
                continue;
            }

            $sanitizedParts[] = $part;
        }

        return $sanitizedParts;
    }

    /**
     * check if a part is allowed or should be filtered
     * if forbidden function gives back true
     *
     * @param $part
     * @return bool
     */
    public static function filterForbiddenPart($part)
    {
        return (array_search($part, static::$forbiddenParts) !== false);
    }

    /**
     * @param String[] $parts
     * @return string|null
     */
    public static function joinPath(...$parts)
    {
        if (empty($parts)) {
            return null;
        }

        foreach ($parts as $part) {
            if (!is_string($part)) {
                throw new InvalidArgumentException('arguments have to be string');
            }
        }

        $isRoot = static::isRoot($parts[0]);

        $parts = static::cleanParts($parts);

        if ($isRoot) {
            array_unshift($parts, '');
        }

        return implode(static::DELIMITER, $parts);
    }

    /**
     * clean parts, remove forbidden characters
     *
     * @param array $parts
     * @return array
     */
    public static function cleanParts(array $parts): array
    {
        foreach ($parts as $key => $part) {
            $parts[$key] = trim($part, static::DELIMITER . ' ');
        }

        return $parts;
    }

    /**
     * checks for a leading "/" in the path
     *
     * @param string $path
     * @return bool
     */
    public static function isRoot(string $path): bool
    {
        $isRoot = false;
        if (substr($path, 0, 1) == static::DELIMITER) {
            $isRoot = true;
        }

        return $isRoot;
    }

    /**
     * gets the file extension from every file path if available other wise return null
     *
     * @param string $path
     * @return string|null
     */
    public static function getFileExtension(string $path): ?string
    {
        $filename = basename($path);

        $position = strrpos($filename, '.');
        if ($position === 0 || $position === false || strlen($filename) <= $position + 1) {
            return null;
        }

        return substr($filename, $position + 1);
    }


    /**
     * @param $source
     * @param $destination
     * @param null $mode
     * @throws Exception
     */
    public static function copyDirectory($source, $destination, $mode = null)
    {
        if (!is_dir($source)) {
            throw new Exception('source "' . $source . '" does not exist');
        }
        if (!is_dir($destination) && !mkdir($destination, 0777, true)) {
            throw new Exception('destination "' . $destination . '" does not exist');
        }

        $dir = opendir($source);
        while (($node = readdir($dir)) !== false) {
            if ($node == '..' or $node == '.') {
                continue;
            }
            $sourcePath = self::joinPath($source, $node);
            $destinationPath = self::joinPath($destination, $node);

            if (filetype($sourcePath) == 'dir') {
                if (!is_dir($destinationPath)) {
                    if (!mkdir($destinationPath)) {
                        throw new Exception('cannot create directory ' . $destinationPath);
                    }
                }
                self::copyDirectory($sourcePath, $destinationPath, $mode);
            } else {
                //echo "copy: " . $sourcePath . " => " . $destinationPath . "\n";
                if (!copy($sourcePath, $destinationPath)) {
                    throw new Exception("cannot copy " . $sourcePath . " to " . $destinationPath);
                }
                if ($mode != null) {
                    chmod($destinationPath, $mode);
                }
            }
        }
    }

    /**
     * remove directory and content from file system
     *
     * @param string $dir
     * @return bool
     * @throws Exception
     */
    public static function removeDirectory(string $dir): bool
    {
        if (is_dir($dir)) {
            foreach (Path::cycle($dir, false) as $path) {
                if (is_dir($path)) {
                    self::removeDirectory($path);
                } else {
                    if (!unlink($path)) {
                        throw new Exception('cannot delete file ' . $path);
                    }
                }
            }
            if (!rmdir($dir)) {
                throw new Exception('cannot delete directory ' . $dir);
            }
        }

        return false;
    }

    /**
     * cycles through a path, can be used in a foreach with its generator
     *
     * @param string $path
     * @param bool $recursive
     * @return Generator
     */
    public static function cycle(string $path, bool $recursive = true): Generator
    {
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                if ($object == "." || $object == "..") {
                    continue;
                }

                $newPath = static::joinPath($path, $object);

                yield $newPath;

                if (is_dir($newPath) && $recursive) {
                    foreach (static::cycle($newPath) as $filePath) {
                        yield $filePath;
                    }
                }
            }
        }
    }

    /**
     * get the size of a full folder, returns a int in bytes
     *
     * @param string $path
     * @return int
     */
    public static function pathSize(string $path): int
    {
        $sum = 0;
        foreach (Path::cycle($path) as $filePath) {
            if (is_file($filePath)) {
                $sum += filesize($filePath);
            }
        }

        return $sum;
    }
}
