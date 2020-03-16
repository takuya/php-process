<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessRealTimeOutputTest extends TestCase {
  
  public function testRealTimeFetchOutputByOnWaitCallback() {
    
    $loop_count = 3;
    $src = sprintf(
      '<?php
      foreach( range(0,%s) as $i ){
        echo "Hello\n";
        usleep(1000);
      }
    ',
      $loop_count - 1);
    $proc = new Process('php');
    $proc->setInput($src);;
    $buff = "";
    $count =0;

    /**
     * Overview of Timing blocking and releasing.
     *                     echo        echo        echo
     *        php  START----|-----------|-----------|-----END
     *  Process onWait -@---->---------@-->--------@-->-----@>---end
     *                  block release  b  r       b   r     ^ last call
     *                                                     read ""(EOF), without blocked.
     */
    $func = function ( $stat, $pipes, $proc_res ) use ( &$buff ) {
      $buff = $buff.fread($pipes[1], 1024);
    };
    $proc->setOnWaiting($func);
    $proc->run();
    
    /// assert.
    $this->assertEquals(str_repeat("Hello\n", $loop_count ), $buff);
    
  }
}