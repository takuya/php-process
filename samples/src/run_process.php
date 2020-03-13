<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('date');
$proc->run();

