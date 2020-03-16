<?php

namespace Tests\Feature\Pipe;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExectuteMethodChainTest extends TestCase {
  
  public function testProcessInputMethodChainAndPipeMethocChain() {
    $str = 'echo HelloWorld';
    $proc1 = new Process('sh');
    $out = $proc1->setInput($str)->pipe('cat')->pipe('cat')->pipe(['grep', 'Hello'])->wait();
    $ret = stream_get_contents($out);
    $ret = trim($ret);
    $this->assertEquals("HelloWorld", $ret);
  }
}
