php-Process for proc_open
=================

The Process class component executes command in proc_open.

![<CircleciTest>](https://circleci.com/gh/takuya/php-process.svg?style=svg)

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

[ → READ More Sample for usage ](https://github.com/takuya/php-process/blob/master/samples/README.md)

Installation
----
```sh
composer require takuya/process
```

Features
----

### Buffered IO stream for STDOUT/STDERR

Process will return buffered IO for read/write

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
### Pseudo-thread style programming
```php
<?php
$proc = new Process('sh sleep.sh');
$proc->start();
echo 'started';
$proc->join();
```


### Chain Method for Pipe Command

Process#pipe() can PIPE programs.

Implicite connect pipe stdout -> stdin
```php

<?php
$proc = new Process(['echo', 'HelloWorld']);
$fd_out =  $proc->pipe('cat')
            ->pipe('cat')
            ->pipe('cat')
            ->pipe('cat')
            ->wait();
```
Explicitly Pipe,  connect (Proc1#)stdout -> stdin(@Proc2)
```php
<?php
$proc1 = new Process(['echo', 'HelloWorld']);
$proc2 = new Process(['cat']);
[$p1_out,$p1_err] = $proc1->start();
$proc2->setInput($p1_out);
$proc2->start();
$proc2->wait();
$proc1->wait();
```
Notice: `$proc2->wait()` call first, to avoid long locking , to run two process in parallel.
The reason is `Process` class adopt implied IOBuffering. calling `wait()` means that runs stream buffering loop until process endup;

### A Simple and Single File for requParallels

No packages required.

A Single File ` require_once 'src/Process.php' ` need to use. 

```php

<?php
require_once 'src/Process.php';

``` 

Using without Composer.phar or Such a package manager, Just write require_once.
 
Process class is written by ***vanilla php***. No extra packages. No pear, No composer, No packages need to install.


More Samples
---


More Usage , Read files in this procjet `/samples`, `/tests/Features`  and `./docs`.


Resources
---------

  * [Documentation](https://github.com/takuya/php-process/blob/master/docs/procss.funcs.md)
  * [Contributing](https://github.com/takuya/php-process/)
  * [Report issues](https://github.com/takuya/php-process/issues) and
    [send Pull Requests](https://github.com/takuya/php-process/pulls)
