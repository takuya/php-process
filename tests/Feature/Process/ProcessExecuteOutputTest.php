<?php

namespace Tests\Feature\Process;

use Exception;
use Tests\TestCase;
use SystemUtil\Process;

class ProcessExecuteOutputTest extends TestCase {
  
  /**
   * redirect stdout to unwritable file (string) -- permission denied
   */
  public function testOutputRedirectToUnWritableFile() {
    
    $this->expectException(\PHPUnit\Framework\Error\Warning::class);
    $output = "/a";
    $proc = new Process('date');
    $proc->setOutput($output);
    $proc->run();
  }
  
  /**
   * redirect stdout to file (string)
   */
  public function testOutputRedirectToFile() {
    $output = "/tmp/test";
    $proc = new Process(['echo', 'Hello']);
    $proc->setOutput($output);
    $proc->run();
    $str = file_get_contents($output);
    $this->assertRegExp("/hello/i", $str);
    @unlink($output);
  }
  
  /**
   * redirect stdout to fd (resource)
   */
  public function testOutputRedirectToFd() {
    $fout = fopen('php://temp', 'w+');
    $proc = new Process(['echo', 'Hello']);
    $proc->setOutput($fout);
    $proc->run();
    rewind($fout);
    $str = stream_get_contents($fout);
    $this->assertRegExp("/hello/i", $str);
  }
  
  /**
   * redirect stdout to php://temp (default)
   */
  public function testOutputRedirectToDefault() {
    $proc = new Process(['echo', 'Hello']);
    $ret = $proc->run();
    $str = stream_get_contents($ret[1]);
    $this->assertRegExp("/hello/i", $str);
  }
  
  /**
   * redirect stdout to php://temp (default)
   */
  public function testOutputRedirectToDefault2() {
    $proc = new Process(['echo', 'Hello']);
    $ret = $proc->run();
    $str = stream_get_contents($proc->getOutputStream());
    $this->assertRegExp("/hello/i", $str);
  }
}