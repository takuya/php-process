<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExectuteWithEnvTest extends TestCase {
  
  public function testExecCommandWithEnvironment() {
    
    $env = [
      'HELLO' => 'WORLD',
    ];
    $str = 'echo $HELLO';
    $proc = new Process('sh', $env);
    $proc->setInput($str);
    $proc->run();
    $ret = $proc->getOutputStream();
    $ret = stream_get_contents($ret);
    $this->assertEquals($ret, "WORLD\n");
  }
  
  public function testExecCommandWithSetEnvironment() {
    
    $env = [
      'HELLO' => 'WORLD',
    ];
    $str = 'echo $HELLO';
    $proc = new Process('sh');
    $proc->setEnv($env);
    $proc->setInput($str);
    $proc->run();
    $ret = $proc->getOutputStream();
    $ret = stream_get_contents($ret);
    $this->assertEquals($ret, "WORLD\n");
  }
  
  public function testExecCommandWithAddEnvironment() {
    
    $str = 'echo $HELLO';
    $proc = new Process('sh');
    $proc->addEnv('HELLO', 'WORLD');
    $proc->setInput($str);
    $proc->run();
    $ret = $proc->getOutputStream();
    $ret = stream_get_contents($ret);
    $this->assertEquals($ret, "WORLD\n");
  }
}