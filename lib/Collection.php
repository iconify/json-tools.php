<?php

/**
 * This file is part of the iconify/json-tools package.
 *
 * (c) Vjacheslav Trushkin <cyberalien@gmail.com>
 *
 * For the full copyright and license information, please view the license.txt
 * file that was distributed with this source code.
 * @license MIT
 */

namespace Iconify\JSONTools;

class Collection
{
    /**
     * @var int Version number used to bust old cache
     */
    protected static $_version = 1;

    /**
     * @var array
     */
    protected $_items;

    /**
     * @var array
     */
    protected $_result;

    /**
     * Collection constructor.
     *
     * @param string|null $prefix
     */
    public function __construct($prefix = null)
    {
        $this->_items = is_string($prefix) ? [
            'prefix'    => $prefix,
            'icons' => []
        ] : null;
    }

    /**
     * Get prefix
     *
     * @return bool|string
     */
    public function prefix()
    {
        return $this->_items === null ? false : $this->_items['prefix'];
    }

    /**
     * De-optimize JSON data
     *
     * @param array $data
     */
    public static function deOptimize(&$data)
    {
        $keys = null;
        foreach ($data as $prop => $value) {
            if (is_numeric($value) || is_bool($value)) {
                if ($keys === null) {
                    $keys = array_keys($data['icons']);
                }
                foreach ($keys as $key) {
                    if (!isset($data['icons'][$key][$prop])) {
                        $data['icons'][$key][$prop] = $value;
                    }
                }
                unset ($data[$prop]);
            }
        }
    }

    /**
     * Optimize collection items by moving common values to root object
     *
     * @param array $json Icons
     * @param array $props Properties to optimize, null if default list should be used
     */
    public static function optimize(&$json, $props = null)
    {
        $props = is_array($props) ? $props : ['width', 'height', 'top', 'left', 'inlineHeight', 'inlineTop', 'verticalAlign'];

        // Delete empty aliases list
        if (isset($json['aliases']) && empty($json['aliases'])) {
            unset ($json['aliases']);
        }

        // Check all attributes
        foreach ($props as $prop) {
            $maxCount = 0;
            $maxValue = false;
            $counters = [];
            $failed = false;

            foreach ($json['icons'] as $key => $item) {
                if (!isset($item[$prop])) {
                    $failed = true;
                    break;
                }

                $value = $item[$prop];
                $valueKey = '' . $value;

                if (!$maxCount) {
                    // First item
                    $maxCount = 1;
                    $maxValue = $value;
                    $counters[$valueKey] = 1;
                    continue;
                }

                if (!isset($counters[$valueKey])) {
                    // First entry for new value
                    $counters[$valueKey] = 1;
                    continue;
                }

                $counters[$valueKey] ++;

                if ($counters[$valueKey] > $maxCount) {
                    $maxCount = $counters[$valueKey];
                    $maxValue = $value;
                }
            }

            if (!$failed && $maxCount > 1) {
                // Remove duplicate values
                $json[$prop] = $maxValue;
                foreach ($json['icons'] as $key => $item) {
                    if ($item[$prop] === $maxValue) {
                        unset($json['icons'][$key][$prop]);
                    }
                }
            }
        }
    }

    /**
     * Load collection from file
     *
     * @param string $filename File to load from
     * @param string|null $cacheFile File to save cache
     * @return boolean
     */
    public function loadFromFile($filename, $cacheFile = null)
    {
        if ($cacheFile !== null && @file_exists($cacheFile)) {
            // Try to load from cache
            if ($this->loadFromCache($cacheFile, filemtime($filename))) {
                return true;
            }
        }

        // Load from file
        $data = file_get_contents($filename);

        // Get default prefix from filename
        $parts = explode('/', $filename);
        $parts = explode('\\', array_pop($parts));
        $parts = explode('.', array_pop($parts));
        $defaultPrefix = array_shift($parts);

        if (!$this->loadJSON($data, $defaultPrefix)) {
            return false;
        }

        // Save cache
        if ($cacheFile !== null) {
            $this->saveCache($cacheFile, filemtime($filename));
        }
        return true;
    }

    /**
     * Load data from JSON string or decoded array
     *
     * @param string|array $data
     * @param string $defaultPrefix
     * @return boolean
     */
    public function loadJSON($data, $defaultPrefix = null)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // Validate
        if (!is_array($data) || !isset($data['icons'])) {
            return false;
        }

        // DeOptimize
        $this->deOptimize($data);

        // Collection does not have prefix - attempt to detect it
        // All icons in collection must have same prefix and its preferred if prefix is set
        if (!isset($data['prefix']) || $data['prefix'] === '') {
            if ($defaultPrefix === null) {
                // Get prefix from first icon
                $keys = array_keys($data['icons']);
                if (empty($keys)) {
                    return false;
                }

                $key = $keys[0];
                $parts = explode(':', $key);

                if (count($parts) === 2) {
                    $prefix = $parts[0];
                } else {
                    $parts = explode('-', $key);
                    if (count($parts) < 2) {
                        return false;
                    }
                    $prefix = $parts[0];
                }
            } else {
                $prefix = $defaultPrefix;
            }

            $prefixLength = strlen($prefix);
            $sliceLength = $prefixLength + 1;
            $test1 = $prefix . ':';
            $test2 = strpos($prefix, '-') !== false ? null : $prefix . '-';

            // Remove prefix from all icons and aliases
            foreach(['icons', 'aliases'] as $prop) {
                if (!isset($data[$prop])) {
                    continue;
                }
                $newItems = [];

                foreach ($data[$prop] as $key => $item) {
                    // Verify that icon has correct prefix, return false on error
                    $test = substr($key, 0, $sliceLength);

                    if ($test !== $test1 && $test !== $test2) {
                        return false;
                    }

                    $newKey = substr($key, $sliceLength);
                    if (isset($item['parent'])) {
                        // Verify that parent icon has correct prefix, return false on error
                        $parent = $item['parent'];
                        $test = substr($parent, 0, $sliceLength);

                        if ($test !== $test1 && $test !== $test2) {
                            return false;
                        }
                        $item['parent'] = substr($parent, $sliceLength);
                    }
                    $newItems[$newKey] = $item;
                }
                $data[$prop] = $newItems;
            }
            $data['prefix'] = $prefix;
        }

        // Success
        $this->_items = $data;
        return true;
    }

    /**
     * Get filename for Iconify collection.
     * If directory is not specified, make sure iconify/json package is installed.
     *
     * @param string $name Collection prefix
     * @param null|string $dir Directory of iconify/json repository
     * @return string
     */
    public static function findIconifyCollection($name, $dir = null)
    {
        if ($dir === null) {
            // Get directory from iconify/json package
            $dir = \Iconify\IconsJSON\Finder::rootDir();
        }

        return $dir . '/json/' . $name . '.json';
    }

    /**
     * Load Iconify collection.
     * If directory is not specified, make sure iconify/json package is installed.
     *
     * @param string $name Collection prefix
     * @param null|string $dir Directory of iconify/json repository
     * @return bool
     */
    public function loadIconifyCollection($name, $dir = null)
    {
        $filename = self::findIconifyCollection($name, $dir);
        return $this->loadFromFile($filename);
    }

    /**
     * Load from cache
     *
     * @param string $filename Cache filename
     * @param int $fileTime Time stamp of source file to compare to
     * @return boolean
     */
    public function loadFromCache($filename, $fileTime = 0)
    {
        $cache_file = null;
        $cache_time = null;
        $cache_version = null;
        $cached_items = null;

        try {
            /** @noinspection PhpIncludeInspection */
            include $filename;
        } catch (\Exception $e) {
            return false;
        }

        if (
            $cache_file !== $filename ||
            $cache_version !== self::$_version ||
            ($cache_time !== null && $cache_time !== $fileTime) ||
            $cached_items === null
        ) {
            return false;
        }

        $this->_items = $cached_items;
        return true;
    }

    /**
     * Save cache
     *
     * @param string $filename Cache filename
     * @param int $fileTime Time stamp of source file
     */
    public function saveCache($filename, $fileTime)
    {
        if ($this->_items === null) {
            return;
        }
        $content = "<?php \nif (class_exists('\\\\Iconify\\\\JSONTools\\\\Collection', false)) { \n
            \$cache_file = " . var_export($filename, true) . ";
            \$cache_time = " . var_export($fileTime, true) . ";
            \$cache_version = " . var_export(self::$_version, true) . ";
            \$cached_items = " . var_export($this->_items, true) . ";
        }";
        file_put_contents($filename, $content);
        @chmod($filename, 0644);
    }

    /**
     * Get icons data (ready to be saved as JSON)
     *
     * @param null|array $icons List of icons, null if all icons
     * @param bool $optimize True if result should be optimized
     * @return array|null
     */
    public function getIcons($icons = null, $optimize = false)
    {
        if ($this->_items === null) {
            return null;
        }

        if ($icons === null) {
            $result = $this->_items;
        } else {
            $this->_result = [
                'prefix'    => $this->_items['prefix'],
                'icons' => [],
                'aliases'   => []
            ];
            foreach ($icons as $icon) {
                $this->_copy($icon, 0);
            }
            $result = $this->_result;
        }

        if ($optimize) {
            Collection::optimize($result);
        }
        return $result;
    }

    /**
     * Copy icon. Internal function used by getIcons()
     *
     * @param $name
     * @param $iteration
     * @return bool
     */
    protected function _copy($name, $iteration)
    {
        if ($iteration > 5 || isset($this->_result['icons'][$name]) || isset($this->_result['aliases'][$name])) {
            return true;
        }
        if (isset($this->_items['icons'][$name])) {
            $this->_result['icons'][$name] = $this->_items['icons'][$name];
            return true;
        }
        if (isset($this->_items['aliases']) && isset($this->_items['aliases'][$name])) {
            if (!$this->_copy($this->_items['aliases'][$name]['parent'], $iteration + 1)) {
                return false;
            }
            $this->_result['aliases'][$name] = $this->_items['aliases'][$name];
            return true;
        }
        return false;
    }

    /**
     * Get icon data for SVG
     * This function assumes collection has been loaded. Verification should be done during loading
     *
     * @param string $name
     * @return array|null
     */
    public function getIconData($name)
    {
        if (isset($this->_items['icons'][$name])) {
            return self::addMissingAttributes($this->_items['icons'][$name]);
        }

        // Alias
        if (!isset($this->_items['aliases']) || !isset($this->_items['aliases'][$name])) {
            return null;
        }
        $this->_result = $this->_items['aliases'][$name];

        $parent = $this->_items['aliases'][$name]['parent'];
        $iteration = 0;

        while ($iteration < 5) {
            if (isset($this->_items['icons'][$parent])) {
                // Merge with icon
                $this->_mergeIcon($this->_items['icons'][$parent]);
                return self::addMissingAttributes($this->_result);
            }

            if (!isset($this->_items['aliases'][$parent])) {
                return null;
            }
            $this->_mergeIcon($this->_items['aliases'][$parent]);
            $parent = $this->_items['aliases'][$parent]['parent'];
            $iteration ++;
        }
        return null;
    }

    /**
     * Merge icon data with $this->_result. Internal function used by getIconData()
     *
     * @param $data
     */
    protected function _mergeIcon($data)
    {
        foreach($data as $key => $value) {
            if (!isset($this->_result[$key])) {
                $this->_result[$key] = $value;
                continue;
            }
            // Merge transformations, ignore the rest because alias overwrites parent items's attributes
            switch ($key) {
                case 'rotate':
                    $this->_result['rotate'] += $value;
                    break;

                case 'hFlip':
                case 'vFlip':
                    $this->_result[$key] = $this->_result[$key] !== $value;
            }
        }
    }

    /**
     * Add missing properties to icon
     *
     * @param array $data
     * @return array
     */
    public static function addMissingAttributes($data)
    {
        $item = array_merge([
            'left'  => 0,
            'top'   => 0,
            'width' => 16,
            'height'    => 16,
            'rotate'    => 0,
            'hFlip' => false,
            'vFlip' => false
        ], $data);
        if (!isset($item['inlineTop'])) {
            $item['inlineTop'] = $item['top'];
        }
        if (!isset($item['inlineHeight'])) {
            $item['inlineHeight'] = $item['height'];
        }
        if (!isset($item['verticalAlign'])) {
            // -0.143 if icon is designed for 14px height,
            // otherwise assume icon is designed for 16px height
            $item['verticalAlign'] = $item['height'] % 7 === 0 && $item['height'] % 8 !== 0 ? -0.143 : -0.125;
        }
        return $item;
    }

    /**
     * Check if icon exists
     *
     * @param string $name
     * @return bool
     */
    public function iconExists($name)
    {
        return $this->_items === null ? false : isset($this->_items['icons'][$name]) || (isset($this->_items['aliases']) && isset($this->_items['aliases'][$name]));
    }

    /**
     * Get list of icons
     *
     * @param bool $includeAliases
     * @return array
     */
    public function listIcons($includeAliases = false)
    {
        if ($this->_items === null) {
            return [];
        }

        $result = array_keys($this->_items['icons']);
        if ($includeAliases && isset($this->_items['aliases'])) {
            $result = array_merge($result, array_keys($this->_items['aliases']));
        }

        return $result;
    }

    /**
     * Remove icon
     *
     * @param string $icon
     * @param boolean $checkAliases
     */
    public function removeIcon($icon, $checkAliases = true)
    {
        if ($this->_items === null) {
            return;
        }

        // Unset icon
        if (isset($this->_items['icons'][$icon])) {
            unset($this->_items['icons'][$icon]);
        } elseif (isset($this->_items['aliases']) && isset($this->_items['aliases'][$icon])) {
            unset($this->_items['aliases'][$icon]);
        } else {
            return;
        }

        // Check aliases
        if ($checkAliases && isset($this->_items['aliases'])) {
            $list = [];
            foreach ($this->_items['aliases'] as $key => $data) {
                if ($data['parent'] === $icon) {
                    $list[] = $key;
                }
            }
            foreach ($list as $key) {
                $this->removeIcon($key, true);
            }
        }
    }

    /**
     * Add icon to collection
     *
     * @param string $name Icon name
     * @param array $data Icon data
     * @return bool
     */
    public function addIcon($name, $data)
    {
        if (!is_array($data) || !isset($data['body'])) {
            return false;
        }
        return $this->_add($name, $data, false);
    }

    /**
     * Add alias to collection
     *
     * @param string $name Icon name
     * @param string $parent Parent icon name
     * @param array $data Icon data
     * @return bool
     */
    public function addAlias($name, $parent, $data = [])
    {
        if (!is_array($data) || !$this->iconExists($parent)) {
            return false;
        }
        $data['parent'] = $parent;
        return $this->_add($name, $data, true);
    }

    /**
     * Add item
     *
     * @param $name
     * @param $data
     * @param $alias
     * @return bool
     */
    protected function _add($name, $data, $alias)
    {
        if ($this->_items === null) {
            return false;
        }

        if ($alias && !isset($this->_items['aliases'])) {
            $this->_items['aliases'] = [];
        }
        $this->_items[$alias ? 'aliases' : 'icons'][$name] = $data;
        if (!$alias && isset($this->_items['aliases'])) {
            unset ($this->_items['aliases'][$name]);
        }

        return true;
    }

    /**
     * Convert to Iconify script
     *
     * @param null|array $options
     * @return string
     */
    public function scriptify($options = null)
    {
        if ($this->_items === null) {
            return '';
        }

        $defaultOptions = [
            // Array of icons to get
            'icons' => null,

            // JavaScript callback function. Default callback uses SimpleSVG instead of Iconify for backwards compatibility
            // with Iconify 1.0.0-beta6 (that used to be called SimpleSVG) and older versions.
            'callback'  => 'SimpleSVG.addCollection',

            // True if result should be optimized for smaller file size
            'optimize'  => false,

            // True if result should be pretty for easy reading
            'pretty'    => false
        ];

        if (is_array($options)) {
            $options += $defaultOptions;
        } else {
            $options = $defaultOptions;
        }

        // Get JSON data
        $json = $this->getIcons($options['icons'], $options['optimize']);
        $json = json_encode($json, $options['pretty'] ? JSON_PRETTY_PRINT : 0);

        // Wrap in callback
        return $options['callback'] . '(' . $json . ");\n";
    }
}
