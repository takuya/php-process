<?php

namespace Tests\Feature\Process;

use Exception;
use Tests\TestCase;
use SystemUtil\Process;

class ProcessExectuteInputTest extends TestCase {
  
  /**
   * redirect stdin to unreadable file (string) -- permission denied
   */
  public function testOutputRedirectToUnWritableFile() {

    
    $this->expectException(\PHPUnit\Framework\Error\Warning::class);
    $fname = "/etc/shadow";
    if( preg_match('/darwin/i', PHP_OS) ) {
      $fname = "/var/root/Downloads/a";
    }
    $proc = new Process('date');
    $proc->setInput($fname);
    $proc->run();
  }
  
  /**
   * redirect stdin to file (string)
   */
  public function testOutputRedirectToFile() {
    $fname = "/tmp/test";
    $str = "--------\nabcdef\n--------\n";
    file_put_contents($fname, $str);
    $proc = new Process('cat');
    $proc->setInput($fname);
    $proc->run();
    $ret = $proc->getOutput();
    $ret = stream_get_contents($ret);
    $this->assertEquals($str, $ret);
    @unlink($fname);
  }
  
  /**
   * redirect stdin to fd (resource)
   */
  public function testInputRedirectToFd() {
    $str = "--------\nabcdef\n--------\n";
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    $proc = new Process('cat');
    $proc->setInput($fin);
    $proc->run();
    $ret = $proc->getOutput();
    $ret = stream_get_contents($ret);
    $this->assertEquals($str, $ret);
  }
  
  public function testInputRedirectBigString() {
    $str = '';
    for ($i = 0; $i < 2000; $i++) {
      $str .= "--------\nabcdef\n--------\n";
    }
    $proc = new Process('php');
    $proc->setInput($str);
    $proc->run();
    $ret = $proc->getOutput();
    $ret = stream_get_contents($ret);
    $this->assertEquals($str, $ret);
  }
}