# Samples


- [Run process](#run_processphp)
    - [Run command](#run_commandphp)
    - [Get error outut](#get_error_oututphp)
    - [Get output](#get_outputphp)
    - [Run command with args](#run_command_with_argsphp)
    - [Run with environment](#run_with_environmentphp)
    - [Redirect command output](#redirect_command_outputphp)
    - [Redirect command error](#redirect_command_errorphp)
- [Pass input to command](#pass_input_to_commandphp)
    - [Redirect input to  file](#redirect_input_to__fillephp)
    - [Pass string as Input](#pass_string_as_Inputphp)
    - [Pass temp file to Input](#pass_temp_file_to_Inputphp)
    - [Execute php source string](#execute_php_source_stringphp)
- [None Blocking](#mone_blockingphp)
    - [Start and wait](#start_and_waitphp)
    - [Wait with callback](#wait_with_callbackphp)
- [Working with File stream]()
    - [Run conencted two_process](#run_connect_two_processphp)
- [Pipe](#run_process_pipephp)
    - [Run Process pipe](#run_process_pipephp)
    - [Run Process pipe chain](#run_process_pipe_chainphp)
- [Run process via ssh](#run_process_via_sshphp)
- [Run php body](#run_php_bodyphp)
- [Timeout](#timeoutphp)
    - [Wait](#waitphp)
    - [wait and timeout](#wait_and_timeoutphp)
- [Get executed process infomation](#get_executed_process_informationphp)


## execute_php_source_string.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "Hello World"');
$proc->run();
$fd = $proc->getErrorOutStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
```
## get_error_outut.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('no-exists-command');
$proc->run();
$fd = $proc->getErrorOutStream();
$out = stream_get_contents($fd);
var_dump($out);// -> sh: no-exists-command: command not found
```
## get_executed_process_information.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "Hello World"');
$proc->run();

$info = $proc->getCurrentProcess();
var_dump($info);
```
## get_output.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process(['echo', 'Hello World']);
$proc->run();
$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World\n
```
## none_blocking.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "Hello World"');
$proc->start();

while($proc->isRunning()){

}
$fd_out= $proc->getOutputStream();
```
## pass_input_to_command.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$input_string = 'echo "Hello World"';
$f_name = tempnam(sys_get_temp_dir());
file_put_contents($f_name, $input_string);


$proc = new Process('sh');
$proc->setInput($f_name);
$proc->run();

$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
```
## pass_string_as_Input.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$str = '<?php echo "Hello World"';
$proc = new Process('php');
$proc->setInput($str );
$proc->run();
$fd = $proc->getErrorOutStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
```
## pass_temp_file_to_Input.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$input_string = 'echo "Hello World"';
$fd_in = fopen('php://temp','w+');
fseek($fd_in, 0);

$proc = new Process('sh');
$proc->setInput($fd_in);
$proc->run();

$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
```
## redirect_command_error.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$f_name = tempnam(sys_get_temp_dir());


$proc = new Process('sh');

$proc->setErrout($f_name);

$proc->run();


$out = file_get_contents($f_name);
```
## redirect_command_output.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$f_name = tempnam(sys_get_temp_dir());


$proc = new Process('sh');
$proc->setOutput($f_name);
$proc->run();


$out = file_get_contents($f_name);
```
## redirect_input_to__fille.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$f_name = tempnam(sys_get_temp_dir());
file_put_contents($f_name,'echo Hello');


$proc = new Process('sh');
$proc->setInput($f_name);
$proc->run();


$out = file_get_contents($f_name);
```
## run_command.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process(['echo', 'HelloWorld']);
$proc->run();
```
## run_command_with_args.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';



$proc = new Process(['ls','-l','/']);
$proc->run();

$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);
```
## run_connect_two_process.php

```php
<?php

use SystemUtil\Process;

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

$p2_out = $proc2->getOutputStream();

$str = stream_get_contents($p2_out);
```
## run_php_body.php

```php
<?php
use SystemUtil\Process;

require_once '../../src/Process.php';



$proc = new Process('php');
$body = '<?php echo date("c");';
$proc->setInput($body);
$proc->run();
$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
```
## run_process.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('date');
$proc->run();
```
## run_process_pipe.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "Hello World"');

$proc->pipe('cat')
    ->pipe('cat')
    ->pipe('cat')
    ->wait();
  


$fd = $proc->getErrorOutStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
```
## run_process_pipe_chain.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process();
$proc->setInput();

$proc->setCmd('php')
    ->setInput('<?php echo "Hello World"')
    ->pipe('cat')
    ->pipe('cat')
    ->pipe('cat')
    ->wait();
  


$fd = $proc->getErrorOutStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Hello World
```
## run_process_via_ssh.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process(['ssh','root@192.168.2.1','sh -c date']);

$proc->run();

$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> Sat Mar 14 09:32:18 JST 2020
```
## run_with_environment.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';

$proc = new Process('sh',['Hello'=>'World']);
$proc->setInput('echo $Hello');

$proc->run();
$fd = $proc->getOutputStream();
$out = stream_get_contents($fd);
var_dump($out);// -> World\n
```
## start_and_wait.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "Hello World";');
$proc->start();
$proc->wait();

$fd_out= $proc->getOutputStream();
var_dump(stream_get_contents($fd_out));
```
## timeout.php

```php
<?php
use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process(['sleep','10']);
$proc->setTimeout(1);


$proc->start();
$proc->wait();
$fd_out= $proc->getOutputStream();

var_dump(stream_get_contents($fd_out));
```
## wait.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';

$proc = new Process('php');
$proc->setInput('<?php echo "Hello World";');
$proc->start();
$proc->wait();

$fd_out= $proc->getOutputStream();
var_dump(stream_get_contents($fd_out));
```
## wait_and_timeout.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';


$proc = new Process('php');
$proc->setInput('<?php echo "START";sleep(10); echo "END"');
$proc->setTimeout(2);

$proc->start();
$proc->wait();

$fd_out= $proc->getOutputStream();
$fd_err= $proc->getErrorOutStream();
var_dump(stream_get_contents($fd_out));
var_dump(stream_get_contents($fd_err));
```
## wait_with_callback.php

```php
<?php

use SystemUtil\Process;

require_once '../../src/Process.php';



$proc = new Process('php');
$proc->setInput('<?echo "Hello World"');
$proc->start();

$proc->wait(
  function ($status,$pipes){
    var_dump('wating');
    usleep(1000*10);
  },
  function ($status,$pipes){
    var_dump('error occured');
  },
  function ($status,$pipes){
    var_dump('success');
  }
);
```
