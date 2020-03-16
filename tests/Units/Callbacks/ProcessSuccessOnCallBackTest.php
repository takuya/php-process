<?php

namespace Tests\Units\Callbacks;

use Tests\TestCase;
use SystemUtil\Process;
use ReflectionFunction;

class ProcessSuccessOnCallBackTest extends TestCase {
  
  public function testUseSuccessCallback() {
    
    $proc = new Process('echo');
    $proc->setOnSuccess(
      function ( $stat, $pipes ) {
        $this->assertTrue(true);
      });
    $proc->run();
  }
  
  public function testUseSuccessCallbackSetterGetter() {
    
    $proc = new Process('echo');
    $default_func = $proc->getOnSuccess();
    $default_func_ref = new  ReflectionFunction($default_func);
    $this->assertEquals(true, $default_func_ref->isClosure());
    $this->assertEquals(2, sizeof($default_func_ref->getParameters()));
    $this->assertEquals($proc, $default_func_ref->getClosureThis());
    //
    $func = function () { };
    $proc->setOnSuccess($func);
    $this->assertEquals($func, $proc->getOnSuccess());
  }
  
  public function testUseSuccessCallbackFunctionArgumentType() {
    
    $proc = new Process('echo');
    $proc->setOnSuccess(
      function ( $stat, $pipes ) {
        $this->assertIsArray($stat);
        $this->assertIsArray($pipes);
        $this->assertEquals(3, sizeof($pipes));
        $this->assertArrayHasKey('exitcode', $stat);
        $this->assertArrayHasKey('running', $stat);
        $this->assertArrayHasKey('signaled', $stat);
      });
    $proc->run();
  }
  
  public function testUseSuccessCallbackCheckPassedArgumentContent() {
    
    $proc = new Process('echo Hello World ');
    $proc->setOnSuccess(
      function ( $stat, $pipes ) {
        $this->assertEquals(false, $stat['running']);
        $this->assertEquals(0, $stat['exitcode']);
        $this->assertEquals("stream", get_resource_type($pipes[1]));
        $this->assertEquals("stream", get_resource_type($pipes[2]));;
        $this->assertEquals(12, strlen(stream_get_contents($pipes[1])));;
        $this->assertEquals(0, strlen(stream_get_contents($pipes[2])));;
        // TODO :: No setInput result in pipes[0] Unknown. but should be active resource.
        $this->assertEquals("Unknown", get_resource_type($pipes[0]));
      });
    $proc->run();
    // check output is buffred and reusable.
    $fd = $proc->getOutput();
    $this->assertEquals(12, strlen(stream_get_contents($fd)));;
  }
}