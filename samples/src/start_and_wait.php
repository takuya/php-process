<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "Hello World";');
$proc->start();
$proc->wait();

$fd_out= $proc->getOutput();
var_dump(stream_get_contents($fd_out));


