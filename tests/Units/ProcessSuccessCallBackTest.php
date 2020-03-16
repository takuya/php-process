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
}