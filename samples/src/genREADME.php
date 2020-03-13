<?php

$list = glob(__DIR__.'/*.php');
$list = array_diff($list, [__FILE__]);

foreach ($list as $name){
  
  $basename = basename($name);
  
  echo "## ${basename}\n";
  $body = file_get_contents($name);
  $body = trim($body);
  echo "\n";
  echo '```php'."\n";
  echo $body."\n";
  echo '```'."\n";
  
  


  
}