<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('python');
$proc->setInput('
import sys
print(sys.path)
');
$proc->run();
$fd = $proc->getOutput();
$out = stream_get_contents($fd);
var_dump($out);
