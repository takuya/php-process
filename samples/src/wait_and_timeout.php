<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';

$proc = new Process('php');
$proc->setInput('<?php echo "START";sleep(10); echo "END"');
$proc->setTimeout(2);

$proc->start();
$proc->wait();

$fd_out= $proc->getOutputStream();
$fd_err= $proc->getErrorOutStream();
var_dump(stream_get_contents($fd_out));
var_dump(stream_get_contents($fd_err));


