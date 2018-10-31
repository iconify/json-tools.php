# Iconify JSON tools

This library is used to manipulate JSON icon collections.

Library is available for PHP and Node.js, code in both versions is almost identical.

## Installation

To install library run this command:

```
composer require iconify/json-tools
```

There are two classes in this package: Collection and SVG

## Collection class

Collection class represents JSON collection.

To include it use this code:

```
use \Iconify\JSONTools\Collection;
```

What can Collection class do?
* Read and write JSON collections
* Add, remove, list icons in collection
* Retrieve icon data
* Create icon bundles for Iconify icon sets

### Initializing class instance

There are two ways to create instance: with prefix and without prefix. If you are going to load collection from JSON
file, you can initialize collection without prefix because load functions use prefix from JSON file. If you are going
to add icons manually using addIcon() or addAlias() functions, you should initialize collection with prefix.

```
$collection = new Collection();
```
```
$collectionWithPrefix = new Collection('custom-icons');
```

### Loading JSON collection

There are several functions to load JSON collection:
* loadFromFile() - loads collection
* loadJSON() - loads JSON data from string or array
* loadIconifyCollection() - loads Iconify collection from iconify/json repository

#### loadFromFile()

This function loads collection from JSON file.

Parameters:
* $file - file to load from

Returns:
* boolean - true on success, false on failure

```
$collection = new Collection();
if (!$collection->loadFromFile('json/custom-icons.json')) {
    throw new \Exception('Failed to load custom-icons.json');
}
```

#### loadJSON()

This function loads collection from string or array.

Parameters:
* $data - JSON string or array
* $prefix - optional prefix if JSON file doesn't include one

Returns:
* boolean - true on success, false on failure

```
$collection = new Collection();
// Use this if collection has prefix
if (!$collection->loadJSON($data)) {
    throw new \Exception('Failed to load JSON data');
}
```

```
$collection = new Collection();
// Use this if collection is missing prefix
if (!$collection->loadJSON($data, 'custom-icons')) {
    throw new \Exception('Failed to load JSON data');
}
```

#### loadIconifyCollection()

This function loads Iconify collection from [iconify/json](https://github.com/iconify-design/collections-json) repository.

Parameters:
* $name - name of collection
* $dir - optional root directory of Iconify collection. Use this option if you want to load Iconify collection from custom directory instead of iconify/json repository

Returns:
* boolean - true on success, false on failure

```
$collection = new Collection();
if (!$collection->loadIconifyCollection('mdi')) {
    throw new \Exception('Failed to load Material Design Icons');
}
```

### Caching collections

PHP loads collections on every page load, so it makes sense not to parse same data many times. This is why PHP version of
package has caching functions.

#### loadFromCache()

This function loads collection from cache.

Parameters:
* $filename - cache file
* $time - filemtime() of original JSON file. This is used to invalidate cache if JSON file has been updated

Returns:
* boolean - true on success, false on failure

```
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

Same as loadFromCache(), but instead of loading collection it saves collection array. See code example above.

saveCache() does not return anything.

### Getting icons data

There are several functions that retrieve icon data from collection:
* getIconData() - returns full data for one icon. It can be used to generate SVG (see SVG class documentation below).
* getIcons() - returns JSON data for icons, which can be used to import to another JSON collection or can be added to Iconify using Iconify.addCollection()
* scriptify() - returns JavaScript bundle file that can be used to load icons in browser with Iconify.

#### getIconData()

This function returns JSON data for one icon. It returns full data, including optional fields, so result can be used to generate SVG.

Parameters:
* $name - icon name

Returns:
* array - icon data

```
$data = collection->getIconData('arrow-left');
$svg = new SVG($data);
echo $svg->getSVG();
```

#### getIcons()

This function returns JSON data for selected icons. If used without parameters, it returns JSON data for entire collection.

Parameters:
* $icons - array of icons
* $optimize - optional boolean parameter. If true, common values will be moved to root of array, making JSON data smaller. Set to true when using script during development to make JSON files smaller, set to false to avoid unnecessary overhead if using script at run time

```
$data = collection->getIcons(['arrow-left', 'arrow-right', 'home'], true);
file_put_contents('bundle.json', json_encode($data));
```

This function can also be used to copy collection:

```
$data = collection->getIcons();
$newCollection = new Collection();
$newCollection->loadJSON($data);
```

#### scriptify()

This is similar to getIcons(), but it generates JavaScript file instead of JSON data and parameters are passed as one array.

Parameters:
* $options - options array

Returns:
* string - JavaScript code you can bundle with your scripts

Options array keys:
* icons - array of icons to retrieve. If not set or null, all icons will be retrieved
* optimize - boolean. If set to true, JSON data will be optimized to make output smaller
* pretty - boolean. If set to true, JSON data will include white spaces that make output easy to read
* callback - string. JavaScript callback to wrap JSON data in. By default it will be "SimpleSVG.addIcons" (SimpleSVG is alias of Iconify object for backwards compatibility with old versions of Iconify script)

Code to create bundle with selected icons from one collection (repeat same code for different collections to make bundle of all icons used on website):
```
$collection = new Collection();
if (!$collection->loadIconifyCollection('mdi')) {
    throw new \Exception('Cannot load Material Design Icons');
}
$code = $collection->scriptify({
    'icons' => ['account', 'account-alert', 'home', 'book-open'],
    'pretty' => false,
    'optimize' => true
});
file_put_contents('bundle-mdi.js', $code);
```

### Adding/removing icons

#### addIcon()

This function adds new icon to collection.

Parameters:
* $name - icon name
* $data - icon data

Returns:
* boolean - true on success, false on failure. Failure is possible if icon is missing 'body' property of if collection has no prefix

```
$collection = new Collection('custom-icons');
$collection->addIcon('arrow', [
    'body' => '<path d="" />',
    'width' => 24,
    'height' => 24
]);
```

### addAlias()

This function adds alias to collection.

Parameters:
* $name - alias name
* $parent - parent icon or alias name
* $data - optional data that should override parent icon's data

Returns:
* boolean - true on success, false on failure. Failure is possible if parent icon is missing

```
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

### removeIcon()

Removes icon or alias from collection.

Parameters:
* $name - icon name
* $checkAliases - if true, collection will be checked for aliases that use removed icon as parent icon and those aliases will be removed too. Set to false if you know for sure there are no aliases referencing this icon, otherwise set to true

```
$collection = new Collection();
$collection->loadIconifyCollection('fa-regular');
$collection->removeIcon('home');
```

### iconExists()

Checks if icon or alias exists.

Parameters:
* $name - icon name

Returns:
* boolean - true or false

```
if (!$collection->iconExists('home')) {
    throw new \Exception('Missing "home" icon!');
}
```

### listIcons()

Lists all icons in collection

Parameters:
* $includeAliases - set to true to include aliases in result

Returns:
* array - list of icons

```
$collection = new Collection();
$collection->loadIconifyCollection('vaadin');
echo 'Available icons in vaadin collection: ', implode(', ', $collection->listIcons(true)), "\n";
```

### Other functions

#### prefix()

Returns collection prefix, false if collection has no prefix.

Returns:
* string|boolean - Prefix, false on error

```
$prefix = $collection->prefix();
```

#### findIconifyCollection()

This function locates Iconify collection from [iconify/json](https://github.com/iconify-design/collections-json) repository.

Parameters:
* $name - Name of collection.
* $dir - Optional root directory of Iconify collection. Use this option if you want to load Iconify collection from custom directory instead of iconify/json repository.

Returns:
* string - location of file

```
echo 'fa.json can be found at ', $collection->findIconifyCollection('fa'), "\n";
```

#### optimize()

Optimize is static function that optimizes JSON data. It modifies array passed by reference in first parameter.

Parameters:
* $data - JSON data to optimize, passed by reference
* $props - optional array of properties to optimize. If not set, default properties list will be used

```
$data = $collection->getIcons();
Collection::optimize($data);
```

#### deOptimize()

Opposite of previous function. It converts optimized JSON data into full JSON data, making it easy to retrieve data for each icon.

Parameters:
* $data - JSON array to de-optimize

```
$data = json_decode(file_get_contents('ant-design.json'), true);
Collection::deOptimize($data);
```

## SVG class

SVG class generates SVG code for icon.

To include it use this code:

```
use \Iconify\JSONTools\SVG;
```

### Initializing class instance

```
$svg = new SVG($data);
```

### Getting SVG icon

SVG class has only one function: getSVG(). It returns SVG as string.

```
use \Iconify\JSONTools\Collection;
use \Iconify\JSONTools\SVG;

$collection = new Collection();
$collection->loadIconifyCollection('mdi');
$svg = new SVG($collection->getIconData('home'));
echo $svg->getSVG();
```

getSVG() has one parameter: custom properties array. Possible array keys:
* inline - if true or "true" or "1" (string or boolean), code will include vertical-align style, making it behave like glyph. See [inline vs block article](https://iconify.design/docs/inline-vs-block/).
* width, height - dimensions of icon. If only one attribute is set, another attribute will be set using icon's width/height ratio. Value can be string (such as "1em", "24px" or number). If value is "auto", icon's original dimensions will be used. If both width and height are not set, height defaults to "1em".
* hFlip, vFlip - if true or "true" or "1" (string or boolean), icon will be flipped horizontally and/or vertically.
* flip - alternative to "hFlip" and "vFlip", string. Value can be "horizontal", "vertical" or "horizontal,vertical"
* rotate - rotation. Value can be in degrees "90deg" (only 90, 180 and 270 rotations are available), percentages "25%" (25%, 50% and 75% are aliases of 90deg, 180deg and 270deg) or number 1-3 (1 - 90deg, 2 - 180deg, 3 - 270deg).
* align - alignment. This is useful if you have custom width and height set. Unlike other images, SVG keep aspect ratio (unless stated otherwise) when scaled. Value is comma or space separated string with possible strings (example: "left,top,crop"):
** left, right, center - horizontal alignment
** top, middle, bottom - vertical alignment
** crop - crop parts that go outside of boundaries
** meet - scale icon down to fit entire icon (opposite of crop)
* color - custom color string to replace currentColor. This is useful when using icon as background image because background image cannot use currentColor
* box - if true or "true" or "1" (string or boolean), icon will include extra rectangle matching its view box. This is useful to export icon to editor making icon easier to scale or place into its position in sketch because often editors ignore viewBox.

```
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
});
$svg->getSVG([
    'height' => 'auto' // height and width will be set from viewBox attribute, using original icon's dimensions
]);
```

## License

Library is released with MIT license.

© 2018 Vjacheslav Trushkin
