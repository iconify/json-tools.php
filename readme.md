# Iconify JSON tools

This library is used to manipulate JSON icon collections.

The library is available for PHP and Node.js, code in both versions is almost identical.

## Installation

To install the library run this command:

```
composer require iconify/json-tools
```

There are two classes in this package: `Collection` and `SVG`

## Collection class

Collection class represents an icon set.

To include it use this code:

```php
use \Iconify\JSONTools\Collection;
```

What can Collection class do?

- Read and write JSON collections.
- Add, remove, list icons in an icon set.
- Retrieve icon data.
- Create icon bundles for Iconify icon sets.

### Initializing class instance

There are two ways to create an instance: with icon set prefix and without icon set prefix.

You can skip icon set prefix in the constructor if you are going to load data from a JSON file because JSON files contain icon set prefix.

```php
$collection = new Collection();
```

```php
$collectionWithPrefix = new Collection('custom-icons');
```

### Loading JSON data

There are several functions to load an icon set from JSON file:

- `loadFromFile()` - loads collection.
- `loadJSON()` - loads JSON data from string or array.
- `loadIconifyCollection()` - loads Iconify collection from `iconify/json` repository.

#### loadFromFile()

This function loads an icon set from a JSON file.

Function parameters:

- `$file` - file to load data from.
- `$defaultPrefix` - optional default prefix in case if JSON file does not have it.
- `$cacheFile` - cache file for a parsed icon set. This option does not exist in Node.js version of the library. Use this to speed up loading.

Returns:

- boolean - true on success, false on failure

```php
$collection = new Collection();
if (!$collection->loadFromFile('json/custom-icons.json')) {
    throw new \Exception('Failed to load custom-icons.json');
}
```

```php
$collection = new Collection();
if (!$collection->loadFromFile('json/custom-icons.json', null, 'cache/custom-icons.php')) {
    throw new \Exception('Failed to load custom-icons.json');
}
```

#### loadJSON()

This function loads an icon set from a string or an array.

Function parameters:

- `$data` - JSON string or array.
- `$prefix` - optional prefix if JSON file doesn't include one.

Returns:

- boolean - true on success, false on failure

```php
$collection = new Collection();
// Use this if collection has prefix
if (!$collection->loadJSON($data)) {
    throw new \Exception('Failed to load JSON data');
}
```

```php
$collection = new Collection();
// Use this if collection is missing prefix
if (!$collection->loadJSON($data, 'custom-icons')) {
    throw new \Exception('Failed to load JSON data');
}
```

#### loadIconifyCollection()

This function loads Iconify collection from [iconify/json](https://github.com/iconify-design/collections-json) repository.

Function parameters:

- `$name` - the name of the icon set.
- `$dir` - optional root directory of Iconify icon set. Use this option if you want to load Iconify icon set from a custom directory instead of the `iconify/json` repository.

Returns:

- boolean - true on success, false on failure

```php
$collection = new Collection();
if (!$collection->loadIconifyCollection('mdi')) {
    throw new \Exception('Failed to load Material Design Icons');
}
```

### Caching icon sets

PHP loads icon sets on every page load, so it makes sense not to parse the same data many times. This is why PHP version of the library has caching functions.

#### loadFromCache()

This function loads icon set from the cache.

Function parameters:

- `$filename` - cached filename.
- `$time` - time stamp (retrieved with `filemtime()` function) of the original JSON file. This parameter is used to invalidate cache if JSON file has been updated since the last time cache was saved.

Returns:

- boolean - true on success, false on failure

```php
$collection = new Collection();
$file = Collection::findIconifyCollection('mdi');
if (!$collection->loadFromCache('cache/mdi.php', filemtime($file))) {
    if (!$collection->loadFromFile($file)) {
        throw new \Exception('Cannot load Material Design Icons');
    }
    $collection->saveCache('cache/mdi.php', filemtime($file));
}
```

### saveCache()

Stores icon set data in the cache.

This function does not return anything.

For a usable example, see `loadFromCache()` example above.

### Getting icon data

Several functions can be used to retrieve icon data from an icon set:

- `getIconData()` - returns full data for one icon. It can be used to generate SVG (see SVG class documentation below).
- `getIcons()` - returns JSON data for icons, which can be used to import to another JSON collection or can be added to Iconify using `Iconify.addCollection()`.
- `scriptify()` - returns JavaScript bundle file that can be used to load icons in browser with Iconify.

#### getIconData()

This function returns JSON data for one icon. It returns full data, including optional fields, so the result can be used to generate SVG.

Function parameters:

- `$name` - icon name.

Returns:

- array - icon data

```php
$data = $collection->getIconData('arrow-left');
$svg = new SVG($data);
echo $svg->getSVG();
```

#### getIcons()

This function returns JSON data for selected icons. If used without parameters, it returns JSON data for an entire icon set.

Parameters:

- `$icons` - icon names array.

```php
$data = $collection->getIcons(['arrow-left', 'arrow-right', 'home']);
file_put_contents('bundle.json', json_encode($data));
```

This function can also be used to copy collection:

```php
$data = $collection->getIcons();
$newCollection = new Collection();
$newCollection->loadJSON($data);
```

Using `$collection->getIcons()` without parameters is the same as accessing `$collection->items` array.

#### scriptify()

This is similar to `getIcons()`, but it generates JavaScript file instead of JSON data and parameters are passed as one array.

Function parameters:

- `$options` - options array.

Returns:

- string - JavaScript code you can bundle with your scripts.

Options array keys:

- `icons` - an array of icons to retrieve. If not set or null, all icons will be retrieved.
- `optimize` - boolean. If set to true, JSON data will be optimized to make output smaller.
- `pretty` - boolean. If set to true, JSON data will include white spaces that make output easy to read.
- `callback` - string. JavaScript callback to wrap JSON data in. The default value is `Iconify.addCollection`.

Code to create a bundle with selected icons from one collection (repeat same code for different collections to make bundle of all icons used on website):

```php
$collection = new Collection();
if (!$collection->loadIconifyCollection('mdi')) {
    throw new \Exception('Cannot load Material Design Icons');
}
$code = $collection->scriptify([
    'icons' => ['account', 'account-alert', 'home', 'book-open'],
    'pretty' => false,
    'optimize' => true
]);
file_put_contents('bundle-mdi.js', $code);
```

### Adding/removing icons

#### addIcon()

This function adds a new icon to the icon set.

Function parameters:

- `$name` - icon name.
- `$data` - icon data.

Returns:

- boolean - true on success, false on failure. Failure is possible if an icon is missing 'body' property of if the icon set has no prefix.

```php
$collection = new Collection('custom-icons');
$collection->addIcon('arrow', [
    'body' => '<path d="" />',
    'width' => 24,
    'height' => 24
]);
```

### addAlias()

This function adds an alias for an existing icon.

Function parameters:

- `$name` - alias name.
- `$parent` - parent icon name.
- `$data` - optional data that should override parent icon's data (such as rotation or flip).

Returns:

- boolean - true on success, false on failure. Failure is possible if the parent icon is missing.

```php
$collection = new Collection('custom-icons');
$collection->addIcon('arrow-left', [
    'body' => '<path d="" />',
    'width' => 24,
    'height' => 24
]);
$collection->addAlias('arrow-right', 'arrow-left', [
    'hFlip' => true
]);
$collection->addAlias('arrow-right-alias', 'arrow-right');
```

### setDefaultIconValue()

Set default value for all icons.

Function parameters:

- `$key` - attribute name.
- `$value` - default value.

```php
$collection->setDefaultIconValue('height', 24);
```

### removeIcon()

Removes an icon or an alias from the icon set.

Function parameters:

- `$name` - icon name.
- `$checkAliases` - if true, the icon set will be checked for aliases that use removed icon as parent icon and those aliases will be removed too. Set to false if you know for sure there are no aliases referencing this icon, otherwise set to true.

```php
$collection = new Collection();
$collection->loadIconifyCollection('fa-solid');
$collection->removeIcon('home');
```

### iconExists()

Checks if an icon or an alias exists.

Function parameters:

- `$name` - icon name.

Returns:

- boolean - true or false

```php
if (!$collection->iconExists('home')) {
    throw new \Exception('Missing "home" icon!');
}
```

### listIcons()

Lists all icons in an icon set.

Function parameters:

- `$includeAliases` - set to true to include aliases in the result.

Returns:

- array - list of icons

```php
$collection = new Collection();
$collection->loadIconifyCollection('vaadin');
echo 'Available icons in vaadin collection: ', implode(', ', $collection->listIcons(true)), "\n";
```

### Other functions

#### items

This is a property, not a function. You can use it to have access to raw JSON data. Value is the same as using `getIcons()` without parameters, however editing the result of `getIcons()` will not affect collection data because it copies array.

Editing the `$collection->items` array will change collection data.

#### prefix()

Returns the icon set prefix, `false` if the icon set has no prefix.

Returns:

- string|boolean - Prefix, `false` on error.

```php
$prefix = $collection->prefix();
```

#### findIconifyCollection()

This function locates Iconify icon set from [iconify/json](https://github.com/iconify-design/collections-json) repository.

Function parameters:

- `$name` - Prefix of the icon set.
- `$dir` - Optional root directory where Iconify icon sets are stored. Use this option if you want to load Iconify icon set from a custom directory instead of the `iconify/json` repository.

Returns:

- string - location of the file.

```php
echo 'fa-solid.json can be found at ', Collection::findIconifyCollection('fa-solid'), "\n";
```

#### optimize()

Optimize is a static function that optimizes JSON data. It modifies an array passed by reference in the first parameter.

Function parameters:

- `$data` - JSON data to optimize, passed by reference.
- `$props` - an optional array of properties to optimize. If not set, default properties list will be used.

```php
$data = $collection->getIcons();
Collection::optimize($data);
```

#### deOptimize()

Opposite of the previous function. It converts optimized JSON data into full JSON data, making it easy to retrieve data for each icon.

Function parameters:

- `$data` - JSON array to de-optimize.

```php
$data = json_decode(file_get_contents('ant-design.json'), true);
Collection::deOptimize($data);
```

## SVG class

The `SVG` class generates code for the icon.

To include it use this code:

```php
use \Iconify\JSONTools\SVG;
```

### Initializing class instance

```php
$svg = new SVG($data);
```

### Getting SVG icon

The `SVG` class has one function: `getSVG()`. It returns SVG as a string.

```php
use \Iconify\JSONTools\Collection;
use \Iconify\JSONTools\SVG;

$collection = new Collection();
$collection->loadIconifyCollection('mdi');
$svg = new SVG($collection->getIconData('home'));
echo $svg->getSVG();
```

`getSVG()` has one parameter: custom properties array. Possible array keys:

- `inline` - if true or "true" or "1" (string or boolean), code will include `vertical-align` style, making it behave like a glyph. See [inline vs block article](https://iconify.design/docs/inline-vs-block/).
- `width`, `height` - dimensions of icon. If only one attribute is set, another attribute will be set using icon's width/height ratio. Value can be string (such as "1em", "24px" or number). If value is "auto", icon's original dimensions will be used. If both width and height are not set, height defaults to "1em".
- `hFlip`, `vFlip` - if true or "true" or "1" (string or boolean), icon will be flipped horizontally and/or vertically.
- `flip` - alternative to "hFlip" and "vFlip", string. Value can be "horizontal", "vertical" or "horizontal,vertical"
- `rotate` - rotation. Value can be in degrees "90deg" (only 90, 180 and 270 rotations are available), percentages "25%" (25%, 50% and 75% are aliases of 90deg, 180deg and 270deg) or number 1-3 (1 - 90deg, 2 - 180deg, 3 - 270deg).
- `align` - alignment. This is useful if you have custom width and height set. Unlike other images, SVG keep aspect ratio (unless stated otherwise) when scaled. Value is comma or space separated string with possible strings (example: "left,top,crop"):
  - `left`, `right`, `center` - horizontal alignment
  - `top`, `middle`, `bottom` - vertical alignment
  - `crop` - crop parts that go outside of boundaries
  - `meet` - scale icon down to fit entire icon (opposite of crop)
- `color` - custom color string to replace currentColor. This is useful when using icon as background image because background image cannot use currentColor
- `box` - if true or "true" or "1" (string or boolean), icon will include extra rectangle matching its view box. This is useful to export icon to editor making icon easier to scale or place into its position in sketch because often editors ignore viewBox.

```php
$svg->getSVG([
    'height' => '24px'
]);
$svg->getSVG([
    'height' => '24px',
    'width' => '24px',
    'align' => 'center,middle,meet',
    'color' => '#ff8000',
    'rotate' => '90deg', // same as "'rotate' =>  1" or "'rotate' => '25%'"
    'flip' => 'horizontal', // same as "'hFlip' => true"
    'box' => true
]);
$svg->getSVG([
    'height' => 'auto' // height and width will be set from viewBox attribute, using original icon's dimensions
]);
```

## License

The library is released with MIT license.

© 2018 - 2020 Vjacheslav Trushkin
