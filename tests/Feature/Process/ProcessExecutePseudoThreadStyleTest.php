<?php



namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExecutePseudoThreadStyleTest  extends TestCase {
  
  public function test_pseudo_thread_style_coding(){
    $code ='
      <?php
      foreach(range(0,9) as $e){
        usleep(10);
        echo "Hello";
      }
    ';
    $proc = new Process('php');
    $proc->setInput($code);
    $proc->start();
    $this->assertTrue($proc->isRunning());
    $proc->join();
    $this->assertFalse($proc->isRunning());
    
  }
}