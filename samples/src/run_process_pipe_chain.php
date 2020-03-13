<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process();
$proc->setInput();

$proc->setCmd('php')
    ->setInput('<?php echo "Hello World"')
    ->pipe('cat')
    ->pipe('cat')
    ->pipe('cat')
    ->wait();
  


$fd = $proc->getErrout();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
