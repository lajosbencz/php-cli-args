<?php

use LajosBencz\CliArgs;
use PHPUnit\Framework\TestCase;

class CliArgsTest extends TestCase
{
    public function testBasic()
    {
        $args = new CliArgs('-f1 -f2 --o1 x --o2 y foo bar');

        $this->assertEquals(true, $args->hasFlag('f1'));
        $this->assertEquals(true, $args->hasFlag('f2'));
        $this->assertEquals(false, $args->hasFlag('f3'));

        $this->assertEquals('x', $args->getOption('o1'));
        $this->assertEquals('y', $args->getOption('o2'));
        $this->assertEquals(null, $args->getOption('o3'));
        $this->assertEquals('o3', $args->getOption('o3', 'o3'));
        $this->assertEquals(['foo', 'bar'], $args->getArguments());
    }
}
