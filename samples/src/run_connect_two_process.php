<?php

use SystemUtil\Process;
require_once __DIR__.'/../../src/Process.php';


$str = '<?php
    $stdout = fopen("php://stdout","w");
    fwrite($stdout,"HelloWorld");
    ';
$proc1 = new Process('php');
$proc1->setInput($str);
[$p1_out, $p1_err] = $proc1->start();

$proc2 = new Process('cat');
$proc2->setInput($p1_out);
$proc2->run();

$p2_out = $proc2->getOutput();

$str = stream_get_contents($p2_out);
