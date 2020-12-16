<?php

use \PHPUnit\Framework\TestCase;
use \Iconify\JSONTools\Collection;

final class CollectionRemoveIconTest extends TestCase
{
    public function testIcons()
    {
        $collection = new Collection();
        $this->assertTrue($collection->loadFromFile(__DIR__ . '/fixtures/test1.json'));

        // Check original icons list without aliases
        // This test also tests listIcons() and iconExists()
        $icons = $collection->listIcons(false);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-up', 'arrow-left', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description'], $icons);

        // Check original icons list with aliases
        $icons = $collection->listIcons(true);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-up', 'arrow-left', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description', 'arrow-circle-right', 'arrow-down', 'arrow-right'], $icons);

        // Test removal of 'arrows'
        $this->assertTrue($collection->iconExists('arrows'));
        $collection->removeIcon('arrows');
        $this->assertFalse($collection->iconExists('arrows'));

        $icons = $collection->listIcons(false);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-up', 'arrow-left', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description'], $icons);

        // Test removal of 'arrow-up' without checking aliases
        $this->assertTrue($collection->iconExists('arrow-up'));
        $collection->removeIcon('arrow-up', false);
        $this->assertFalse($collection->iconExists('arrow-up'));

        $icons = $collection->listIcons(true);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-left', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description', 'arrow-circle-right', 'arrow-down', 'arrow-right'], $icons);

        // Test if 'arrow-down' exists and try to get its data
        $this->assertTrue($collection->iconExists('arrow-down'));
        $this->assertNull($collection->getIconData('arrow-down'));

        // Test removal of 'arrow-left' and its aliases
        $this->assertTrue($collection->iconExists('arrow-left'));
        $this->assertTrue($collection->iconExists('arrow-right'));
        $collection->removeIcon('arrow-left');
        $this->assertFalse($collection->iconExists('arrow-left'));
        $this->assertFalse($collection->iconExists('arrow-right'));

        $icons = $collection->listIcons(true);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description', 'arrow-circle-right', 'arrow-down'], $icons);

        // Remove icon that does not exist
        $collection->removeIcon('foo');
        $icons = $collection->listIcons(true);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description', 'arrow-circle-right', 'arrow-down'], $icons);
    }

    public function testNoAliases()
    {
        $json = file_get_contents(__DIR__ . '/fixtures/test1.json');
        $json = json_decode($json, true);
        unset ($json['aliases']);

        $collection = new Collection();
        $collection->loadJSON($json);

        // Check original icons list without aliases
        // This test also tests listIcons() and iconExists()
        $icons = $collection->listIcons(false);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-up', 'arrow-left', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description'], $icons);

        // Check with aliases
        $icons = $collection->listIcons(true);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-up', 'arrow-left', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description'], $icons);

        // Remove 'at'
        $this->assertTrue($collection->iconExists('at'));
        $collection->removeIcon('at');
        $this->assertFalse($collection->iconExists('at'));

        $icons = $collection->listIcons(true);
        $this->assertEquals(['arrow-circle-left', 'arrow-circle-up', 'arrow-up', 'arrow-left', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'assistive-listening-systems', 'asterisk', 'audio-description'], $icons);

        // Get JSON. It should match original without 'at' icon
        unset ($json['icons']['at']);
        $data = $collection->getIcons();
        $this->assertEquals($json, $data);
    }
}
