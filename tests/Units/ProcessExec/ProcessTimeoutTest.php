<?php

namespace Tests\Units\ProcessExec;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessTimeoutTest extends TestCase {
  
  public function testSetTimeoutAndGetTimeoutValue() {
    
    $proc = new Process();
    $proc->setTimeout(1);
    $this->assertTrue(is_double($proc->getTimeout()));
    $proc->setTimeout(0.1);
    $this->assertTrue(is_double($proc->getTimeout()));
    $proc->setTimeout(0.001);
    $this->assertTrue(is_double($proc->getTimeout()));
    $proc->setTimeout(1.1);
    $this->assertTrue(is_double($proc->getTimeout()));
    $proc->setTimeout('1.1');
    $this->assertTrue(is_double($proc->getTimeout()));
    $ret = $proc->setTimeout('1.1');
    $this->assertEquals(Process::class, get_class($ret));
  }
  
  public function testCheckLimitTimeExecutionTimeoutWorksFine() {
    $proc = new Process('php');
    // $proc->setInput('<?php usleep(1000);');
    $proc->setInput('<?php sleep(1);');
    // $proc->setTimeout(1/1000/1000*100);
    $proc->setTimeout(1/100 );
    $proc->run();
    $this->assertEquals(true, $proc->canceled());
  }
}