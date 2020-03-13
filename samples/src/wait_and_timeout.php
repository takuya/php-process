<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "START";sleep(10); echo "END"');
$proc->setTimeout(2);

$proc->start();
$proc->wait();

$fd_out= $proc->getOutput();
$fd_err= $proc->getErrout();
var_dump(stream_get_contents($fd_out));
var_dump(stream_get_contents($fd_err));


