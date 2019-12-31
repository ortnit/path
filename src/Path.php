<?php

namespace Ortnit\Path;

class Path
{
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
        $parts = explode(DIRECTORY_SEPARATOR, $path);

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
        return (substr($path, 0, 1) == DIRECTORY_SEPARATOR);
    }

    public static function sanitizeParts(array $parts)
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
     * @param array $args
     * @return string|null
     */
    public static function joinPath(...$args)
    {
        dump($args);


        $delimiter = '/';
        $parts = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $parts = array_merge($parts, $arg);
            } else {
                $parts[] = $arg;
            }
        }

        if (empty($parts)) {
            return null;
        }

        $leading = false;
        if (substr($parts[0], 0, 1) == $delimiter) {
            $leading = true;
        }
        foreach ($parts as $key => $part) {
            $parts[$key] = trim($part, $delimiter);
        }

        $path = ($leading ? $delimiter : '') . implode($delimiter, $parts);
        return $path;
    }


    /**
     * @param $path
     * @return null
     */
    public static function getExtension($path)
    {
        list($extension, $name) = array_map('strrev', explode('.', strrev($path), 2));
        //var_dump('-----', $path, $name, $extension);
        if (empty($name)) {
            return null;
        }
        return $extension;
    }


    /**
     * @param $source
     * @param $destination
     * @param null $mode
     * @throws \Exception
     */
    public static function copyDirectory($source, $destination, $mode = null)
    {
        if (!is_dir($source)) {
            throw new \Exception('source "' . $source . '" does not exist');
        }
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
            //throw new \Exception('destination "' . $destination . '" does not exist');
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
                        throw new \Exception('cannot create directory ' . $destinationPath);
                    }
                }
                self::copyDirectory($sourcePath, $destinationPath, $mode);
            } else {
                //echo "copy: " . $sourcePath . " => " . $destinationPath . "\n";
                if (!copy($sourcePath, $destinationPath)) {
                    throw new \Exception("cannot copy " . $sourcePath . " to " . $destinationPath);
                }
                if ($mode != null) {
                    chmod($destinationPath, $mode);
                }
            }
        }
    }

    /**
     * @param $dir
     * @return bool
     */
    public static function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        self::removeDirectory(self::joinPath($dir, $object));
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            return rmdir($dir);
        }
        return false;
    }

    public static function pathSize($path)
    {
        $sum = 0;
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $tmpPath = self::joinPath($path, $object);
                    if (is_dir($tmpPath)) {
                        $sum += self::pathSize($tmpPath);
                    } elseif (is_file($tmpPath)) {
                        $size = filesize($tmpPath);
                        //echo $tmpPath . ": " . $size . "\n";
                        $sum += $size;
                    }
                }
            }
        }
        return $sum;
    }
}
