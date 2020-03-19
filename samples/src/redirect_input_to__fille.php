<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


$f_name = tempnam(sys_get_temp_dir(), 'temp');
file_put_contents($f_name,'echo Hello');


$proc = new Process('sh');
$proc->setInput($f_name);
$proc->run();


$out = file_get_contents($f_name);
