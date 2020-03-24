<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';


$str = '<?php echo "Hello World"';
$proc = new Process('php');
$proc->setInput($str );
$proc->run();
$fd = $proc->getErrorOutStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
