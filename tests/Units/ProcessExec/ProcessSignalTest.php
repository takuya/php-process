<?php

namespace Tests\Units\ProcessExec;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessSignalTest extends TestCase {
  
  public function testSendSignalToProcess() {
    $proc = new Process('php');
    $proc->setInput('<?php usleep(1000);');
    $proc->setTimeout(1/1000/1000*100);
    $proc->start();
    $proc->signal(15);
    $this->assertEquals(true, $proc->canceled());
    $this->assertEquals(false, $proc->isRunning());
    $this->assertEquals(false, $proc->isSuccessful());
  }
}