<?php

namespace Tests\Feature\Process;

use Tests\TestCase;
use SysUtil\ProcessExec\Process;

class ProcessExecuteErrorOutputTest  extends TestCase {
  
  public function testStdinFDStdoutTempStdErrorTempRedirect(){
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->run();
    //
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    //
    $this->assertEquals('HelloWorld', $out);
    $this->assertEquals('HelloError', $err);
  }
  public function testStdinFDStdoutFDStdErrorTempRedirect(){
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    $fout = fopen('php://temp', 'w+');
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOutput($fout);
    $proc->run();
    //
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    //
    $this->assertEquals('HelloWorld', $out);
    $this->assertEquals('HelloError', $err);
  }
  public function testStdinFDStdoutFDStdErrorFDRedirect(){
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    $fout = fopen('php://temp', 'w+');
    $ferr = fopen('php://temp', 'w+');
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setOutput($fout);
    $proc->setErrout($ferr);
    $proc->run();
    //
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    //
    $this->assertEquals('HelloWorld', $out);
    $this->assertEquals('HelloError', $err);
  }
  public function testStdinFDStdoutTempStdErrorFDRedirect(){
    $str = '<?php
    $stdout = fopen("php://stdout","w");
    $stderr = fopen("php://stderr","w");
    fwrite($stdout,"HelloWorld");
    fwrite($stderr,"HelloError");';
    
    $fin = fopen('php://temp', 'r+');
    fwrite($fin, $str);
    rewind($fin);
    $ferr = fopen('php://temp', 'w+');
    $proc = new Process('php');
    $proc->setInput($fin);
    $proc->setErrout($ferr);
    $proc->run();
    //
    $out = stream_get_contents($proc->getOutput());
    $err = stream_get_contents($proc->getErrout());
    //
    $this->assertEquals('HelloWorld', $out);
    $this->assertEquals('HelloError', $err);
  }

}
