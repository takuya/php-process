<?php

namespace Tests\Units;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessRedirectTest extends TestCase {
  
  public function testRedirectOutputToFileNameString() {
    $proc = new Process(['echo', 'HelloWorld']);
    $proc->setOutput('/dev/null');
    $this->assertEquals(["file", "/dev/null", "w+",], $proc->getOutput());
  }
  
  public function testRedirectErrorOutputToFileNameString() {
    $proc = new Process(['echo', 'HelloWorld']);
    $proc->setErrout('/dev/null');
    $this->assertEquals(["file", "/dev/null", "w+",], $proc->getErrout());
  }
  
  public function testRedirectOutputToStream() {
    $proc = new Process(['echo', 'HelloWorld']);
    $proc->setOutput(fopen('/dev/null', 'w+'));
    //
    $fd = $proc->getOutput();
    $this->assertEquals('/dev/null', stream_get_meta_data($fd)['uri']);
    $this->assertTrue( is_resource(  $fd ) );
  }
  public function testRedirectErrorOutputToStream() {
    $proc = new Process(['echo', 'HelloWorld']);
    $proc->setErrout(fopen('/dev/null', 'w+'));
    $fd = $proc->getErrout();
    $this->assertEquals('/dev/null', stream_get_meta_data($fd)['uri']);
    $this->assertTrue( is_resource(  $fd ) );
  }
  
  public function testRedirectInputAsString(){
    $proc = new Process(['sh']);
    $proc->setInput('echo hello');
    // string mapped to php://temp.
    $fd = $proc->getInput();
    $this->assertTrue( is_resource(  $fd ) );
    $this->assertEquals('php://temp', stream_get_meta_data($fd)['uri']);
  }
  public function testRedirectInputAsFileNameString(){
    $proc = new Process(['sh']);
    $proc->setInput('/bin/true');
    // file name string should be mapped to array.
    $fd = $proc->getInput();
    $this->assertEquals(["file", "/bin/true", "r",],$fd);
  }
  public function testRedirectInputAsStream(){
    $proc = new Process(['sh']);
    $proc->setInput(fopen('/dev/null','r'));
    // check input stream can be set.
    $fd = $proc->getInput();
    $this->assertEquals('/dev/null', stream_get_meta_data($fd)['uri']);
    $this->assertTrue( is_resource(  $fd ) );
  }
  
  
}


