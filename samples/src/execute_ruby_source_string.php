<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('ruby');
$proc->setInput('puts :Hello');
$proc->run();
$fd = $proc->getOutput();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World