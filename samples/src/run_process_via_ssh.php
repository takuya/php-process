<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';

$proc = new Process(['ssh','127.0.0.1','sh -c date']);
$proc->run();

$fd = $proc->getOutput();
$out = stream_get_contents($fd);
var_dump($out);// -> Sat Mar 14 09:32:18 JST 2020