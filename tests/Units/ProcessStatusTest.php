<?php

namespace Tests\Units;

use Tests\TestCase;
use SystemUtil\Process;
use PHPUnit\Framework\TestResult;

class ProcessStatusTest  extends TestCase {
  
  public function testPrcocessSucessExitStatusCode(){
    
    $proc = new Process(['date']);
    $proc->run();
    $this->assertEquals(0, $proc->getExitStatusCode());
    
  }
  public function testPrcocessFailedExitStatusCode(){
    
    $proc = new Process(['date','-OOOO']);
    $proc->run();
    $this->assertEquals(1, $proc->getExitStatusCode());
    
  }
  public function testPrcocessPHPExitStatusCodePHPExitCode(){
    
    foreach ([1,2,3,4] as $i ){
      $proc = new Process(['php']);
      $proc->setInput(sprintf('<?php echo 1 ; exit(%d);', $i));
      $proc->run();
      $this->assertNotEquals(0, $proc->getExitStatusCode());
    }
  
  }
  public function testPrcocesExecutingStatusCode(){
    
    $proc = new Process(['sleep','1']);
    $proc->setTimeout(0.1);
    $proc->start();
    $this->assertEquals(-1, $proc->getExitStatusCode());
    usleep(10);
    $this->assertEquals(-1, $proc->getExitStatusCode());
    $proc->wait();
    $this->assertEquals(-1, $proc->getExitStatusCode());
  }
  public function testPrcocesIsCacncedBySingnal(){
    
    $proc = new Process(['php']);
    $proc->setInput('<?php echo 1 ; sleep(1);');
    $proc->setTimeout(0.1);
    $proc->start();
    $this->assertEquals(-1, $proc->getExitStatusCode());
    usleep(10);
    $this->assertEquals(-1, $proc->getExitStatusCode());
    $proc->wait();
    $this->assertNotEquals(0, $proc->getExitStatusCode());
    $this->assertEquals(true, $proc->canceled() );
  }
}