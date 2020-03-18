<?php


function fstat_c( $res ) {
  
  $st = (fstat($res));
  $st2 = array_intersect_key(
    $st,array_flip(
      [
        'dev',
        'ino',
        'mode',
        'nlink',
        'uid',
        'gid',
        'rdev',
        'size',
        'atime',
        'mtime',
        'ctime',
        'blksize',
        'blocks',
      ]
    ));
  $st2 =get_resource_type($res) != 'stream' ? $st2 : array_merge($st2,stream_get_meta_data($res));
  
  return $st2;
}


// alias

function dd( ...$args){
  var_dump($args);
  exit();
}
function dump( ...$args){
  var_dump($args);
}
