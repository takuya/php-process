
  # Process class Documentation
  
  ```
  /**
 * Class Process
 * @license  GPL-3.0
 * @package  SystemUtl\Process
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

## __construct
Process constructor.    

### Descriptoin
```php
__construct ($cmd = null, $env = [], $cwd = null) :void
```

### Pramters
- ***$cmd***: 
- ***$env***:array 
- ***$cwd***: 


## getCurrentProcess
return process information executing or executed.    
 {    
proc: resource of proc_open,    
pipes: array of pipes ,    
descriptor: array of descrition ,    
start_time: int started timestamp of process    
stat:   array of last proc_get_status() called    

### Descriptoin
```php
getCurrentProcess () :void
```

### Pramters


## setTimeout
Set a timeout for process to limit max execution time.    

### Descriptoin
```php
setTimeout ($timeout = null) :void
```

### Pramters
- ***$timeout***:int 


## isUseMemory
get flag    

### Descriptoin
```php
isUseMemory () :bool
```

### Pramters


## setUseMemory
Set the flag whether using php://memory for buffreing stdio .    
This will be used IO seekable buffering, wrapper of default stream  ['pipe', 'r']    
true ; process use php://memory    
false; process use php://temp    

### Descriptoin
```php
setUseMemory (bool $use_memory) :void
```

### Pramters
- ***$use_memory***:bool 


## run
run Process, and wait for finished. Blocking method.    

### Descriptoin
```php
run () :void
```

### Pramters


## pipeProcess
pipe command process    

### Descriptoin
```php
pipeProcess (SystemUtil\Process $proc2) :SystemUtil\Process
```

### Pramters


## pipe
pipe command process    

### Descriptoin
```php
pipe ($cmd = null) :SystemUtil\Process
```

### Pramters


## getInput
Get input to be pass stdin of process    

### Descriptoin
```php
getInput () :void
```

### Pramters


## setInput
Set STIDN for process.    
$input should be string / fd / filename / stream.    
if input is not file name, input string pass as tempfile to process stdin.    

### Descriptoin
```php
setInput ($input = null) :SystemUtil\Process
```

### Pramters
- ***$input***:resource | string 


## getOutput
Get process Output as Stream.    

### Descriptoin
```php
getOutput () :void
```

### Pramters


## setOutput
Set command output.    
!! notice    
The default ['pipe','w'] cannot handle large data.    
When more than 1Mb Output Data Expected, you should use this method as direct output.    
If skip setOutput() and leave null, UnCatchable error will be occurred.    
And you will encounter many troubles.    
setOutput( $fd=fopen('php://temp', 'w+')) is better choice, than using default ['pipe', 'w'].    
For same reason, It might be better to avoid using this with 'php://memory' on large output.    

### Descriptoin
```php
setOutput ($output = null) :void
```

### Pramters


## getErrout
Get process Error output as Stream    

### Descriptoin
```php
getErrout () :void
```

### Pramters


## setErrout
Set process Error output as Stream    

### Descriptoin
```php
setErrout ($errout = null) :void
```

### Pramters
- ***$errout***:resource 


## getCmd
Get a process command.    

### Descriptoin
```php
getCmd () :void
```

### Pramters


## setCmd
Set a process command.    

### Descriptoin
```php
setCmd ($cmd = null) :void
```

### Pramters
- ***$cmd***:string | array 


## getCwd
Get a working directory set for process to be execute.    

### Descriptoin
```php
getCwd () :void
```

### Pramters


## setCwd
Set Process working directory.    

### Descriptoin
```php
setCwd ($cwd = null) :void
```

### Pramters
- ***$cwd***:string 


## getEnv
Get Process Environment Array    

### Descriptoin
```php
getEnv () :void
```

### Pramters


## setEnv
Set Environment Array    

### Descriptoin
```php
setEnv ($env = null) :void
```

### Pramters
- ***$env***:array 


## getOnExecuting
Get callback function on process waiting.    

### Descriptoin
```php
getOnExecuting () :void
```

### Pramters


## getOnError
Get callback function on error.    

### Descriptoin
```php
getOnError () :void
```

### Pramters


## setOnError
Set on error callback.    

### Descriptoin
```php
setOnError ($on_error = null) :void
```

### Pramters


## getOnSuccess
Get callback function on success.    

### Descriptoin
```php
getOnSuccess () :void
```

### Pramters


## setOnSuccess
Set callback function on success.    

### Descriptoin
```php
setOnSuccess ($on_success = null) :void
```

### Pramters


## getOnProcClosed
get callback function on after proc_close called.    

### Descriptoin
```php
getOnProcClosed () :Closure
```

### Pramters


## isRunning
check process is running.    

### Descriptoin
```php
isRunning () :void
```

### Pramters


## getTimeout
get current set timeout.    

### Descriptoin
```php
getTimeout () :void
```

### Pramters


## signal
Send signal to process id    

### Descriptoin
```php
signal (int $signal) :void
```

### Pramters
- ***$signal***:int 


## wait
Wait process.    

### Descriptoin
```php
wait (Closure $waiting = null, Closure $success = null, Closure $error = null) :void
```

### Pramters
- ***$waiting***:\Closure | null 
- ***$success***:\Closure | null 
- ***$error***:\Closure | null 


## setOnWaiting
Set Callback function called when waiting process.    

### Descriptoin
```php
setOnWaiting ($on_executing = null) :void
```

### Pramters


## start
Start Process. This is none blocking.    
array [ 1 -> stdout 2 -> stderr ], raw output, not buffered.    

### Descriptoin
```php
start () :void
```

### Pramters


## addEnv
Add Process Environment    

### Descriptoin
```php
addEnv ($k = null, $v = null) :void
```

### Pramters
- ***$k***:string 
- ***$v***:string 

