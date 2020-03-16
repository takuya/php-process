<?php

namespace Tests\Units\IOStream;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessErrorOutputStreamTest extends TestCase {
  
  public function testOutputStreamIsBufferedIsSeekable() {
    $proc = new Process(['sh']);
    $proc->setInput('echo HelloWorld 3>&1 1>&2 2>&3');
    $proc->run();
    $fd = $proc->getErrout();
    $this->assertEquals(true, stream_get_meta_data($fd)['seekable']);
  }
  
  public function testErrorOutputStreamIsBufferedIsRewound() {
    $proc = new Process(['sh']);
    $proc->setInput('echo HelloWorld 3>&1 1>&2 2>&3');
    $proc->run();
    $fd = $proc->getErrout();
    $this->assertEquals(0, ftell($fd));
  }
  
  public function testErrorOutputStreamIsBuffered_checkRewind() {
    
    $proc = new Process(['sh']);
    $proc->setInput('echo HelloWorld 3>&1 1>&2 2>&3');
    $proc->run();
    $fd = $proc->getErrout();
    $this->assertRegExp('/HelloWorld/', stream_get_contents($fd));
    rewind($fd);
    $this->assertRegExp('/HelloWorld/', stream_get_contents($fd));
  }
  
  public function testErrorOutputStreamIsBuffered_1Kbytes() {
    $size = 1024;
    $proc = new Process('php');
    $proc->setInput(sprintf('<?php $fd=fopen("php://stderr","w+");
      for( $i=0;$i<%d;$i++ ){ fwrite($fd, 1); };
      fflush($fd);
      fclose($fd);',$size));
    $proc->run();
    $fd = $proc->getErrout();
    fseek($fd, $size);
    fread($fd, 1);
    $this->assertEquals($size, fstat($fd)['size']);
    $this->assertEquals($size, ftell($fd));
    $this->assertEquals(true, feof($fd));
  }
  
  public function testErrorOutputStreamIsBuffered_64Kbytes() {
    $size = 1024*64;
    $proc = new Process('php');
    $proc->setInput(sprintf('<?php $fd=fopen("php://stderr","w+");
      for( $i=0;$i<%d;$i++ ){ fwrite($fd, 1); };
      fflush($fd);
      fclose($fd);',$size));
    $proc->run();
    $fd = $proc->getErrout();
    fseek($fd, $size);
    fread($fd, 1);
    $this->assertEquals($size, fstat($fd)['size']);
    $this->assertEquals($size, ftell($fd));
    $this->assertEquals(true, feof($fd));
  }
  
  public function testErrorOutputStreamIsBuffered_65Kbytes() {
    $size = 256*256 + 1;
    // more than 256*256+1 will freeze.
    $proc = new Process('php');
    $proc->setInput(sprintf('<?php $fd=fopen("php://stderr","w+");
      for( $i=0;$i<%d;$i++ ){ fwrite($fd, 1); };
      fflush($fd);
      fclose($fd);',$size));
    $proc->setTimeout(1);
    $proc->run();
    $is_canceld = $proc->canceled();
    $this->assertEquals(true, $is_canceld);
  }
  
  public function testErrorOutputStreamIsBuffered_1Mbytes() {
    $size = 1024*1024;
    $proc = new Process('php');
    $proc->setInput(sprintf('<?php $fd=fopen("php://stderr","w+");
      for( $i=0;$i<%d;$i++ ){ fwrite($fd, 1); };
      fflush($fd);
      fclose($fd);',$size));
    $proc->setErrout($fd = fopen('php://temp', 'w'));
    $proc->run();
    $fd = $proc->getErrout();
    fseek($fd, $size);
    fread($fd, 1);
    $this->assertEquals($size, fstat($fd)['size']);
    $this->assertEquals($size, ftell($fd));
    $this->assertEquals(true, feof($fd));
  }
  
  public function testErrorOutputStreamIsBuffered_10Mbytes() {
    // very slow. why.
    $size = 1024*1024*10;
    $proc = new Process('php');
    $proc->setInput(sprintf('<?php $fd=fopen("php://stderr","w+");
      for( $i=0;$i<%d;$i++ ){ fwrite($fd, 1);};
      fflush($fd);
      fclose($fd);',$size));
    $proc->setErrout($fd = fopen('php://temp', 'w'));
    $proc->run();
    $fd = $proc->getErrout();
    fseek($fd, $size);
    fread($fd, 1);
    $this->assertEquals($size, fstat($fd)['size']);
    $this->assertEquals($size, ftell($fd));
    $this->assertEquals(true, feof($fd));
  }
  
}