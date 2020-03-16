<?php
use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


$proc = new Process(['sleep','10']);
$proc->setTimeout(1);

$proc->start();
$proc->wait();
$fd_out= $proc->getOutput();

var_dump(stream_get_contents($fd_out));


