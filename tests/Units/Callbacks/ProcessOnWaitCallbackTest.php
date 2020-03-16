<?php

namespace Tests\Units\Callbacks;

use Tests\TestCase;
use SystemUtil\Process;
use ReflectionFunction;

class ProcessOnWaitCallbackTest extends TestCase {
  
  public function testUseOnWaitingCallbackWillBeCalledAtLeastOnce() {
    
    $proc = new Process('echo');// very fast end program.
    $proc->setOnWaiting(
      function () {
        $this->assertTrue(true);
      });
    $proc->run();
  }
  
  public function testUseOnWaitingCallbackSetterGetter() {
    
    $proc = new Process();
    $default_func = $proc->getOnWaiting();
    $default_func_ref = new  ReflectionFunction($default_func);
    $this->assertEquals(true, $default_func_ref->isClosure());
    $this->assertEquals(3, sizeof($default_func_ref->getParameters()));
    $this->assertEquals($proc, $default_func_ref->getClosureThis());
    //
    $func = function () { };
    $proc->setOnSuccess($func);
    $this->assertEquals($func, $proc->getOnSuccess());
  }
  
  public function testUseOnWaitingCallbackFunctionArgumentType() {
    
    $proc = new Process('echo');
    $proc->setOnWaiting(
      function ( $stat, $pipes, $proc_res ) use ( $proc ) {
        $this->assertIsArray($stat);
        $this->assertIsArray($pipes);
        $this->assertEquals('process', get_resource_type($proc_res));
        $this->assertEquals(3, sizeof($pipes));
        $this->assertArrayHasKey('exitcode', $stat);
        $this->assertArrayHasKey('running', $stat);
        $this->assertArrayHasKey('signaled', $stat);
        // for sure to call once.
        usleep(10);
        $proc->setOnWaiting(function () { });
      });
    $proc->run();
  }
  
  public function testUseOnWaitingCallbackCheckPassedArgumentContent() {
    
    $proc = new Process('php');
    $proc->setInput('<?php usleep(1000);echo "Hello";');
    $proc->setOnWaiting(
      function ( $stat, $pipes, $proc_res ) use ( $proc ) {
        // Don't use stream_get_contents() , because of stream_get_contents is blocking I/O.
        $this->assertEquals(0, fstat($pipes[1])['size']);;
        $this->assertEquals(0, fstat($pipes[2])['size']);;
        $this->assertEquals(true, $stat['running']);
        $this->assertEquals(-1, $stat['exitcode']);
        $this->assertEquals("stream", get_resource_type($pipes[1]));
        $this->assertEquals("stream", get_resource_type($pipes[2]));;
        // TODO ::  SetInput(string) result in pipes[0] is null. but should be active resource or else.
        $this->assertEquals(null, get_resource_type($pipes[0]));
        // for sure, called once.
        usleep(10);
        $proc->setOnWaiting(function () { });
      });
    $proc->run();
    // check output is buffered and reusable.
    $fd = $proc->getOutput();
    $this->assertEquals(5, strlen(stream_get_contents($fd)));
  }
}