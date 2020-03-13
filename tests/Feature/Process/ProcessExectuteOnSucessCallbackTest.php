<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SysUtil\ProcessExec\Process;


class ProcessExectuteOnSucessCallbackTest extends TestCase {
  
  public function testOnSucessCallbackStatus() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $fout = fopen('php://temp', 'w+');
    $ferr = fopen('php://temp', 'w+');
    $on_sucess = function ( $status, $pipes ) use ( &$fout, &$ferr ) {
      while( ! feof($pipes[1])) {
        fwrite($fout, fread($pipes[1], 1024), 1024);
      }
      while( ! feof($pipes[2])) {
        fwrite($ferr, fread($pipes[2], 1024), 1024);
      }
      fflush($fout);
      fflush($ferr);
      rewind($fout);
      rewind($ferr);
    };
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnSuccess($on_sucess);
    $proc->run();
    //
    $out = stream_get_contents($fout);
    $err = stream_get_contents($ferr);
    // //
    $this->assertEquals('HelloWorld', $out);
    $this->assertEquals('HelloError', $err);
  }
  
  public function testOnSucessDoNothingCallback() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $on_sucess = function ( $status, $pipes ) {
    };
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnSuccess($on_sucess);
    $proc->run();
    //
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    // //
    $this->assertEquals('HelloWorld', $out);
    $this->assertEquals('HelloError', $err);
  }
  
  public function testOnSucessClosePipes() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $on_sucess = function ( $status, $pipes ) {
      fclose($pipes[1]);
      fclose($pipes[2]);
    };
    //
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnSuccess($on_sucess);
    $proc->run();
    //
    $this->assertTrue(get_resource_type($proc->getOutput()) == 'Unknown');
    $this->assertTrue(get_resource_type($proc->getErrout()) == 'Unknown');
  }
}
