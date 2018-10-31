<?php

use \PHPUnit\Framework\TestCase;
use \Iconify\JSONTools\Collection;

final class CollectionLoadJSONTest extends TestCase
{
    // Load collection with prefix
    public function testWithPrefix()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'baz90'    => [
                    'parent'    => 'baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $this->assertTrue($collection->loadJSON($data));

        $this->assertEquals('foo', $collection->prefix());

        $items = $collection->getIcons();
        $this->assertEquals($data['icons']['bar'], $items['icons']['bar']);
    }

    // Same as above, but prefix is missing
    public function testMissingPrefix()
    {
        $data = [
            'icons' => [
                'bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'baz90'    => [
                    'parent'    => 'baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $this->assertFalse($collection->loadJSON($data));

        $this->assertFalse($collection->prefix());
        $this->assertNull($collection->getIcons());
    }

    // Set prefix
    public function testWithoutPrefix()
    {
        $data = [
            'icons' => [
                'test-foo-bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'test-foo-baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'test-foo-baz90'    => [
                    'parent'    => 'test-foo-baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $collection->loadJSON($data, 'test');

        $this->assertEquals('test', $collection->prefix());

        $items = $collection->getIcons();
        $this->assertEquals(['foo-bar', 'foo-baz'], array_keys($items['icons']));
    }

    // Set wrong prefix
    public function testWithWrongPrefix()
    {
        $data = [
            'icons' => [
                'test-foo-bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'test-foo-baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'test-foo-baz90'    => [
                    'parent'    => 'test-foo-baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        // icon names must have : after prefix that has -
        $this->assertFalse($collection->loadJSON($data, 'test-foo'));
        $this->assertFalse($collection->prefix());
    }

    // Detect prefix
    public function testWithDetectablePrefix()
    {
        $data = [
            'icons' => [
                'test-foo-bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'test-foo-baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'test-foo-baz90'    => [
                    'parent'    => 'test-foo-baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $this->assertTrue($collection->loadJSON($data));

        $this->assertEquals('test', $collection->prefix());
    }

    // Prefix separated with :
    public function testWithDetectablePrefix2()
    {
        $data = [
            'icons' => [
                'test-foo:bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'test-foo:baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'test-foo:baz90'    => [
                    'parent'    => 'test-foo:baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $this->assertTrue($collection->loadJSON($data));

        $this->assertEquals('test-foo', $collection->prefix());
    }

    // Mismatched prefix
    public function testMismatchedPrefix()
    {
        $data = [
            'icons' => [
                'foo-bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                // Prefix is different than in previous icon
                'bar-baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ]
        ];

        $collection = new Collection();
        $this->assertFalse($collection->loadJSON($data));

        $this->assertFalse($collection->prefix());
    }

    // Mismatched alias
    public function testMismatchedAlias()
    {
        $data = [
            'icons' => [
                'foo-bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'foo-baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                // Prefix is different
                'foo2-baz90'    => [
                    'parent'    => 'foo-baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $this->assertFalse($collection->loadJSON($data));

        $this->assertFalse($collection->prefix());
    }

    // Mismatched parent item
    public function testMismatchedParentItem()
    {
        $data = [
            'icons' => [
                'foo-bar:test' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'foo-bar:baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'foo-bar:baz90'    => [
                    'parent'    => 'foo-baz', // Invalid parent icon
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $this->assertFalse($collection->loadJSON($data));

        $this->assertFalse($collection->prefix());
    }

    // Mismatched partial prefix
    public function testMismatchedPartialPrefix()
    {
        $data = [
            'icons' => [
                'foo-bar:test' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'foo-bar:baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                // Prefix is "foo", not "foo-bar"
                'foo-bar-baz90'    => [
                    'parent'    => 'foo-bar:baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $this->assertFalse($collection->loadJSON($data));

        $this->assertFalse($collection->prefix());
    }

    // De-optimize extra stats
    public function testOptimizedCollection()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'bar' => [
                    'body'  => '<bar />',
                    'height'    => 20
                ],
                'baz' => [
                    'body'  => '<baz />'
                ]
            ],
            'aliases'   => [
                'baz90'    => [
                    'parent'    => 'baz',
                    'rotate'    => 1
                ]
            ],
            'width' => 30,
            'height'    => 40
        ];

        $collection = new Collection();
        $this->assertTrue($collection->loadJSON($data));

        $this->assertEquals('foo', $collection->prefix());

        $items = $collection->getIcons();
        $this->assertEquals($items['icons']['bar'], [
            'body'  => '<bar />',
            'height'    => 20,
            'width' => 30
        ]);
        $this->assertEquals($items['icons']['baz'], [
            'body'  => '<baz />',
            'height'    => 40,
            'width' => 30
        ]);
    }
}


