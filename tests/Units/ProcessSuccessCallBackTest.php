<?php

namespace Tests\Units;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessSuccessCallBackTest extends TestCase {
  
  
  public function testUseSuccessCallback(){
    
    $proc = new Process('echo');
    $proc->setOnSuccess(function( $stat, $pipes ) {
      $this->assertTrue(true);
    });
    $proc->run();
  }
  public function testUseSuccessCallbackCheckStat(){
    
    $proc = new Process('echo');
    $proc->setOnSuccess(function( $stat, $pipes ) {
      $this->assertEquals(false,$stat['running']);
      $this->assertEquals(0,$stat['exitcode']);
    });
    $proc->run();
  }
  
}