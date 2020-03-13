<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "Hello World"');
$proc->run();

$info = $proc->getCurrentProcess();
var_dump($info);
