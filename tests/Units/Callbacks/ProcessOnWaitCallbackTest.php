<?php

namespace Tests\Units\Callbacks;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessOnWaitCallbackTest  extends TestCase {
  
  public function testUseOnWaitingCallback() {
    
    $proc = new Process('echo');
    $proc->setOnWaiting(
      function ( $stat, $pipes ) {
        $this->assertTrue(true);
      });
    $proc->run();
  }
  public function testUseOnWaitingCallbackSetterGetter() {
    
    $proc = new Process('echo');
    
    $default_func = $proc->getOnWaiting();
    $default_func_ref = new  \ReflectionFunction($default_func);
    $this->assertEquals(true, $default_func_ref->isClosure());
    $this->assertEquals(3, sizeof($default_func_ref->getParameters()));
    $this->assertEquals($proc, $default_func_ref->getClosureThis());
    //
    $func = function(){};
    $proc->setOnSuccess($func);
    $this->assertEquals($func, $proc->getOnSuccess());
  }
  public function testUseOnWaitingCallbackFunctionArgumentType() {
    
    $proc = new Process('echo');
    $proc->setOnWaiting(
      function ( $stat, $pipes, $proc_res ) {
        $this->assertIsArray($stat);
        $this->assertIsArray($pipes);
        $this->assertEquals('process',get_resource_type($proc_res));
        $this->assertEquals(3, sizeof($pipes));
        $this->assertArrayHasKey('exitcode', $stat);
        $this->assertArrayHasKey('running', $stat);
        $this->assertArrayHasKey('signaled', $stat);
        usleep(100);// for sure to call once.
      });
    $proc->run();
  }
  public function testUseOnWaitingCallbackCheckPassedArgumentContent() {
    
    $proc = new Process('php');
    $proc->setInput('<?php usleep(100);echo "Hello";');
    $proc->setOnWaiting(
      function ( $stat, $pipes, $proc_res ) {
        $this->assertEquals(true, $stat['running']);
        $this->assertEquals(-1, $stat['exitcode']);
        $this->assertEquals("stream", get_resource_type($pipes[1]));
        $this->assertEquals("stream", get_resource_type($pipes[2]));;
        // Dont use stream_get_contents() , becase stream_get_contents is blocking IO.
        $this->assertEquals(0, fstat($pipes[1])['size']);;
        $this->assertEquals(0, fstat($pipes[2])['size']);;
        // TODO ::  SetInput(string) result in pipes[0] null. but should be active resource or else.
        $this->assertEquals(null, get_resource_type($pipes[0]));
        usleep(1000);// for sure, called once.
      });
    $proc->run();
    
    // check output is buffered and reusable.
    $fd = $proc->getOutput();
    $this->assertEquals(5, strlen(stream_get_contents($fd)));;
    
  }
  
}