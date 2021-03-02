<?php

namespace Tests\Units\IOStream;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessOutStringTest extends TestCase {
  
  public function testOutputErrorOutAsString() {
    $proc = new Process(['php']);
    $src = '<?php
    $fderr=fopen("php://stderr","w+");
    $fdout=fopen("php://stdout","w+");
    
    fwrite($fdout , "Hello World" );
    fwrite($fderr, "Hello Error");
    fclose($fdout);
    fclose($fderr);
    ';
    $proc->setInput($src);
    $proc->run();
    $out = $proc->getOutput();
    $err = $proc->getErrorOutput();
  
    $this->assertEquals("Hello World", $out);
    $this->assertEquals("Hello Error", $err);
  }
  public function testGetCommandLineAsString() {
    $cmd = 'ssh 192.168.1.1 date';
    $proc = new Process($cmd);
    $this->assertEquals($cmd, $proc->getCommandLine());
    
    $proc = new Process(preg_split('/\s+/', $cmd));
    preg_match('|^([\d.]+)|',phpversion(),$m);
    if ( floatval($m[0]) < 7.4 ){
      $this->assertEquals($cmd, $proc->getCommandLine());
    }else{
      $this->assertEquals(preg_split('/\s+/', $cmd), $proc->getCommandLine());
    }
  }
  public function testExitCodeAndIsSuccessful() {
    $proc = new Process(['php']);
    $src = '<?php
    $fderr=fopen("php://stderr","w+");
    $fdout=fopen("php://stdout","w+");
    
    fwrite($fdout , "Hello World" );
    fwrite($fderr, "Hello Error");
    fclose($fdout);
    fclose($fderr);
    ';
    $proc->setInput($src);
    $proc->run();
  
    $this->assertEquals(0, $proc->getExitCode());
    $this->assertEquals(true, $proc->isSuccessful());
    
  }
  
}