php-Process for proc_open
=================

The Process class component executes command in proc_open.
  

Sample
------
```php
<?php

$proc1 = new Process('sh');

$fd_out = $proc1->setInput('echo HelloWorld')
  ->pipe('cat')
  ->pipe('cat')
  ->pipe(['grep', 'Hello'])
  ->wait();

$ret = stream_get_contents($fd_out);

```

Feature
----

### Buffered IO for STDOUT/STDERR

Process return bufuffered IO for read/write

Method will return stream.

```php

<?php
$proc = new Process(['echo', 'HelloWorld']);
$fd_out = $proc->run();

$output = stream_get_contents($fd_out);
// you can reuse, re-read output  
fseek($fd_out,0);
$str = stream_get_contents($fd_out);
```

### Chain Method for Pipe Command

Process#pipe() can PIPE programs.

```php

<?php
$proc = new Process(['echo', 'HelloWorld']);
$fd_out =  $proc->pipe('cat')
            ->pipe('cat')
            ->pipe('cat')
            ->pipe('cat')
            ->wait();
```

### A Simple and Single File for require.

No required packages.

A Single File ` require_once 'src/Process.php' `  to use  Process class 

Process class is written by vanilla php. No extra no composer.


Resources
---------

  * [Documentation]()
  * [Contributing]()
  * [Report issues](https://github.com/takuya/php-process/issues) and
    [send Pull Requests](https://github.com/takuya/php-process/pulls)
