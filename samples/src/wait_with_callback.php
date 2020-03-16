<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';

$proc = new Process('php');
$proc->setInput('<?echo "Hello World"');
$proc->start();

$proc->wait(
  function ($status,$pipes){
    var_dump('wating');
    usleep(1000*10);
  },
  function ($status,$pipes){
    var_dump('success.');
  },
  function ($status,$pipes){
    var_dump('error occured');
  }
);




