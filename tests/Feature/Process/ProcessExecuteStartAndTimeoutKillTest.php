<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;


class ProcessExecuteStartAndTimeoutKillTest extends TestCase {
  
  public function testProcessStartAndWait() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    for ( $i=0; $i<10;$i++ ){
      fwrite($stdout,"$i:HelloWorld\n");
      fwrite($stderr,"$i:HelloError\n");
      sleep(1);
    }
    exit(0);';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $starttime = time();
    $callback = function ( $status, $pipes, $process ) use ( $starttime ) {
      if( $starttime + 0.3 < time() ) {
        proc_terminate($process, SIGTERM);
      }
    };
    //
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnWaiting($callback);
    $proc->run();
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    $this->assertLessThan(2, preg_match_all('/(HelloWorld)/s', $out, $maches));
    $this->assertLessThan(2, preg_match_all('/(HelloError)/s', $err, $maches));
  }
  
  public function testProcessSetTimeout() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    for ( $i=0; $i<10;$i++ ){
      fwrite($stdout,"$i:HelloWorld\n");
      fwrite($stderr,"$i:HelloError\n");
      sleep(1);
    }
    exit(0);';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setTimeout(0.3);
    $proc->run();
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    $this->assertLessThan(2, preg_match_all('/(HelloWorld)/s', $out, $maches));
    $this->assertLessThan(2, preg_match_all('/(HelloError)/s', $err, $maches));
  }
}