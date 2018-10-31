<?php

use \PHPUnit\Framework\TestCase;
use \Iconify\JSONTools\Collection;

final class CollectionLoadFileTest extends TestCase
{
    // Load collection with prefix and cache
    public function testFontAwesome()
    {
        $filename = __DIR__ . '/fixtures/test1.json';

        $collection = new Collection();
        $this->assertTrue($collection->loadFromFile($filename));

        $this->assertEquals('fa', $collection->prefix());

        $items = $collection->getIcons();
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-up', 'arrow-left', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description'], array_keys($items['icons']));

        $icon = $items['icons']['arrow-circle-left'];
        $this->assertEquals([
            'body' => '<path d="M1280 832V704q0-26-19-45t-45-19H714l189-189q19-19 19-45t-19-45l-91-91q-18-18-45-18t-45 18L360 632l-91 91q-18 18-18 45t18 45l91 91 362 362q18 18 45 18t45-18l91-91q18-18 18-45t-18-45L714 896h502q26 0 45-19t19-45zm256-64q0 209-103 385.5T1153.5 1433 768 1536t-385.5-103T103 1153.5 0 768t103-385.5T382.5 103 768 0t385.5 103T1433 382.5 1536 768z" fill="currentColor"/>',
            'width' => 1536,
            'height' => 1536,
            'inlineHeight' => 1792,
            'inlineTop' => -128,
            'verticalAlign' => -0.143,
        ], $icon);

        $icon = $items['aliases']['arrow-right'];
        $this->assertEquals([
            'parent' => 'arrow-left',
            'hFlip' => true,
        ], $icon);

        // Save to cache for next test and also test cache storage
        $cacheFile = __DIR__ . '/cache/cache-test1.php';
        @unlink($cacheFile);

        $time = filemtime($filename);
        $collection->saveCache($cacheFile, $time);

        // Check if file exists
        $this->assertTrue(file_exists($cacheFile));

        // Load file with wrong time
        $collection2 = new Collection();
        $collection2->loadFromCache($cacheFile, time());
        $this->assertFalse($collection2->prefix());

        // Load file with correct time
        $collection2->loadFromCache($cacheFile, $time);
        $this->assertEquals($collection->prefix(), $collection2->prefix());

        // Compare items
        $items2 = $collection2->getIcons();
        $this->assertEquals($items, $items2);

        // Test loadFromFile with cache
        // To make sure it is loaded from cache, modify cache
        $collection2->removeIcon('arrows', true);
        $collection2->saveCache($cacheFile, $time);

        // Make sure cache was modified
        $collection3 = new Collection();
        $collection3->loadFromCache($cacheFile, $time);
        $this->assertEquals('fa', $collection3->prefix());
        $this->assertEquals($collection2->getIcons(), $collection3->getIcons());

        // Load file from both file and cache. It should be loaded from cache
        $collection4 = new Collection();
        $collection4->loadFromFile($filename, $cacheFile);
        $this->assertEquals('fa', $collection4->prefix());
        $this->assertNotEquals($items, $collection4->getIcons());
        $this->assertEquals($collection2->getIcons(), $collection4->getIcons());

        @unlink($cacheFile);
    }

    public function testFontAwesome5()
    {
        $collection = new Collection();
        $this->assertTrue($collection->loadIconifyCollection('fa-regular'));
        $this->assertEquals('fa-regular', $collection->prefix());
    }
}

