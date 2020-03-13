<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('no-exists-command');
$proc->run();
$fd = $proc->getErrout();
$out = stream_get_contents($fd);
var_dump($out);// -> sh: no-exists-command: command not found