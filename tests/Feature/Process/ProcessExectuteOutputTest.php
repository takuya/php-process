<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SysUtil\ProcessExec\Process;

class ProcessExectuteOutputTest extends TestCase {
  
  /**
   * redirect stdout to unwritable file (string) -- permission denied
   */
  public function testOutputRedirectToUWritableFile() {
    
    $this->expectException(\Exception::class);
    $output = "/a";
    $proc = new Process('dig dns.google +short');
    $proc->setOutput($output);
    $proc->run();
  }
  
  /**
   * redirect stdout to file (string)
   */
  public function testOutputRedirectToFile() {
    $output = "/tmp/test";
    $proc = new Process('dig dns.google +short');
    $proc->setOutput($output);
    $proc->run();
    $str = file_get_contents($output);
    $this->assertRegExp("/8\.8\.8\.8/", $str);
    @unlink($output);
  }
  
  /**
   * redirect stdout to fd (resource)
   */
  public function testOutputRedirectToFd() {
    $fout = fopen('php://temp', 'w+');
    $proc = new Process('dig dns.google +short');
    $proc->setOutput($fout);
    $proc->run();
    rewind($fout);
    $str = stream_get_contents($fout);
    $this->assertRegExp("/8\.8\.8\.8/", $str);
  }
  
  /**
   * redirect stdout to php://temp (default)
   */
  public function testOutputRedirectToDefault() {
    $proc = new Process('dig dns.google +short');
    $ret = $proc->run();
    $str = stream_get_contents($ret[1]);
    $this->assertRegExp("/8\.8\.8\.8/", $str);
  }
  
  /**
   * redirect stdout to php://temp (default)
   */
  public function testOutputRedirectToDefault2() {
    $proc = new Process('dig dns.google +short');
    $ret = $proc->run();
    $str = stream_get_contents($proc->getOutput());
    $this->assertRegExp("/8\.8\.8\.8/", $str);
  }
}