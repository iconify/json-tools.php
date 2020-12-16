<?php

use \PHPUnit\Framework\TestCase;
use \Iconify\JSONTools\Collection;

final class CollectionGetIconTest extends TestCase
{
    public function testIcons()
    {
        $collection = new Collection();
        $this->assertTrue($collection->loadFromFile(__DIR__ . '/fixtures/test1-optimized.json'));

        // Test "fa-arrows"
        $icon = $collection->getIconData('arrows');
        $this->assertEquals([
            // original data from json
            'body'  => '<path d="M1792 896q0 26-19 45l-256 256q-19 19-45 19t-45-19-19-45v-128h-384v384h128q26 0 45 19t19 45-19 45l-256 256q-19 19-45 19t-45-19l-256-256q-19-19-19-45t19-45 45-19h128v-384H384v128q0 26-19 45t-45 19-45-19L19 941Q0 922 0 896t19-45l256-256q19-19 45-19t45 19 19 45v128h384V384H640q-26 0-45-19t-19-45 19-45L851 19q19-19 45-19t45 19l256 256q19 19 19 45t-19 45-45 19h-128v384h384V640q0-26 19-45t45-19 45 19l256 256q19 19 19 45z" fill="currentColor"/>',
            'width' => 1792,
            'height'    => 1792,
            'inlineTop' => 0,
            'inlineHeight'  => 1792,
            'verticalAlign' => -0.143,
            // missing attributes
            'hFlip' => false,
            'vFlip' => false,
            'rotate'    => 0,
            'left'  => 0,
            'top'   => 0,
        ], $icon);

        // Test missing icon
        $icon = $collection->getIconData('foo');
        $this->assertNull($icon);

        // Test alias
        $expected = [
            // data from alias
            'hFlip' => true,
            'parent'    => 'arrow-circle-left',
            // data from parent icon
            'body'  => '<path d="M1280 832V704q0-26-19-45t-45-19H714l189-189q19-19 19-45t-19-45l-91-91q-18-18-45-18t-45 18L360 632l-91 91q-18 18-18 45t18 45l91 91 362 362q18 18 45 18t45-18l91-91q18-18 18-45t-18-45L714 896h502q26 0 45-19t19-45zm256-64q0 209-103 385.5T1153.5 1433 768 1536t-385.5-103T103 1153.5 0 768t103-385.5T382.5 103 768 0t385.5 103T1433 382.5 1536 768z" fill="currentColor"/>',
            'width' => 1536,
            'height'    => 1536,
            'inlineHeight'  => 1792,
            'inlineTop' => -128,
            'verticalAlign' => -0.143,
            // missing attributes
            'vFlip' => false,
            'rotate'    => 0,
            'left'  => 0,
            'top'   => 0
        ];

        $icon = $collection->getIconData('arrow-circle-right');
        $this->assertEquals($expected, $icon);

        // Test character
        $icon = $collection->getIconData('f0a9');
        $this->assertEquals($expected, $icon);
    }
}


