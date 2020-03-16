<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExecuteStartAndTimeoutKillTest extends TestCase {
  
  public function testWaitTimeIsCurrectCheckWaittedTimeOfDiff() {
    $proc = new Process(['php']);
    $proc->setInput('<?php usleep(1000);');
    $stime = microtime(true);
    $proc->run();
    $this->assertLessThanOrEqual(0.5, microtime(true) - $stime);
  }
  
  public function testProcessIsAbleToKillProccessIdByProcTerminateWithSIGKILL() {
    $proc = new Process(['sh']);
    $proc->setInput('sleep 3');
    $stime = time();
    $proc->start();
    $res = $proc->getCurrentProcess()->proc;
    proc_terminate($res, 9);// SIGKILL=9
    while($proc->isRunning()) {
      usleep(100);
    }
    $this->assertLessThanOrEqual(1, time() - $stime);
  }
  
  public function testProcessIsAbleToKillProccessIdByProcTerminateWithSIGTERM() {
    $proc = new Process(['sh']);
    $proc->setInput('sleep 3');
    $stime = microtime(true);
    $proc->start();
    $res = $proc->getCurrentProcess()->proc;
    proc_terminate($res, 15);// SIGTERM=15
    while($proc->isRunning()) {
      usleep(100);
    }
    $this->assertLessThan(1, microtime(true) - $stime);
  }
  
  public function testProcessIsAbleToKillProccessIdByProcTerminateWithSIGINT() {
    $proc = new Process(['sh']);
    $proc->setInput('sleep 3');
    $stime = microtime(true);
    $proc->start();
    $res = $proc->getCurrentProcess()->proc;
    while($proc->isRunning()) {
      usleep(100);
      proc_terminate($res, 2);// SIGTERM=2
    }
    $this->assertLessThan(1, microtime(true) - $stime);
  }
  
  public function testProcessIsAbleToKillProccessInRunCallback() {
    $proc = new Process(['sleep', '10']);
    $stime = microtime(true);
    $proc->setTimeout(0.3);
    $proc->start();
    $proc->wait();
    $this->assertLessThan(0.4, microtime(true) - $stime);
  }
  
  public function testProcessIsAbleToKillPipedProcess() {
    $proc = new Process(['sleep', '10']);
    $stime = microtime(true);
    $proc->setTimeout(1)->pipe(['sleep', '9'])->setTimeout(1)->wait();
    $this->assertLessThan(2, microtime(true) - $stime);
  }
  
  public function testProcessForkedShellChildProcess() {
    
    $proc = new Process(['sh']);
    $proc->setInput('echo hello; sleep 10'); // zonmbiee
    $proc->setTimeout(0.8);
    $stime = microtime(true);
    $proc->run();
    $this->assertLessThan(1, microtime(true) - $stime);
  }
  
  public function testPHPProcessTimeout() {
    $proc = new Process(['php']);
    $proc->setInput("<?php echo 1; sleep(3);");
    $proc->setTimeout(0.3);
    $proc->run();
    $stime = microtime(true);
    $this->assertLessThan(1, microtime(true) - $stime);
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
    $stime = microtime(true);
    $proc = new Process('cat');
    $proc->setInput($fin)->pipe('php');
    $proc->setTimeout(0.5);
    $proc->run();
    $this->assertLessThan(2, microtime(true) - $stime);
  }
}