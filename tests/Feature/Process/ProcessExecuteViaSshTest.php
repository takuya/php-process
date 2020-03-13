<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExecuteViaSshTest extends TestCase {
  
  public function testProcessExecuteViaSSH() {
    
    $cmd = [
      'ssh',
      's0',
      'uname -a',
    ];
    $proc = new Process($cmd);
    $proc->run();
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    $this->assertRegExp('/linux/i', $out);
  }
  
  public function testProcessExecuteViaSSHWithInputRedirect() {
    
    $cmd = [
      'ssh',
      's0',
      'cat',
    ];
    $fin = fopen("php://temp", 'w+');
    $str = md5(strftime("%c").random_bytes(100));
    fwrite($fin, $str);
    rewind($fin);
    $proc = new Process($cmd);
    $proc->setInput($fin);
    $proc->run();
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    $this->assertEquals($str, $out);
  }
}