<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;


class ProcessExecuteStartAndTimeoutKillTest extends TestCase {
  
  public function testWaitTimeIsCurrectCheckWaittedTimeOfDiff(){
    $proc = new Process(['sh']);
    $proc->setInput('sleep 1');
    $stime = microtime();
    $proc->run();
    $this->assertLessThanOrEqual( 1, microtime() - $stime );
  }
  
  public function testProcessIsAbleToKillProccessIdByProcTerminateWithSIGKILL(){
    $proc = new Process(['sh']);
    $proc->setInput('sleep 3');
    $stime = time();
    $proc->start();
    $res = $proc->getCurrentProcess()->proc;
    proc_terminate($res,9);// SIGKILL=9
    while( $proc->isRunning()){
      usleep(100);
    }
    $this->assertLessThanOrEqual( 1, time() - $stime );
    
  }
  public function testProcessIsAbleToKillProccessIdByProcTerminateWithSIGTERM(){
    $proc = new Process(['sh']);
    $proc->setInput('sleep 3');
    $stime = microtime();
    $proc->start();
    $res = $proc->getCurrentProcess()->proc;
    proc_terminate($res,15);// SIGTERM=15
    while( $proc->isRunning()){
      usleep(100);
    }
    $this->assertLessThan( 1, microtime() - $stime );
  }
  public function testProcessIsAbleToKillProccessIdByProcTerminateWithSIGINT(){
    $proc = new Process(['sh']);
    $proc->setInput('sleep 3');
    $stime = microtime();
    $proc->start();
    $res = $proc->getCurrentProcess()->proc;
    while( $proc->isRunning()){
      usleep(100);
      proc_terminate($res,2);// SIGTERM=2
    }
    $this->assertLessThan( 1, microtime() - $stime );
  }
  public function testProcessIsAbleToKillProccessInRunCallback(){
    $proc = new Process(['sleep','10']);
    $stime = microtime();
    $proc->setTimeout(1);
    $proc->start();
    $proc->wait();
    $this->assertLessThan( 2, microtime() - $stime );
  }
  public function testProcessForkedShellChildPrcorss(){
    $proc = new Process(['bash']);
    $proc->setInput('echo 1; sleep 5;');
    $stime = microtime();
    $proc->setTimeout(1);
    $proc->start();
    $proc->wait();
    $this->assertLessThan( 2, microtime() - $stime );
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