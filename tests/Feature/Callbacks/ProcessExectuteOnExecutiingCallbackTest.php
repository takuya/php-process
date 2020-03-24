<?php

namespace Tests\Feature\Callbacks;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExectuteOnExecutiingCallbackTest extends TestCase {
  
  public function testOnExectionThatDoNothingForSuccessCommand() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    for ( $i=0; $i<10;$i++ ){
      fwrite($stdout,"$i:HelloWorld\n");
      fwrite($stderr,"$i:HelloError\n");
      usleep(1000*1);
    }
    exit(0);';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $callback = function ( $status, $pipes ) {
      // var_dump($status);
    };
    //
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnWaiting($callback);
    $proc->run();
    $out = stream_get_contents($proc->getOutputStream());
    $err = stream_get_contents($proc->getErrorOutStream());
    $this->assertEquals(10, preg_match_all('/(HelloWorld)/s', $out, $maches));
    $this->assertEquals(10, preg_match_all('/(HelloError)/s', $err, $maches));
  }
  
  public function testOnExectionThatDoNothingForFailureCommand() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    for ( $i=0; $i<10;$i++ ){
      fwrite($stdout,"$i:HelloWorld\n");
      fwrite($stderr,"$i:HelloError\n");
      usleep(1000*1);
    }
    exit(1);';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $callback = function ( $status, $pipes ) {
      // var_dump($status);
    };
    //
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnWaiting($callback);
    $proc->run();
    $out = stream_get_contents($proc->getOutputStream());
    $err = stream_get_contents($proc->getErrorOutStream());
    $this->assertEquals(10, preg_match_all('/(HelloWorld)/s', $out, $maches));
    $this->assertEquals(10, preg_match_all('/(HelloError)/s', $err, $maches));
  }
  
  public function testOnExectionThatReadStdErrorForSuccessCommand() {
    // prepare
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    for ( $i=0; $i<10;$i++ ){
      fwrite($stdout,"$i:HelloWorld\n");
      fwrite($stderr,"$i:HelloError\n");
      usleep(100*1);
    }
    exit(1);';
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    //
    $temp = fopen("php://memory", 'w+');
    $callback = function ( $status, $pipes ) use ( &$temp ) {
      // read stderr for every time incoming
      $size = 5;
      fwrite($temp, fread($pipes[2], $size), $size);
      fseek($temp, -1*$size);
      fwrite(STDERR, fread($temp, $size), $size);
    };
    //
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOnWaiting($callback);
    $proc->run();
    // getout put to eof
    $out = stream_get_contents($proc->getOutputStream());
    $err = stream_get_contents($proc->getErrorOutStream());
    // merge chunk read to eof
    rewind($temp);
    $err = stream_get_contents($temp).$err;
    //assersion
    $this->assertEquals(10, preg_match_all('/(HelloWorld)/s', $out, $maches));
    $this->assertEquals(10, preg_match_all('/(HelloError)/s', $err, $maches));
  }
}
