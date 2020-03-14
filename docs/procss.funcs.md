
  # Process class Documentation
  
  ```
  /**
 * Class Process
 * @license  GPL-3.0
 * @package  SystemUtil\Process
 * @author   takuya_1st <http://github.com/takuya/php-process>
 * @since    2020-03-13
 * @version  1.0
 */
  ```
  

# public methods

- [__construct](#__construct)
- [getCurrentProcess](#getCurrentProcess)
- [setTimeout](#setTimeout)
- [isUseMemory](#isUseMemory)
- [setUseMemory](#setUseMemory)
- [run](#run)
- [pipeProcess](#pipeProcess)
- [pipe](#pipe)
- [getInput](#getInput)
- [setInput](#setInput)
- [getOutput](#getOutput)
- [setOutput](#setOutput)
- [getErrout](#getErrout)
- [setErrout](#setErrout)
- [getCmd](#getCmd)
- [setCmd](#setCmd)
- [getCwd](#getCwd)
- [setCwd](#setCwd)
- [getEnv](#getEnv)
- [setEnv](#setEnv)
- [getOnExecuting](#getOnExecuting)
- [getOnError](#getOnError)
- [setOnError](#setOnError)
- [getOnSuccess](#getOnSuccess)
- [setOnSuccess](#setOnSuccess)
- [getOnProcClosed](#getOnProcClosed)
- [isRunning](#isRunning)
- [getTimeout](#getTimeout)
- [signal](#signal)
- [wait](#wait)
- [setOnWaiting](#setOnWaiting)
- [start](#start)
- [addEnv](#addEnv)

# __construct
Process constructor.    
```php
__construct ($cmd = null, $env = [], $cwd = null) :void
```
### Pramters

- ***$cmd***: 
- ***$env***:array 
- ***$cwd***: 




# getCurrentProcess
return process information executing or executed.    
 {    
"proc": resource of proc_open,    
"pipes": array of pipes ,    
"descriptor": array of descrition ,    
"start_time": int started timestamp of process,    
"stat":   array of proc_get_status() last called result,    
} as anonymous class    
* @return object object of anonymous class.    
```php
getCurrentProcess () :object
```

### Return

object : object of anonymous class.


# setTimeout
Set a timeout for process to limit max execution time.    
```php
setTimeout ($timeout = null) :void
```
### Pramters

- ***$timeout***:int 




# isUseMemory
Get flag tempfile type.    
```php
isUseMemory () :bool
```

### Return

bool 


# setUseMemory
Set the flag whether using php://memory for buffreing stdio.    
This will be used IO seekable buffering, wrapper of default stream  ['pipe', 'r']    
true : process use php://memory    
false: process use php://temp    
```php
setUseMemory (bool $use_memory) :void
```
### Pramters

- ***$use_memory***:bool 




# run
run Process, and wait for finished. Blocking method.    
```php
run () :resource[]
```

### Return

resource[] : fd array, The struct is [ 1=> output , 2=> error]. Both of fd is 'buffered' to enable fseek.


# pipeProcess
pipe command process    
```php
pipeProcess (SystemUtil\Process $proc2) :void
```




# pipe
pipe command process    
```php
pipe ($cmd = null) :void
```




# getInput
Get input to be pass stdin of process    
```php
getInput () :resource|array
```

### Return

resource|array 


# setInput
Set STIDN for process.    
$input should be string / fd / filename / stream.    
if input is not file name, input string pass as tempfile to process stdin.    
```php
setInput ($input = null) :\SystemUtil\Process
```
### Pramters

- ***$input***:resource | string 

### Return

\SystemUtil\Process : return $this for method chaining


# getOutput
Get process Output as Stream.    
```php
getOutput () :resource
```

### Return

resource 


# setOutput
Set command output.    
!! notice    
The default ['pipe','w'] cannot handle large data.    
When more than 1Mb Output Data Expected, you should use this method as direct output.    
If skip setOutput() and leave null, UnCatchable error will be occurred.    
And you will encounter many troubles.    
setOutput( $fd=fopen('php://temp', 'w+')) is better choice, than using default ['pipe', 'w'].    
For same reason, It might be better to avoid using this with 'php://memory' on large output.    
```php
setOutput ($output = null) :\SystemUtil\Process
```

### Return

\SystemUtil\Process 


# getErrout
Get process Error output as Stream    
```php
getErrout () :resource
```

### Return

resource : resource


# setErrout
Set process Error output as Stream    
```php
setErrout ($errout = null) :void
```
### Pramters

- ***$errout***:resource 




# getCmd
Get a process command.    
```php
getCmd () :mixed
```

### Return

mixed 


# setCmd
Set a process command, return $this for method chain.    
```php
setCmd ($cmd = null) :\SystemUtil\Process
```
### Pramters

- ***$cmd***:string | array 

### Return

\SystemUtil\Process 


# getCwd
Get a working directory set for process to be execute.    
```php
getCwd () :string
```

### Return

string 


# setCwd
Set Process working directory.    
```php
setCwd ($cwd = null) :void
```
### Pramters

- ***$cwd***:string 




# getEnv
Get Process Environment Array    
```php
getEnv () :mixed
```

### Return

mixed 


# setEnv
Set Environment Array    
```php
setEnv ($env = null) :void
```
### Pramters

- ***$env***:array 




# getOnExecuting
Get callback function on process waiting.    
```php
getOnExecuting () :\Closure
```

### Return

\Closure : --- -- function ( $status, $pipes, $prcoess ){..}


# getOnError
Get callback function on error.    
```php
getOnError () :\Closure
```

### Return

\Closure : $on_error -- function ( $status, $pipes ){..}


# setOnError
Set on error callback.    
```php
setOnError ($on_error = null) :void
```




# getOnSuccess
Get callback function on success.    
```php
getOnSuccess () :\Closure
```

### Return

\Closure : --  function ( $status, $pipes ){..}


# setOnSuccess
Set callback function on success.    
```php
setOnSuccess ($on_success = null) :void
```




# getOnProcClosed
get callback function on after proc_close called.    
```php
getOnProcClosed () :\Closure
```

### Return

\Closure 


# isRunning
check process is running.    
```php
isRunning () :bool
```

### Return

bool : is running status


# getTimeout
get current set timeout.    
```php
getTimeout () :int
```

### Return

int : timeout


# signal
Send signal to process id    
```php
signal (int $signal) :bool|void
```
### Pramters

- ***$signal***:int 

### Return

bool|void : result code


# wait
Wait process.    
```php
wait (Closure $waiting = null, Closure $success = null, Closure $error = null) :resource
```
### Pramters

- ***$waiting***:\Closure | null 
- ***$success***:\Closure | null 
- ***$error***:\Closure | null 

### Return

resource : resource of std output


# setOnWaiting
Set Callback function called when waiting process.    
```php
setOnWaiting ($on_executing = null) :void
```




# start
Start Process. This is none blocking.    
array [ 0 -> stdout 1-> stderr ], raw output, not buffered.    
```php
start () :resource[]
```

### Return

resource[] : array of resouorce [ 0 -> stdout,  1-> stderr ]


# addEnv
Add Process Environment    
```php
addEnv ($k = null, $v = null) :void
```
### Pramters

- ***$k***:string 
- ***$v***:string 



