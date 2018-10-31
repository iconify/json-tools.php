<?php

use \Iconify\JSONTools\SVG;

class DimensionsTest extends \PHPUnit\Framework\TestCase {
    public function testNumbers()
    {
        $width = 48;
        $height = 36;

        // Get height knowing width
        $result = SVG::calculateDimension($width, $height / $width);
        $this->assertEquals($height, $result);

        // Get width knowing height
        $result = SVG::calculateDimension($height, $width / $height);
        $this->assertEquals($width, $result);

        // Get height for custom width
        $result = SVG::calculateDimension(24, $height / $width);
        $this->assertEquals(18, $result);

        $result = SVG::calculateDimension(30, $height / $width);
        $this->assertEquals(22.5, $result);

        $result = SVG::calculateDimension(99, $height / $width);
        $this->assertEquals(74.25, $result);

        // Get width for custom height
        $result = SVG::calculateDimension(18, $width / $height);
        $this->assertEquals(24, $result);

        $result = SVG::calculateDimension(74.25, $width / $height);
        $this->assertEquals(99, $result);

        // Test floating numbers
        $result = SVG::calculateDimension(16, 10 / 9);
        $this->assertEquals(17.78, $result);

        $result = SVG::calculateDimension(16, 10 / 9, 1000);
        $this->assertEquals(17.778, $result);
    }

    public function testStrings()
    {
        $width = 48;
        $height = 36;

        // Strings without units
        $result = SVG::calculateDimension('48', $height / $width);
        $this->assertEquals('36', $result);

        // Pixels
        $result = SVG::calculateDimension('48px', $height / $width);
        $this->assertEquals('36px', $result);

        // Percentages
        $result = SVG::calculateDimension('36%', $width / $height);
        $this->assertEquals('48%', $result);

        // em
        $result = SVG::calculateDimension('1em', $height / $width);
        $this->assertEquals('0.75em', $result);

        $result = SVG::calculateDimension('1em', $width / $height);
        $this->assertEquals('1.34em', $result);

        $result = SVG::calculateDimension('1em', $width / $height, 1000);
        $this->assertEquals('1.334em', $result);

        // custom units with space
        $result = SVG::calculateDimension('48 Whatever', $height / $width);
        $this->assertEquals('36 Whatever', $result);

        // numbers after unit should be parsed too
        $result = SVG::calculateDimension('48% + 5em', $height / $width);
        $this->assertEquals('36% + 3.75em', $result);

        // calc()
        $result = SVG::calculateDimension('calc(100% - 48px)', $height / $width);
        $this->assertEquals('calc(75% - 36px)', $result);

        // -webkit-calc()
        $result = SVG::calculateDimension('-webkit-calc(100% - 48px)', $height / $width);
        $this->assertEquals('-webkit-calc(75% - 36px)', $result);
    }

    public function testStringsWithoutUnits()
    {
        $width = 48;
        $height = 36;

        // invalid number
        $result = SVG::calculateDimension('-.', $height / $width);
        $this->assertEquals('-.', $result);

        // variable
        $result = SVG::calculateDimension('@width', $height / $width);
        $this->assertEquals('@width', $result);
    }
}
