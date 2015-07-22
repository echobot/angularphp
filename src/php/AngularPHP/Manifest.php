<?php

namespace Echobot\AngularPHP;

/**
 * The manifest holds definitions of all classes to be exported by AngularPHP
 *
 * @author    Dave Kingdon <kingdon@echobot.de>
 * @copyright 2014 Echobot Media Technologies GmbH
 */
class Manifest
{
    /**
     * Specified whether to cache expensive operations
     * @var boolean
     */
    private $enableCache = true;

    /**
     * The location in which to store caches
     * @var string
     */
    private $cachePath;

    /**
     * An array of entries, one for each class contained in the manifest
     * @var array
     */
    private $entries = array();

    /**
     * Specifies whether to export all methods & properties whether they're
     * marked with the @Export annotation or not. This is a security nightmare
     * if enabled, as it will export *everything* in the class.    It is fine
     * for use on internal applications which will not be exposed to malicious
     * actors, but use in public-facing applications without a thorough
     * security audit is asking for trouble.    Don't say you've not been warned!
     *
     * @var boolean
     */
    private $exportAll = false;


    public function __construct()
    {
        $this->cachePath = sys_get_temp_dir();
    }


    /**
     * Sets whether to export all methods, properties, and constants,
     * regardless of whether they are marked for export
     *
     * @return boolean
     *   If passed with no parameters, the value of $exportAll
     *
     * @return object
     *   If passed with a parameter, the manifest
     */
    public function exportAll()
    {
        if (func_num_args()) {
            $this->exportAll = func_get_arg(0) == true;
            return $this;
        }
        return $this->exportAll;
    }


    /**
     * Adds a class by name to the manifest
     *
     * @param string $name
     *   The name of the class to add
     *
     * @param string $alias (optional)
     *   The alias to use
     */
    public function addClass($name, $alias = null)
    {
        if ($this->containsClass($name)) {
            return false;
        }

        if (is_null($alias)) {
            $alias = $this->createAlias($name);
        }

        $this->entries[] = new Manifest\Entry($name, $alias, $this);
    }


    /**
     * Specifies a file to scan for classes, and add those classes
     *
     * @param string $filename
     *   The file to scan
     *
     * @param string|array|null $restrict
     *   A glob-esque pattern to restrict classes to
     *
     * @return self
     */
    public function addFile($filename, $restrict = null)
    {
        $classes = $this->getClassesInFile($filename);

        if (!is_null($restrict)) {
            if (!is_array($restrict)) {
                $restrict = array($restrict);
            }

            foreach ($restrict as $pattern) {
                $pattern = str_replace('*', '.+', $pattern);
                $pattern = str_replace('/', '\/', $pattern);
                $pattern = '/' . $pattern . '/u';
                foreach ($classes as $i => $class) {
                    if (!preg_match($pattern, $class)) {
                        unset($classes[$i]);
                    }
                }
            }
        }

        foreach ($classes as $class) {
            $this->addClass($class);
        }

        return $this;
    }


    /**
     * Specifies a directory to scan for PHP files to add
     *
     * @param string $path
     *   The directory to scan
     *
     * @param string|array|null $restrict
     *   A glob-esque pattern to restrict classes to
     *
     * @throws \RuntimeException
     *   If the passed directory does not exist
     *
     * @return self
     */
    public function addDirectory($path, $restrict = null)
    {
        if (!is_dir($path)) {
            throw new \RuntimeException('Directory "' . $path . '" not found');
        }

        $dir = opendir($path);

        while (false !== ($entry = readdir($dir))) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $name = realpath($path . '/' . $entry);

            if (is_dir($name)) {
                $this->addDirectory($name, $restrict);
            } elseif (pathinfo($name, PATHINFO_EXTENSION) == 'php') {
                $this->addFile($name, $restrict);
            }
        }

        return $this;
    }


    /**
     * Returns a list of classes contained in the specified file.  If caching is
     * enabled, a separate file cache is used.
     *
     * @param string $filename
     *   The file to scan
     *
     * @throws \RuntimeException
     *   If the file does not exist
     *
     * @return array
     *   The classes contained in the file
     */
    private function getClassesInFile($filename)
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException('File not found: ' . $filename);
        }

        require_once($filename);

        if ($this->enableCache) {
            $cacheFile = $this->cachePath . '/angular_php_file_cache_' . md5($filename);

            if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($filename)) {
                return unserialize(file_get_contents($cacheFile));
            }
        }

        $tokens = token_get_all(file_get_contents($filename));
        $classes = array();
        $namespace = null;

        for ($c = 0; $c < count($tokens); $c++) {
            if ($tokens[$c][0] == T_NAMESPACE) {
                $tempNs = array();
                do {
                    $c += 2;
                    $tempNs[] = $tokens[$c][1];
                } while ($tokens[$c + 1][0] == T_NS_SEPARATOR);
                $namespace = join('\\', $tempNs);
            }

            if (in_array($tokens[$c][0], array(T_CLASS, T_INTERFACE))) {
                $currentNs = ($namespace ? $namespace . '\\' : '');
                $classes[] = $currentNs . $tokens[$c + 2][1];
            }
        }

        if ($this->enableCache) {
            file_put_contents($cacheFile, serialize($classes));
        }

        return $classes;
    }


    /**
     * Converts a class name into an exportable class name.
     * This is performed by taking enough right-most parts of a class name
     * to make a unique name, and concatenating those together.
     *
     * @param string $class
     *   The class name to convert
     *
     * @return string
     *   The converted class name
     */
    private function createAlias($class)
    {
        foreach ($this->entries as $entry) {
            if ($entry->class == $class) {
                return $entry->alias;
            }
        }

        $bits = explode('\\', $class);
        $count = 0;

        do {
            $alias = join('', array_slice($bits, 0 - ++$count));
        } while ($this->containsAlias($alias));

        return $alias;
    }


    /**
     * Look up an entry by its alias
     *
     * @param  string $alias
     *   The alias to look up
     *
     * @return Manifest\Entry|null
     *   The entry, if found, or null
     */
    public function getEntryByAlias($alias)
    {
        foreach ($this->entries as $entry) {
            if ($entry->alias == $alias) {
                return $entry;
            }
        }
    }


    /**
     * Look up an entry by its class name, or by an object with its class name
     *
     * @param  string|object $class
     *   The class name, or object of whose class name, to look up
     *
     * @return Manifest\Entry|null
     *   The entry, if found, or null
     */
    public function getEntryByClass($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        foreach ($this->entries as $entry) {
            if ($entry->class == $class) {
                return $entry;
            }
        }
    }


    /**
     * Return whether the manifest contains an entry with this alias
     *
     * @param  string $alias
     *   The alias to check for
     *
     * @return boolean
     *   Whether the manifest contains an entry with the alias
     */
    public function containsAlias($alias)
    {
        return $this->getEntryByAlias($alias) !== null;
    }


    /**
     * Return whether the manifest contains an entry with this class name
     *
     * @param  string $class
     *   The class name to check for
     *
     * @return boolean
     *   Whether the manifest contains an entry with the class name
     */
    public function containsClass($class)
    {
        return $this->getEntryByClass($class) !== null;
    }


    /**
     * Tells all the entries to populate themselves from their classes
     */
    public function finalise()
    {
        foreach ($this->entries as $entry) {
            $entry->finalise();
        }

        return $this;
    }


    /**
     * Returns an array representation of all the entries in the manifest
     *
     * @return array
     *   The representation of the manifest
     */
    public function export()
    {
        $toExport = array();

        foreach ($this->entries as $entry) {
            $toExport[] = $entry->export();
        }

        return $toExport;
    }
}
