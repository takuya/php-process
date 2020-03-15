<?php

use SystemUtil\Process;

require_once '../../src/Process.php';



foreach ( range( 256*256-10,256*257) as $i){
  $size = $i;
  $proc = new Process(['head', '-c', $size, '/dev/urandom']);
  $proc->setTimeout(1);
  $proc->run();
  if($proc->canceled()) {
    echo "{$size}, failed .\n";
    printf("It is %d , seems to be max limit for proc_open [1=>['pipe'=>'w']].\n", $size-1);
    break;
  }else{
    echo "{$size}, ok .\n";
  }
  
}
