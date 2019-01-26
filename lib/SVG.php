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

class SVG
{
    /**
     * @var string|false
     */
    protected $_item;

    /**
     * Attributes used for icon
     *
     * @var array
     */
    protected static $_iconAttributes = ['width', 'height', 'inline', 'hFlip', 'vFlip', 'flip', 'rotate', 'align', 'color', 'box'];

    /**
     * Constructor
     *
     * @param array $icon Icon data
     *  Use Collection->getIconData() to retrieve icon data
     */
    public function __construct($icon)
    {
        $this->_item = $icon;
    }

    /**
     * Get SVG attributes
     *
     * @param array $props Custom properties (same as query string in Iconify API)
     * @return array
     */
    public function getAttributes($props = [])
    {
        $item = $this->_item;

        // Set data
        $align = [
            'horizontal' => 'center',
            'vertical' => 'middle',
            'slice' => false,
        ];
        $transform = [
            'rotate' => $item['rotate'],
            'hFlip' => $item['hFlip'],
            'vFlip' => $item['vFlip']
        ];
        $style = [];

        $attributes = [];

        // Get width/height
        $inline = isset($props['inline']) ? ($props['inline'] === true ||  $props['inline'] === 'true' || $props['inline'] === '1') : false;
        $box = [
            'left' => $item['left'],
            'top' => $inline ? $item['inlineTop'] : $item['top'],
            'width' => $item['width'],
            'height' => $inline ? $item['inlineHeight'] : $item['height']
        ];

        // Transformations
        foreach(['hFlip', 'vFlip'] as $key) {
            if (isset($props[$key]) && ($props[$key] === true || $props[$key] === 'true' || $props[$key] === '1')) {
                $transform[$key] = !$transform[$key];
            }
        }
        if (isset($props['flip'])) {
            $values = preg_split('/[\s,]+/', strtolower($props['flip']));
            foreach ($values as $value) {
                switch ($value) {
                    case 'horizontal':
                        $transform['hFlip'] = !$transform['hFlip'];
                        break;

                    case 'vertical':
                        $transform['vFlip'] = !$transform['vFlip'];
                }
            }
        }
        if (isset($props['rotate'])) {
            $value = $props['rotate'];
            if (is_numeric($value)) {
                $transform['rotate'] += $value;
            } elseif (is_string($value)) {
                $units = preg_replace('/^-?[0-9.]*/', '', $value);
                if ($units === '') {
                    $transform['rotate'] += intval($value);
                } elseif ($units !== $value) {
                    $split = false;
                    switch ($units) {
                        case '%':
                            // 25% -> 1, 50% -> 2, ...
                            $split = 25;
                            break;

                        case 'deg':
                            // 90deg -> 1, 180deg -> 2, ...
                            $split = 90;
                    }
                    if ($split) {
                        $transform['rotate'] += round(intval(substr($value, 0, strlen($value) - strlen($units))) / $split);
                    }
                }
            }
        }

        // Apply transformations to box
        $transformations = [];
        if ($transform['hFlip']) {
            if ($transform['vFlip']) {
                $transform['rotate'] += 2;
            } else {
                // Horizontal flip
                $transformations[] = 'translate(' . ($box['width'] + $box['left']) . ' ' . (0 - $box['top']) . ')';
                $transformations[] = 'scale(-1 1)';
                $box['top'] = $box['left'] = 0;
            }
        } elseif ($transform['vFlip']) {
            // Vertical flip
            $transformations[] = 'translate(' . (0 - $box['left']) . ' ' . ($box['height'] + $box['top']) . ')';
            $transformations[] = 'scale(1 -1)';
            $box['top'] = $box['left'] = 0;
        }
        switch ($transform['rotate'] % 4) {
            case 1:
                // 90deg
                $tempValue = $box['height'] / 2 + $box['top'];
                array_unshift($transformations, 'rotate(90 ' . $tempValue . ' ' . $tempValue . ')');
                // swap width/height and x/y
                if ($box['left'] !== 0 || $box['top'] !== 0) {
                    $tempValue = $box['left'];
                    $box['left'] = $box['top'];
                    $box['top'] = $tempValue;
                }
                if ($box['width'] !== $box['height']) {
                    $tempValue = $box['width'];
                    $box['width'] = $box['height'];
                    $box['height'] = $tempValue;
                }
                break;

            case 2:
                // 180deg
                array_unshift($transformations, 'rotate(180 ' . ($box['width'] / 2 + $box['left']) . ' ' . ($box['height'] / 2 + $box['top']) . ')');
                break;

            case 3:
                // 270deg
                $tempValue = $box['width'] / 2 + $box['left'];
                array_unshift($transformations, 'rotate(-90 ' . $tempValue . ' ' . $tempValue . ')');
                // swap width/height and x/y
                if ($box['left'] !== 0 || $box['top'] !== 0) {
                    $tempValue = $box['left'];
                    $box['left'] = $box['top'];
                    $box['top'] = $tempValue;
                }
                if ($box['width'] !== $box['height']) {
                    $tempValue = $box['width'];
                    $box['width'] = $box['height'];
                    $box['height'] = $tempValue;
                }
                break;
        }

        // Calculate dimensions
        // Values for width/height: null = default, 'auto' = from svg, false = do not set
        // Default: if both values aren't set, height defaults to '1em', width is calculated from height
        $customWidth = isset($props['width']) ? $props['width'] : null;
        $customHeight = isset($props['height']) ? $props['height'] : null;

        if ($customWidth === null && $customHeight === null) {
            $customHeight = '1em';
        }
        if ($customWidth !== null && $customHeight !== null) {
            $width = $customWidth;
            $height = $customHeight;
        } elseif ($customWidth !== null) {
            $width = $customWidth;
            $height = self::calculateDimension($width, $box['height'] / $box['width']);
        } else {
            $height = $customHeight;
            $width = self::calculateDimension($height, $box['width'] / $box['height']);
        }

        if ($width !== false) {
            $attributes['width'] = $width === 'auto' ? $box['width'] : $width;
        }
        if ($height !== false) {
            $attributes['height'] = $height === 'auto' ? $box['height'] : $height;
        }

        // Add vertical-align for inline icon
        if ($inline && $item['verticalAlign'] !== 0) {
            $style['vertical-align'] = $item['verticalAlign'] . 'em';
        }

        // Check custom alignment
        if (isset($props['align'])) {
            $values = preg_split('/[\s,]+/', strtolower($props['align']));
            foreach ($values as $value) {
                switch ($value) {
                    case 'left':
                    case 'right':
                    case 'center':
                        $align['horizontal'] = $value;
                        break;

                    case 'top':
                    case 'bottom':
                    case 'middle':
                        $align['vertical'] = $value;
                        break;

                    case 'crop':
                        $align['slice'] = true;
                        break;

                    case 'meet':
                        $align['slice'] = false;
                }
            }
        }

        // Generate viewBox and preserveAspectRatio attributes
        $attributes['preserveAspectRatio'] = $this->_align($align);
        $attributes['viewBox'] = $box['left'] . ' ' . $box['top'] . ' ' . $box['width'] . ' ' . $box['height'];

        // Generate body
        $body = self::replaceIDs($item['body']);

        if (isset($props['color'])) {
            $body = str_replace('currentColor', $props['color'], $body);
        }
        if (count($transformations)) {
            $body = '<g transform="' . implode(' ', $transformations) . '">' . $body . '</g>';
        }
        if (isset($props['box']) && ($props['box'] === true || $props['box'] === 'true' || $props['box'] === '1')) {
            // Add transparent bounding box
            $body .= '<rect x="' . $box['left'] . '" y="' . $box['top'] . '" width="' . $box['width'] . '" height="' . $box['height'] . '" fill="rgba(0, 0, 0, 0)" />';
        }

        return [
            'attributes' => $attributes,
            'body' => $body,
            'style' => $style
        ];
    }

    /**
     * Generate SVG
     *
     * @param array $props Custom properties (same as query string in Iconify API)
     * @param bool $addExtra True if extra attributes should be added to SVG.
     * @return string
     */
    public function getSVG($props = [], $addExtra = false)
    {
        $attributes = self::splitAttributes($props);
        $data = $this->getAttributes($attributes['icon']);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"';

        // Add extra attributes
        if ($addExtra) {
            foreach ($attributes['node'] as $attr => $value) {
                $svg .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
            }
        }

        // Add SVG attributes
        foreach ($data['attributes'] as $attr => $value) {
            $svg .= ' ' . $attr . '="' . $value . '"';
        }

        // Add style with 360deg transformation to style to prevent subpixel rendering bug
        $svg .= ' style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);';
        foreach ($data['style'] as $attr => $value) {
            $svg .= ' ' . $attr . ': ' . $value . ';';
        }
        if (!empty($props) && isset($props['style'])) {
            $svg .= $props['style'];
        }
        $svg .= '">';

        $svg .= $data['body'] . '</svg>';

        return $svg;
    }

    /**
     * Get preserveAspectRatio attribute value
     *
     * @param array $align
     * @return string
     */
    protected function _align($align)
    {
        switch ($align['horizontal']) {
            case 'left':
                $result = 'xMin';
                break;

            case 'right':
                $result = 'xMax';
                break;

            default:
                $result = 'xMid';
        }
        switch ($align['vertical']) {
            case 'top':
                $result .= 'YMin';
                break;

            case 'bottom':
                $result .= 'YMax';
                break;

            default:
                $result .= 'YMid';
        }
        $result .= $align['slice'] === true ? ' slice' : ' meet';
        return $result;
    }

    public static function splitAttributes($props)
    {
        $result = [
            'icon' => [],
            'node' => []
        ];

        foreach ($props as $name => $value) {
            $result[in_array($name, self::$_iconAttributes) ? 'icon' : 'node'][$name] = $value;
        }

        return $result;
    }

    /**
     * Calculate one dimension based on width/height ratio
     *
     * @param mixed $size
     * @param float $ratio
     * @param int $precision
     * @return mixed
     */
    public static function calculateDimension($size, $ratio, $precision = 100)
    {
        if ($ratio == 1) {
            return $size;
        }

        if (is_numeric($size)) {
            return ceil($size * $ratio * $precision) / $precision;
        }

        if (!is_string($size)) {
            return $size;
        }

        // split code into sets of strings and numbers
        if (!preg_match_all('/-?[0-9.]*[0-9]+[0-9.]*/', $size, $matches, PREG_OFFSET_CAPTURE)) {
            return $size;
        }

        $result = '';
        $start = 0;

        foreach ($matches[0] as $match) {
            $offset = $match[1];
            $number = floatval($match[0]);
            if ($offset > $start) {
                $result .= substr($size, $start, $offset - $start);
            }
            $result .= strval(ceil($number * $ratio * $precision) / $precision);
            $start = $offset + strlen($match[0]);
        }
        $result .= substr($size, $start);

        return $result;
    }

    /**
     * Replace IDs in SVG body with unique IDs.
     * Fast replacement without parsing XML, assuming commonly used patterns.
     *
     * @param $body
     * @return string
     */
    public static function replaceIDs($body)
    {
        if (!preg_match_all('/\\sid="(\\S+)"/', $body, $matches) || !count($matches[0])) {
            return $body;
        }

        $ids = array_unique($matches[1]);
        $replacements = [];

        if (function_exists('random_bytes')) {
            try {
                $random = bin2hex(random_bytes(3));
            } catch (\Exception $e) {
                $random = dechex(mt_rand(0, 0x1000000));
            }
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $random = bin2hex(openssl_random_pseudo_bytes(3));
        } else {
            $random = dechex(mt_rand(0, 0x1000000));
        }

        $counter = 0;
        $prefix = 'IconifyId-' . dechex(time()) . '-' . $random . '-';
        foreach ($ids as $id) {
            $counter ++;
            $newID = $prefix . $counter;
            $replacements['="' . $id . '"'] = '="' . $newID . '"';
            $replacements['="#' . $id . '"'] = '="#' . $newID . '"';
            $replacements['(#' . $id . ')'] = '(#' . $newID . ')';
        }

        return strtr($body, $replacements);
    }
}
