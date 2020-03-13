<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$f_name = tempnam(sys_get_temp_dir());


$proc = new Process('sh');
$proc->setOutput($f_name);
$proc->run();


$out = file_get_contents($f_name);
