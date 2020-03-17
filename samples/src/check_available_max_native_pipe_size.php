<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


function checkPipe1(){
  printf("-- checking stdout.\n");
  foreach ( range( 256*256-4,256*257) as $i){
    $size = $i;
    $proc = new Process(['head', '-c', $size, '/dev/urandom']);
    $proc->setTimeout(1);
    $proc->run();
    if($proc->canceled()) {
      echo "{$size}, failed .\n";
      printf("It is %d , seems to be max limit for proc_open [1=>['pipe'=>'w']].\n", $size-1);
      break;
    }else{
      echo "{$size}, ok.\n";
    }
  }
  
}
function checkPipe2(){
  printf("-- checking stderr.\n");
  foreach ( range( 256*256-4,256*257) as $i){
    $size = $i;
    $proc = new Process('php');
    $proc->setInput(sprintf('<?php
    $fd=fopen("php://stderr","w+");
    foreach( range(0,%d) as $i ){ fwrite($fd, 0); };
    fflush($fd);fclose($fd);',$size));
    
    $proc->setTimeout(1);
    $proc->run();
    if($proc->canceled()) {
      echo "{$size}, failed .\n";
      printf("It is %d , seems to be max limit for proc_open [2=>['pipe'=>'w']].\n", $size-1);
      break;
    }else{
      $out = $proc->getErrout();
      printf ("write %d bytes, error output %d bytes.\n", $size, fstat($out)['size']);
    }
  }
}


checkPipe1();
checkPipe2();
