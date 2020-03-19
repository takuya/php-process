<?php

namespace Tests\Feature\Callbacks;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExecuteOnChangedCallbackTest extends TestCase {

  public function testOnOutputChangedCallback() {
    $php_src = '<?php
      foreach( [0,1,2,3,4] as $i ){
        echo sprintf("No.%02d: Hello\n", $i);
        // flush();
        usleep( 10 );
      }
      ';
    $function_called= false;
    $callback = function ( $str )  use ( &$function_called ) {
      $function_called = true;
      //
      $cnt = preg_match_all('/Hello\n/', $str);
      $this->assertGreaterThan(0, $cnt);
      $this->assertLessThanOrEqual(5, $cnt);
    };

    $proc = new Process('php');
    $proc->setInput($php_src);
    $proc->setOnOutputChanged($callback);
    $proc->run();
    $str = stream_get_contents($proc->getOutput());
    $cnt = preg_match_all('/Hello\n/', $str);
    $this->assertEquals(5, $cnt);
  }
  public function testOnErroutChangedCallback() {
    $php_src = '<?php
      $err = fopen("php://stderr","w+");
        foreach( [0,1,2,3,4] as $i ){
        fwrite($err, sprintf("No.%02d: Error\n", $i));
        fflush($err);
        usleep( 10 );
      }
      ';
    $proc = new Process('php');
    $proc->setInput($php_src);
    $proc->setWaitTime(1000*10);
    $function_called= false;
    $callback = function ( $str )  use ( &$function_called ) {
      $cnt = preg_match_all('/Error\n/', $str);
      $this->assertGreaterThan(0, $cnt);
      $this->assertLessThanOrEqual(5, $cnt);
      $function_called = true;
    };
    $proc->setOnErrputChanged($callback);
    $proc->run();
    $this->assertEquals(true, $function_called);
    $str = stream_get_contents($proc->getErrout());
    $cnt = preg_match_all('/Error\n/', $str);
    $this->assertEquals(5, $cnt);
    
  }
}