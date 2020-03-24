<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExecuteViaSshTest extends TestCase {
  
  public function testProcessExecuteViaSSH() {
    
    $cmd = [
      'ssh',
      'localhost',
      'echo  Hello via ssh ',
    ];
    $proc = new Process($cmd);
    $proc->run();
    $out = stream_get_contents($proc->getOutputStream());
    $err = stream_get_contents($proc->getErrorOutStream());
    $this->assertRegExp('/Hello via ssh/i', $out);
  }
  
  public function testProcessExecuteViaSSHWithInputRedirect() {
    
    $cmd = [
      'ssh',
      'localhost',
      'cat',
    ];
    $fin = fopen("php://temp", 'w+');
    $str = md5(strftime("%c").random_bytes(100));
    fwrite($fin, $str);
    rewind($fin);
    $proc = new Process($cmd);
    $proc->setInput($fin);
    $proc->run();
    $out = stream_get_contents($proc->getOutputStream());
    $err = stream_get_contents($proc->getErrorOutStream());
    $this->assertEquals($str, $out);
  }
}