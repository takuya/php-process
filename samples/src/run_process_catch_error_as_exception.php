<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';

try{
  $proc = new Process('___noexits_command_');
  $proc->setOnError(function($pr,$io){
    throw new \Exception('error');
  });
  $proc->run();
  
}catch (\Exception $e){
  echo 'error occured';
}
