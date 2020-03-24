<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


$proc = new Process(['ls','-l','/']);
$proc->run();

$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);


