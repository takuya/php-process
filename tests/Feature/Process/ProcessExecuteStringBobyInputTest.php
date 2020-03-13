<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SystemUtil\Process;


class ProcessExecuteStringBobyInputTest extends TestCase {
  
  public function testPhpBodyAsFd() {
    $str = "<?php echo 'hello world';";
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->run();
    $ret = $proc->getOutput();
    $ret = stream_get_contents($ret);
    $this->assertEquals('hello world', $ret);
  }
  public function testPhpBodyString() {
    $str = "<?php echo 'hello world';";
    $proc = new Process('php');
    $proc->setInput($str);
    $proc->run();
    $ret = $proc->getOutput();
    $ret = stream_get_contents($ret);
    $this->assertEquals('hello world', $ret);
  }
  public function testShBodyShortString() {
    $str = "echo hello";
    $proc = new Process('sh');
    $proc->setInput($str);
    $proc->run();
    $ret = $proc->getOutput();
    $ret = stream_get_contents($ret);
    $this->assertEquals("hello\n", $ret);
  }
  public function testShBodyMultiLineString() {
    $str = "
    echo hello
    echo hello
    echo hello
    echo hello
    echo hello
    echo hello
    echo hello
    echo hello
    echo hello
    echo hello
    ";
    $proc = new Process('sh');
    $proc->setInput($str);
    $proc->run();
    $ret = $proc->getOutput();
    $ret = stream_get_contents($ret);
    $this->assertEquals(10, preg_match_all('/hello/', $ret));
  }
}