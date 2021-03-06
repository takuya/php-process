<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


$input_string = 'echo "Hello World"';
$f_name = tempnam(sys_get_temp_dir(),"temp");
file_put_contents($f_name, $input_string);


$proc = new Process('sh');
$proc->setInput($f_name);
$proc->run();

$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
