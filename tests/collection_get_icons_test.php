<?php

use \PHPUnit\Framework\TestCase;
use \Iconify\JSONTools\Collection;

final class CollectionGetIconsTest extends TestCase
{
    public function testFontAwesome()
    {
        $collection = new Collection();
        $this->assertTrue($collection->loadFromFile(__DIR__ . '/fixtures/test1.json'));

        // Full set
        $items = $collection->getIcons();
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-up', 'arrow-left', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description'], array_keys($items['icons']));
        $this->assertEquals(['arrow-circle-right', 'arrow-down', 'arrow-right'], array_keys($items['aliases']));

        // Get only "arrows" and "asterisk"
        $items = $collection->getIcons(['arrows', 'asterisk']);
        $this->assertEquals([
            'prefix'    => 'fa',
            'icons' => [
                'arrows'    => [
                    'body' => '<path d="M1792 896q0 26-19 45l-256 256q-19 19-45 19t-45-19-19-45v-128h-384v384h128q26 0 45 19t19 45-19 45l-256 256q-19 19-45 19t-45-19l-256-256q-19-19-19-45t19-45 45-19h128v-384H384v128q0 26-19 45t-45 19-45-19L19 941Q0 922 0 896t19-45l256-256q19-19 45-19t45 19 19 45v128h384V384H640q-26 0-45-19t-19-45 19-45L851 19q19-19 45-19t45 19l256 256q19 19 19 45t-19 45-45 19h-128v384h384V640q0-26 19-45t45-19 45 19l256 256q19 19 19 45z" fill="currentColor"/>',
                    'width' => 1792,
                    'height' => 1792,
                    'inlineTop' => 0,
                    'inlineHeight' => 1792,
                    'verticalAlign' => -0.143,
                ],
                'asterisk'  => [
                    'body' => '<path d="M1386 922q46 26 59.5 77.5T1433 1097l-64 110q-26 46-77.5 59.5T1194 1254l-266-153v307q0 52-38 90t-90 38H672q-52 0-90-38t-38-90v-307l-266 153q-46 26-97.5 12.5T103 1207l-64-110q-26-46-12.5-97.5T86 922l266-154L86 614q-46-26-59.5-77.5T39 439l64-110q26-46 77.5-59.5T278 282l266 153V128q0-52 38-90t90-38h128q52 0 90 38t38 90v307l266-153q46-26 97.5-12.5T1369 329l64 110q26 46 12.5 97.5T1386 614l-266 154z" fill="currentColor"/>',
                    'width' => 1472,
                    'height' => 1536,
                    'inlineHeight' => 1792,
                    'inlineTop' => -128,
                    'verticalAlign' => -0.143,
                ]
            ],
            'aliases'   => []
        ], $items);

        // Same, but optimized
        $this->assertTrue($collection->loadFromFile(__DIR__ . '/fixtures/test1-optimized.json'));
        $items = $collection->getIcons(['arrows', 'asterisk']);
        $this->assertEquals([
            'prefix'    => 'fa',
            'icons' => [
                'arrows'    => [
                    'body' => '<path d="M1792 896q0 26-19 45l-256 256q-19 19-45 19t-45-19-19-45v-128h-384v384h128q26 0 45 19t19 45-19 45l-256 256q-19 19-45 19t-45-19l-256-256q-19-19-19-45t19-45 45-19h128v-384H384v128q0 26-19 45t-45 19-45-19L19 941Q0 922 0 896t19-45l256-256q19-19 45-19t45 19 19 45v128h384V384H640q-26 0-45-19t-19-45 19-45L851 19q19-19 45-19t45 19l256 256q19 19 19 45t-19 45-45 19h-128v384h384V640q0-26 19-45t45-19 45 19l256 256q19 19 19 45z" fill="currentColor"/>',
                    'width' => 1792,
                    'height' => 1792,
                    'inlineTop' => 0,
                ],
                'asterisk'  => [
                    'body' => '<path d="M1386 922q46 26 59.5 77.5T1433 1097l-64 110q-26 46-77.5 59.5T1194 1254l-266-153v307q0 52-38 90t-90 38H672q-52 0-90-38t-38-90v-307l-266 153q-46 26-97.5 12.5T103 1207l-64-110q-26-46-12.5-97.5T86 922l266-154L86 614q-46-26-59.5-77.5T39 439l64-110q26-46 77.5-59.5T278 282l266 153V128q0-52 38-90t90-38h128q52 0 90 38t38 90v307l266-153q46-26 97.5-12.5T1369 329l64 110q26 46 12.5 97.5T1386 614l-266 154z" fill="currentColor"/>',
                    'width' => 1472,
                ]
            ],
            'aliases' => [],
            'width' => 1536,
            'height' => 1536,
            'inlineHeight' => 1792,
            'verticalAlign' => -0.143,
            'inlineTop' => -128,
        ], $items);

        // Get icons that do not exist
        $items = $collection->getIcons(['foo', 'bar']);
        $this->assertEquals([
            'prefix'    => 'fa',
            'icons' => [],
            'aliases'   => [],
            'width' => 1536,
            'height' => 1536,
            'inlineHeight' => 1792,
            'verticalAlign' => -0.143,
            'inlineTop' => -128,
        ], $items);

        // Alias and item that does not exist
        $items = $collection->getIcons(['missing', 'arrow-right', 'whatever']);
        $this->assertEquals([
            'prefix'    => 'fa',
            'icons' => [
                'arrow-left' => [
                    'body' => '<path d="M1472 736v128q0 53-32.5 90.5T1355 992H651l293 294q38 36 38 90t-38 90l-75 76q-37 37-90 37-52 0-91-37L37 890Q0 853 0 800q0-52 37-91L688 59q38-38 91-38 52 0 90 38l75 74q38 38 38 91t-38 91L651 608h704q52 0 84.5 37.5T1472 736z" fill="currentColor"/>',
                    'width' => 1472,
                    'height' => 1600,
                    'inlineTop' => -160,
                ]
            ],
            'aliases'   => [
                'arrow-right' => [
                    'parent' => 'arrow-left',
                    'hFlip' => true,
                ]
            ],
            'width' => 1536,
            'height' => 1536,
            'inlineHeight' => 1792,
            'verticalAlign' => -0.143,
            'inlineTop' => -128
        ], $items);

        // Alias by character
        $items = $collection->getIcons(['missing', 'f061', 'whatever']);
        $this->assertEquals([
            'prefix'    => 'fa',
            'icons' => [
                'arrow-left' => [
                    'body' => '<path d="M1472 736v128q0 53-32.5 90.5T1355 992H651l293 294q38 36 38 90t-38 90l-75 76q-37 37-90 37-52 0-91-37L37 890Q0 853 0 800q0-52 37-91L688 59q38-38 91-38 52 0 90 38l75 74q38 38 38 91t-38 91L651 608h704q52 0 84.5 37.5T1472 736z" fill="currentColor"/>',
                    'width' => 1472,
                    'height' => 1600,
                    'inlineTop' => -160,
                ]
            ],
            'aliases'   => [
                'arrow-right' => [
                    'parent' => 'arrow-left',
                    'hFlip' => true,
                ],
                'f061'  => [
                    'parent' => 'arrow-right'
                ]
            ],
            'width' => 1536,
            'height' => 1536,
            'inlineHeight' => 1792,
            'verticalAlign' => -0.143,
            'inlineTop' => -128
        ], $items);
    }
}


