<?php

namespace Tests\Feature\Callbacks;

use Tests\TestCase;
use SystemUtil\Process;

class ProcessExecuteOnOutPutChangedCallbackTest extends TestCase {

  public function testOnOutPutChangedCallbackWithBlockingAndReUseResponseBuffered(){
    $php_src = '<?php
      $err = fopen("php://stderr","w+");
      
      foreach( [0,1,2,3,4] as $i ){
        echo sprintf("No.%02d: Hello\n", $i);
        fwrite($err, sprintf("No.%02d: Error\n", $i));
        // flush();
        usleep( 10 );
      }
      ';
    $proc = new Process('php');
    $proc->setInput($php_src);
    $proc->setOnOutputChanged(function ($str){
      $cnt = preg_match_all('/Hello\n/', $str);
      $this->assertGreaterThan(0, $cnt);
      $this->assertLessThanOrEqual(5, $cnt);
    });
    $proc->run();
    $str = stream_get_contents($proc->getOutput());
    $cnt = preg_match_all('/Hello\n/',$str);
    $this->assertEquals(5, $cnt);
  }
  public function testOnOutPutChangedCallbackWithBlockingWithNotBuffered(){
    $php_src = '<?php
      $err = fopen("php://stderr","w+");
      
      foreach( [0,1,2,3,4] as $i ){
        echo sprintf("No.%02d: Hello\n", $i);
        fwrite($err, sprintf("No.%02d: Error\n", $i));
        // flush();
        usleep( 10 );
      }
      ';
    $size = 5;
    $callback = function ($str) use(&$size){
      $cnt = preg_match_all('/Hello\n/', $str);
      $this->assertGreaterThan(0, $cnt);
      $this->assertLessThanOrEqual($size, $cnt);
      $size= $size-$cnt;
    };
    $proc = new Process('php');
    $proc->setInput($php_src);
    $proc->setOnOutputChanged($callback,["buffered"=>false]);
    $proc->run();
    
    $str = stream_get_contents($proc->getOutput());
    $cnt2 = preg_match_all('/Hello\n/',$str);
    // var_dump([$cnt2,$size,$str]);
    $this->assertEquals($size, $cnt2);
  }
  public function testOnOutPutChangedCallbackNoBlokingNoBuffering(){
    $php_src = '<?php
      $err = fopen("php://stderr","w+");
      
      foreach( [0,1,2,3,4] as $i ){
        echo sprintf("No.%02d: Hello\n", $i);
        fwrite($err, sprintf("No.%02d: Error\n", $i));
        // flush();
        usleep( 1000*100 );
      }
      ';
    $size = 5;
    $outstr = '';
    $callback = function ($str) use(&$outstr){
      $outstr .=$str;
    };
    $proc = new Process('php');
    $proc->setInput($php_src);
    $proc->setOnOutputChanged($callback,["buffered"=>false, 'blocking'=>false]);
    $proc->run();
    
    $outstr .= stream_get_contents($proc->getOutput());
    $cnt2 = preg_match_all('/Hello\n/',$outstr );
    $this->assertEquals($size, $cnt2);
  }
  
  public function testOnOutPutChangedCallbackNoBlokingWithBuffering(){
    $php_src = '<?php
      $err = fopen("php://stderr","w+");
      
      foreach( [0,1,2,3,4] as $i ){
        echo sprintf("No.%02d: Hello\n", $i);
        fwrite($err, sprintf("No.%02d: Error\n", $i));
        // flush();
        usleep( 1000 );
      }
      ';
    $size = 5;
    $outstr = '';
    $callback_called = false;
    $callback = function ($str) use(&$callback_called){
      $callback_called=true;
    };
    $proc = new Process('php');
    $proc->setWaitTime(1000);
    $proc->setInput($php_src);
    $proc->setOnOutputChanged($callback,["buffered"=>true, 'blocking'=>false]);
    $proc->run();
    
    $outstr .= stream_get_contents($proc->getOutput());
    $cnt2 = preg_match_all('/Hello\n/',$outstr );

    
    $this->assertEquals(true, $callback_called);
    $this->assertEquals($size, $cnt2);
  }
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
  
}