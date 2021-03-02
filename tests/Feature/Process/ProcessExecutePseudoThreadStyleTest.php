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
  
    $proc = new Process('php');
    $proc->setInput('<?php usleep(1000);');
    $proc->setTimeout(1/1000/1000*100);
    $proc->start();
    $proc->stop();
    $proc->join();
    $this->assertEquals(true, $proc->canceled());
    $this->assertEquals(false, $proc->isRunning());
    $this->assertEquals(false, $proc->isSuccessful());
    
  }
}