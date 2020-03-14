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

[ → READ More Sample for usage ](https://github.com/takuya/php-process/blob/master/samples/README.md)

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
