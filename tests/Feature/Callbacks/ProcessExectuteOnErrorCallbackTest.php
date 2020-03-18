<?php

namespace Tests\Feature\Callbacks;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExectuteOnErrorCallbackTest extends TestCase {
  
  public function testOnErrorCallbackStatus() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");
    exit(1);';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $fout = fopen('php://temp', 'w+');
    $ferr = fopen('php://temp', 'w+');
    $callback = function ( $status, $pipes ) use ( &$fout, &$ferr ) {
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
    $proc->setOnError($callback);
    $proc->run();
    //
    $out = stream_get_contents($fout);
    $err = stream_get_contents($ferr);
    // //
    $this->assertEquals('HelloWorld', $out);
    $this->assertEquals('HelloError', $err);
  }
  
  public function testOnErrorDoNothingCallback() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");
    exit(1);
    ';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $on_error = function ( $status, $pipes ) {
    };
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnError($on_error);
    $proc->run();
    //
    $fout = $proc->getOutput();
    // var_dump([fstat_c($fout),$proc]);exit;;
    $out = stream_get_contents($fout);
    $err = stream_get_contents($proc->getErrout());
    // //
    $this->assertEquals('HelloWorld', $out);
    $this->assertEquals('HelloError', $err);
  }
  
  public function testOnErrorClosePipes() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");
    exit(1);';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $callback = function ( $status, $pipes ) {
      fclose($pipes[1]);
      fclose($pipes[2]);
    };
    //
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnError($callback);
    $proc->run();
    //
    $this->assertTrue(get_resource_type($proc->getOutput()) == 'Unknown');
    $this->assertTrue(get_resource_type($proc->getErrout()) == 'Unknown');
  }
}
