<?php

namespace Ortnit\Path;

class Path
{
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
     * @return null|string
     */
    public static function joinPath()
    {
        $delimiter = '/';
        $args = func_get_args();
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
