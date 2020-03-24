<?php

use SystemUtil\Process;

require_once __DIR__.'/../../src/Process.php';

$proc = new Process('sh',['Hello'=>'World']);
$proc->setInput('echo $Hello');

$proc->run();
$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> World\n