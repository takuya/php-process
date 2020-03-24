<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


$proc = new Process('no-exists-command');
$proc->run();
$fd = $proc->getErrorOutStream();
$out = stream_get_contents($fd);
var_dump($out);// -> sh: no-exists-command: command not found