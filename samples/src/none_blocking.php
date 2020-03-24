<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "Hello World"');
$proc->start();

while($proc->isRunning()){

}
$fd_out= $proc->getOutputStream();



