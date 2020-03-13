<?php

use SystemUtil\Process;

require_once '../../src/Process.php';



$proc = new Process(['ls','-l','/']);
$proc->run();

$fd = $proc->getOutput();
$out = stream_get_contents($fd);
var_dump($out);


