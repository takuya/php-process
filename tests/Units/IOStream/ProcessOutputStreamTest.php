<?php

namespace Tests\Units\IOStream;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessOutputStreamTest extends TestCase {
  
  public function testOutputStreamIsBufferedIsSeekable() {
    $proc = new Process(['sh']);
    $proc->setInput('echo HelloWorld');
    $proc->run();
    $fd = $proc->getOutput();
    $this->assertEquals(true, stream_get_meta_data($fd)['seekable']);
  }
  
  public function testOutputStreamIsBufferedIsRewound() {
    $proc = new Process(['sh']);
    $proc->setInput('echo HelloWorld');
    $proc->run();
    $fd = $proc->getOutput();
    $this->assertEquals(0, ftell($fd));
  }
  
  public function testOutputStreamBufferedContentisCorrect() {
    
    $proc = new Process(['sh']);
    $proc->setInput('echo  HelloWorld');
    $proc->run();
    $fd = $proc->getOutput();
    $this->assertRegExp('/HelloWorld/', stream_get_contents($fd));
    rewind($fd);
    $this->assertRegExp('/HelloWorld/', stream_get_contents($fd));
  }
  
  public function testOutputStreamIsBuffered_1Kbytes() {
    $size = 1024;
    $proc = new Process(['head', '-c', $size, '/dev/urandom']);
    $proc->run();
    $fd = $proc->getOutput();
    fseek($fd, SEEK_END);
    $this->assertEquals($size, fstat($fd)['size']);
  }
  
  public function testOutputStreamIsBuffered_64Kbytes() {
    $size = 256*256;
    $proc = new Process(['head', '-c', $size, '/dev/urandom']);
    $proc->run();
    $fd = $proc->getOutput();
    fseek($fd, SEEK_END);
    $this->assertEquals($size, fstat($fd)['size']);
  }
  
  public function testOutputStreamIsBuffered_65Kbytes_checkFreeze() {
    $size = 256*256 + 1;
    // without buffering, output more than 256*256+1 will freeze.Check freeze, and Timeout
    $proc = new Process(['head', '-c', $size, '/dev/urandom']);
    $proc->disableBufferingOnWait();
    $proc->setTimeout(0.25);
    $proc->run();
    $is_canceld = $proc->canceled();
    $this->assertEquals(true, $is_canceld);
  }
  public function testOutputStreamIsBuffered_65Kbytes_avoidByBuffuering() {
    $size = 256*256 + 1;
    // Without buffering,  more than 256*256+1 will freeze. Check Success by Buffering.
    $proc = new Process(['head', '-c', $size, '/dev/urandom']);
    $proc->enableBufferingOnWait();
    $proc->setTimeout(0.25);
    $proc->run();
    $fd = $proc->getOutput();
    fseek($fd, SEEK_END);
    $this->assertEquals($size, fstat($fd)['size']);
  }
  
  public function testOutputStreamIsBuffered_100bytes() {
    $size = 1024*100;
    $proc = new Process(['head', '-c', $size, '/dev/urandom']);
    $proc->setOutput($fd = fopen('php://temp', 'w'));
    $proc->run();
    $fd = $proc->getOutput();
    fseek($fd, SEEK_END);
    $this->assertEquals($size, fstat($fd)['size']);
  }
}