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
    $proc->setInput($src);
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
    $proc->setWaitTime(1000);
    $proc->run();
    
    /// assert.
    $this->assertEquals(str_repeat("Hello\n", $loop_count ), $buff);
    
  }
  public function testRealTimeOutputWithCallback(){
    $loop_count = 3;
    $src = sprintf(
      '<?php
      foreach( range(1,%s) as $i ){
        echo "Hello\n";
        usleep(200);
      }
    ',
      $loop_count );
    $proc = new Process('php');
    $proc->setWaitTime(200);
    $proc->setInput($src);
    $buff = "";
    $proc->run(function($type,$str)use(&$buff) {
      $buff .=$str;
    });
    $this->assertEquals(str_repeat("Hello\n", $loop_count ), $buff);
  }
  public function testRealTimeOutputWithCallbackBothStdOutAndStdErr(){
    $loop_count = 3;
    $src = sprintf(
      '<?php
      $stdout = fopen("php://stdout","w");
      $stderr = fopen("php://stderr","w");
      foreach( range(1,%s) as $i ){
        fwrite($stdout,"HelloWorld\n");
        fwrite($stderr,"HelloError\n");
        usleep(200);
      }
    ',
      $loop_count );
    $proc = new Process('php');
    $proc->setWaitTime(200);
    $proc->setInput($src);
    $s_out = "";
    $s_err = "";
    $proc->run(function($type,$str)use(&$s_out,&$s_err) {
      if ( $type==Process::ERR){
        $s_err .=$str;
      }else{
        $s_out .=$str;
        
      }
    });
    $this->assertEquals(str_repeat("HelloWorld\n", $loop_count ), $s_out);
    $this->assertEquals(str_repeat("HelloError\n", $loop_count ), $s_err);
  }
}