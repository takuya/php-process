<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


$proc = new Process(['echo', 'HelloWorld']);
$proc->run();
