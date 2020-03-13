<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$input_string = 'echo "Hello World"';
$f_name = tempnam(sys_get_temp_dir());
file_put_contents($f_name, $input_string);


$proc = new Process('sh');
$proc->setInput($f_name);
$proc->run();

$fd = $proc->getOutput();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
