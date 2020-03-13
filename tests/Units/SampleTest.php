<?php

namespace Test\Units;


require_once  __DIR__."../../vendor/autoload.php";


use Tests\TestCase;

class SampleTest extends TestCase {

  function testTest(){
    $this->assertEquals(1,1);
  }
}