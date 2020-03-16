<?php

namespace Tests\Units\Callbacks;

use Tests\TestCase;
use SystemUtil\Process;
use ReflectionFunction;

class ProcessOnErrorCallBackTest extends TestCase {
  
  public function testUseErrorCallback() {
    
    $proc = new Process('php');
    $proc->setInput('<?php exit(1);');
    $proc->setOnError(
      function ( $stat, $pipes ) {
        $this->assertTrue(true);
      });
    $proc->run();
  }
  
  public function testUseErrorCallbackSetterGetter() {
    
    $proc = new Process('echo');
    $default_func = $proc->getOnError();
    $default_func_ref = new  ReflectionFunction($default_func);
    $this->assertEquals(true, $default_func_ref->isClosure());
    $this->assertEquals(2, sizeof($default_func_ref->getParameters()));
    $this->assertEquals($proc, $default_func_ref->getClosureThis());
    //
    $func = function () { };
    $proc->setOnSuccess($func);
    $this->assertEquals($func, $proc->getOnError());
  }
  
  public function testUseErrorCallbackCheckFunctionArgumentType() {
    
    $proc = new Process('php');
    $proc->setInput('<?php exit(1);');
    $proc->setOnError(
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
  
  public function testUseErrorCallbackCheckPassedArgumentContent() {
    
    $proc = new Process('php');
    $proc->setInput('<?php echo "Hello World\n"; exit(1);');
    $proc->setOnError(
      function ( $stat, $pipes ) {
        $this->assertEquals(false, $stat['running']);
        $this->assertEquals(1, $stat['exitcode']);
        $this->assertEquals("stream", get_resource_type($pipes[1]));
        $this->assertEquals("stream", get_resource_type($pipes[2]));;
        $this->assertEquals(12, strlen(stream_get_contents($pipes[1])));;
        $this->assertEquals(0, strlen(stream_get_contents($pipes[2])));;
        // TODO :: After call setInput, pipes[0] is null. but should be resource or else.
        $this->assertEquals(null, get_resource_type($pipes[0]));
      });
    $proc->run();
  }
}