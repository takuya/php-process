<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExectutePipeTest extends TestCase {
  
  public function testTwoProcessJoinByFd() {
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    fwrite($stdout,"HelloWorld");
    ';
    $proc1 = new Process('php');
    $proc1->setInput($str);
    [$p1_out, $p1_err] = $proc1->start();
    $proc2 = new Process('cat');
    $proc2->setInput($p1_out);
    $proc2->run();
    $p2_out = $proc2->getOutput();
    $str = stream_get_contents($p2_out);
    $this->assertEquals("HelloWorld", $str);
  }
  
  public function testTwoProcessJoinByPipeMethod() {
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    fwrite($stdout,"HelloWorld");
    ';
    $proc1 = new Process('php');
    $proc1->setInput($str);
    $proc2 = $proc1->pipe('cat');
    $proc2->wait();
    $p2_out = $proc2->getOutput();
    $str = stream_get_contents($p2_out);
    $this->assertEquals("HelloWorld", $str);
  }
  
  public function testPipeThreeProcessJoinByPipeMethod() {
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    fwrite($stdout,"HelloWorld");
    ';
    $proc1 = new Process('php');
    $proc1->setInput($str);
    //
    $proc2 = $proc1->pipe('cat');
    $proc3 = $proc2->pipe('cat');
    $proc3->wait();
    $p3_out = $proc3->getOutput();
    $str = stream_get_contents($p3_out);
    $this->assertEquals("HelloWorld", $str);
  }
  
  public function testPipeFourProcessJoinByPipeMethod() {
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    fwrite($stdout,"HelloWorld");
    ';
    $proc1 = new Process('php');
    $proc1->setInput($str);
    //
    $proc2 = $proc1->pipe('cat');
    $proc3 = $proc2->pipe('cat');
    $proc4 = $proc3->pipe(['grep', 'World']);
    $proc4->wait();
    $p4_out = $proc4->getOutput();
    $str = stream_get_contents($p4_out);
    $str = trim($str);
    $this->assertEquals("HelloWorld", $str);
  }
  
  public function testTwoProcessJoinByPipeProcessMethod() {
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    fwrite($stdout,"HelloWorld");
    ';
    $proc1 = new Process('php');
    $proc2 = new Process('cat');
    $proc1->setInput($str);
    $proc1->pipeProcess($proc2);
    $proc2->wait();
    $p2_out = $proc2->getOutput();
    $str = stream_get_contents($p2_out);
    $this->assertEquals("HelloWorld", $str);
  }
  
  public function testTwoProcessJoinByPipeMethocChain() {
    $str = '
    echo Hello
    echo HelloWorld
    echo Hello Sample
    ';
    $proc1 = new Process('sh');
    $proc1->setInput($str);
    $out = $proc1->pipe('cat')->pipe(['grep', 'HelloWorld'])->wait();
    $str = stream_get_contents($out);
    $str = trim($str);
    $this->assertEquals("HelloWorld", $str);
  }
  
  public function testThreeProcessJoinByPipeMethocChain() {
    $str = '
    echo Hello
    echo HelloWorld
    echo Hello Sample
    ';
    $proc1 = new Process('sh');
    $proc1->setInput($str);
    $out = $proc1->pipe('cat')->pipe(['grep', 'HelloWorld'])->wait();
    $str = stream_get_contents($out);
    $str = trim($str);
    $this->assertEquals("HelloWorld", $str);
  }
}
