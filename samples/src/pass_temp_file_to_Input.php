<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$input_string = 'echo "Hello World"';
$fd_in = fopen('php://temp','w+');
fseek($fd_in, 0);

$proc = new Process('sh');
$proc->setInput($fd_in);
$proc->run();

$fd = $proc->getOutput();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
