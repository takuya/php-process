<?php

namespace Tests\SampleCode;

use Tests\TestCase;
use SystemUtil\Process;

class TestExecuteSamplesTest extends TestCase {
  
  public function testCheckSyntaxSamples() {
    
    $samples_file = glob(__DIR__.'/../../samples/src/*.php');
    foreach ($samples_file as $f_name) {
      
      $proc = new Process([trim(`which php`), '-l', $f_name]);
      $proc->run();
      if( ! $proc->getExitStatusCode() == 0 ) {
        var_dump([$proc->getExitStatusCode(), $f_name]);
      }
      $this->assertEquals(0, $proc->getExitStatusCode());
    }
  }
  
  public function testCheckExecuteSamples() {
    
    $samples_file = glob(__DIR__.'/../../samples/src/*.php');
    foreach ($samples_file as $f_name) {
      
      
      $start = time();
      $proc = new Process([trim(`which php`), $f_name]);
      $proc->run();
      if (  (time() - $start) > 10){
        throw new \Exception(['long time exectuion.', $f_name]);
      }
      if( ! $proc->getExitStatusCode() == 0
          || preg_match('/Stack trace/', ($err = stream_get_contents($proc->getErrout())))) {
        var_dump([$proc->getExitStatusCode(), $f_name, $err]);
        throw new \Exception([$proc->getExitStatusCode(), $f_name, $err]);
        //   var_dump([$proc->getCurrentProcess(), stream_get_contents($proc->getOutput()), stream_get_contents($proc->getErrout())]);exit;
      }
      $this->assertEquals(0, $proc->getExitStatusCode());
    }
  }
}