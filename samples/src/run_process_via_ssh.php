<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';

$proc = new Process(['ssh','localhost','sh -c date']);
$proc->run();

$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Sat Mar 14 09:32:18 JST 2020