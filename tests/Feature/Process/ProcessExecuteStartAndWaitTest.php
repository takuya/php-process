<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExecuteStartAndWaitTest extends TestCase {
  
  public function testStartProcessStartWaitNoSetOutputGetOut() {
    
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    $proc = new Process('php');
    $proc->setInput($str);
    $proc->start();
    $proc->wait();
    //
    $ret = $proc->getOutputStream();
    $ret = stream_get_contents($ret);
    //
    $this->assertEquals("HelloWorld", $ret);
  }
  
  public function testStartProcessStartWaitNonBlockingNoGetOutput() {
    
    $str = 'HelloWorld';
    $proc = new Process('php');
    $proc->setInput("<?php usleep(1000*3); echo '$str';");
    $proc->start();
    $looped = 0;
    while($proc->isRunning()) {
      $looped++;
      usleep(10);
    }
    //
    $this->assertGreaterThan(10, $looped);
  }
  
  public function testStartProcessStartWaitNonBlockingSetOutputGetOutput() {
    
    $str = 'HelloWorld';
    $proc = new Process('php');
    $proc->setInput("<?php usleep(1000*3); echo '$str';");
    $proc->setOutput($fout = fopen("php://temp", 'w+'));
    $proc->start();
    $looped = 0;
    while($proc->isRunning()) {
      $looped++;
      usleep(10);
    }
    $ret = $proc->getOutputStream();
    $ret = stream_get_contents($ret);
    $this->assertEquals($str, $ret);
    $this->assertGreaterThan(10, $looped);
  }
  
  public function testStartProcessStartWaitNonBlockingSetErroutGetErrOut() {
    
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    $proc = new Process('php');
    $proc->setInput($str);
    $proc->setErrout($fout = fopen("php://temp", 'w+'));
    $proc->start();
    $looped = 0;
    while($proc->isRunning()) {
      $looped++;
      usleep(10);
    }
    // dd($proc);
    $ret = $proc->getErrorOutStream();
    $ret = stream_get_contents($ret);
    $this->assertEquals("HelloError", $ret);
    $this->assertGreaterThan(10, $looped);
  }
  
  public function testStartProcessStartWaitNonBlockingGetErrOut() {
    
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    $proc = new Process('php');
    $proc->setInput($str);
    $proc->start();
    $looped = 0;
    while($proc->isRunning()) {
      $looped++;
      usleep(10);
    }
    // dd($proc);
    $ret = $proc->getErrorOutStream();
    $ret = stream_get_contents($ret);
    $this->assertEquals("HelloError", $ret);
    $this->assertGreaterThan(10, $looped);
  }
  
  public function testStartProcessStartWaitNonBlockingGetOutput() {
    
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    $proc = new Process('php');
    $proc->setInput($str);
    $proc->start();
    $looped = 0;
    while($proc->isRunning()) {
      $looped++;
      usleep(10);
    }
    // dd($proc);
    $ret = $proc->getOutputStream();
    $ret = stream_get_contents($ret);
    $this->assertEquals("HelloWorld", $ret);
    $this->assertGreaterThan(10, $looped);
  }
}