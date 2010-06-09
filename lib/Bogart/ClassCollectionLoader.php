<?php

namespace Bogart;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * ClassCollectionLoader.
 *
 * @package    Symfony
 * @subpackage Foundation
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class ClassCollectionLoader
{
    /**
     * @throws \InvalidArgumentException When class can't be loaded
     */
    static public function load($classes, $cacheDir, $name, $autoReload)
    {
        $cache = $cacheDir.'/'.$name.'.php';

        // auto-reload
        $reload = false;
        if ($autoReload) {
            $metadata = $cacheDir.'/'.$name.'.meta';
            if (!file_exists($metadata) || !file_exists($cache)) {
                $reload = true;
            } else {
                $time = filemtime($cache);
                $meta = unserialize(file_get_contents($metadata));

                if ($meta[1] != $classes) {
                    $reload = true;
                } else {
                    foreach ($meta[0] as $resource) {
                        if (!file_exists($resource) || filemtime($resource) > $time) {
                            $reload = true;

                            break;
                        }
                    }
                }
            }
        }

        if (!$reload && file_exists($cache)) {
            require_once $cache;

            return;
        }

        $files = array();
        $content = '';
        foreach ($classes as $class) {
          new $class();
            if (!class_exists($class) && !interface_exists($class)) {
                throw new \InvalidArgumentException(sprintf('Unable to load class "%s"', $class));
            }

            $r = new \ReflectionClass($class);
            $files[] = $r->getFileName();

            $content .= preg_replace(array('/^\s*<\?php/', '/\?>\s*$/'), '', file_get_contents($r->getFileName()));
        }

        // cache the core classes
        if (!is_dir(dirname($cache))) {
            mkdir(dirname($cache), 0777, true);
        }
        self::writeCacheFile($cache, self::stripComments('<?php '.$content));

        if ($autoReload) {
            // save the resources
            self::writeCacheFile($metadata, serialize(array($files, $classes)));
        }
    }

    static protected function writeCacheFile($file, $content)
    {
        $tmpFile = tempnam(dirname($file), basename($file));
        if (!$fp = @fopen($tmpFile, 'wb')) {
            die(sprintf('Failed to write cache file "%s".', $tmpFile));
        }
        @fwrite($fp, $content);
        @fclose($fp);

        if ($content != file_get_contents($tmpFile)) {
            die(sprintf('Failed to write cache file "%s" (cache corrupted).', $tmpFile));
        }

        @rename($tmpFile, $file);
        chmod($file, 0644);
    }
    
    static public function stripComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $ignore = array(T_COMMENT => true, T_DOC_COMMENT => true);
        $output = '';
        foreach (token_get_all($source) as $token) {
            // array
            if (isset($token[1])) {
                // no action on comments
                if (!isset($ignore[$token[0]])) {
                    // anything else -> output "as is"
                    $output .= $token[1];
                }
            } else {
                // simple 1-character token
                $output .= $token;
            }
        }

        return $output;
    }
}