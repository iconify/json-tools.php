<?php

use \PHPUnit\Framework\TestCase;
use \Iconify\JSONTools\Collection;

final class CollectionOptimizeTest extends TestCase
{
    // Test optimizing and de-optimizing FontAwesome JSON
    public function testOptimize()
    {
        // Get data from test1.json
        $original = file_get_contents(__DIR__ . '/fixtures/test1.json');
        $original = json_decode($original, true);

        // Optimize it
        $optimized = $original;
        Collection::optimize($optimized);

        $this->assertNotEquals($original, $optimized);

        // Test verticalAlign
        $this->assertEquals(-0.143, $optimized['verticalAlign']);
        $this->assertFalse(isset($optimized['icons']['audio-description']['verticalAlign']));
        $this->assertFalse(isset($original['verticalAlign'])); // Make sure $data was not changed

        // Test width
        $this->assertEquals(1536, $optimized['width']);
        $this->assertFalse(isset($optimized['icons']['arrow-circle-left']['width']));
        $this->assertTrue(isset($optimized['icons']['arrow-up']['width']));

        // Test if height is set and test each item
        $this->assertEquals(1536, $optimized['height']);
        foreach ($optimized['icons'] as $key => $item) {
            $this->assertTrue(!isset($item['height']) || $item['height'] !== 1536);
        }

        // De-optimize it
        $final = $optimized;
        Collection::deOptimize($final);

        $this->assertNotEquals($optimized, $final);
        $this->assertEquals($final, $original);
    }

    // Test optimization with missing property
    public function testOptimizeMissingProp()
    {
        $original = [
            'prefix'    => 'test',
            'icons' => [
                'foo'   => [
                    'body'  => '<path />',
                    'width' => 1024,
                    'height'    => 512,
                    'verticalAlign' => -0.125
                ],
                'bar'   => [
                    'body'  => '<path />',
                    'width' => 1024,
                    'height'    => 128,
                    'verticalAlign' => -0.15
                ],
                'baz'   => [
                    'body'  => '<path />',
                    // missing width
                    'height'    => 128,
                    'verticalAlign' => -0.12
                ]
            ]
        ];

        $optimized = $original;
        Collection::optimize($optimized);

        $this->assertEquals([
            'prefix'    => 'test',
            'icons' => [
                'foo'   => [
                    'body'  => '<path />',
                    'width' => 1024,
                    'height'    => 512,
                    'verticalAlign' => -0.125
                ],
                'bar'   => [
                    'body'  => '<path />',
                    'width' => 1024,
                    'verticalAlign' => -0.15
                ],
                'baz'   => [
                    'body'  => '<path />',
                    'verticalAlign' => -0.12
                ]
            ],
            'height'    => 128
        ], $optimized);

        $final = $optimized;
        Collection::deOptimize($final);

        $this->assertEquals($original, $final);
    }
}