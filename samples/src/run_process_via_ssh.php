<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process(['ssh','root@192.168.2.1','sh -c date']);

$proc->run();

$fd = $proc->getOutput();
$out = stream_get_contents($fd);
var_dump($out);// -> Sat Mar 14 09:32:18 JST 2020