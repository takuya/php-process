<?php

namespace SystemUtil;

use Closure;

/**
 * Class Process
 * @license  GPL-3.0
 * @package  SystemUtil\Process
 * @author   takuya_1st <http://github.com/takuya/php-process>
 * @since    2020-03-13
 * @version  1.0
 */
class Process {
  
  /**
   * @var \Closure
   */
  protected $on_error;
  /**
   * @var \Closure
   */
  protected $on_success;
  /**
   * @var \Closure
   */
  protected $on_executing;
  /**
   * @var \Closure
   */
  protected $on_proc_closed;
  /**
   * @var boolean
   */
  protected $use_memory = true;
  /**
   * @var array|string
   */
  protected $cmd;
  /**
   * @var array
   */
  protected $env;
  /**
   * @var string
   */
  protected $cwd;
  /**
   * @var resource
   */
  protected $output;
  /**
   * @var resource
   */
  protected $errout;
  /**
   * @var resource
   */
  protected $input;
  /** @var double */
  protected $max_execution_time = null;
  /** @var boolean */
  protected $is_successful = null;
  /**
   * @var object
   */
  private $current_process;
  
  /**
   * Process constructor.
   * @param       $cmd
   * @param array $env
   * @param null  $cwd
   */
  public function __construct( $cmd=null, $env = [], $cwd = null ) {
    $this->cwd = $cwd;
    $this->env = $env;
    $this->cmd = $cmd;
    $this->current_process = $this->processStruct();
  }
  
  /**
   * Struct of Process information as anonymous class
   * @return object
   */
  protected function processStruct() {
    return new class { // Struct
      
      public $proc           = null;
      public $pipes          = [];
      public $descriptor     = [];
      public $stat           = null;
      public $buffered_pipes = [];
      public $start_time     = null;
    };
  }
  
  /**
   * return process information executing or executed.
   *  {
   *      "proc": resource of proc_open,
   *      "pipes": array of pipes ,
   *      "descriptor": array of descrition ,
   *      "start_time": int started timestamp of process,
   *      "stat":   array of proc_get_status() last called result,
   *   } as anonymous class
   *
   * @return object object of anonymous class.
   */
  public function getCurrentProcess() {
    return $this->current_process;
  }
  
  /**
   * Set a timeout for process to limit max execution time.
   * @param int $timeout
   */
  public function setTimeout( $timeout ):void {
    if( is_numeric($timeout) ) {
      $this->max_execution_time = $timeout;
    }
  }
  
  /**
   * Get flag tempfile type.
   * @return bool
   */
  public function isUseMemory():bool {
    return $this->use_memory;
  }
  
  /**
   * Set the flag whether using php://memory for buffreing stdio.
   *  This will be used IO seekable buffering, wrapper of default stream  ['pipe', 'r']
   *  true : process use php://memory
   *  false: process use php://temp
   * @param bool $use_memory
   */
  public function setUseMemory( bool $use_memory ):void {
    $this->use_memory = $use_memory;
  }
  
  /**
   * run Process, and wait for finished. Blocking method.
   * @return resource[]  fd array, The struct is [ 1=> output , 2=> error]. Both of fd is 'buffered' to enable fseek.
   * @throws \Exception
   */
  public function run() {
    $this->process_exec();
    
    return [1 => $this->getOutput(), 2 => $this->getErrout()];
  }
  
  /**
   * @throws \Exception
   */
  protected function process_exec() {
    //
    $this->start_process();
    //
    $this->wait_process();
    
    return;
  }
  /**
   * pipe command process
   * @throws \Exception
   */
  public function pipeProcess( Process $proc2 ):Process{
    list($out,$err) = $this->start();
    $proc2->setInput($out);
    $proc2->start();
    return $proc2;
  }
  /**
   * pipe command process
   * @throws \Exception
   */
  public function pipe($cmd):Process {
    $proc2 = new Process($cmd, $this->getEnv(),$this->getCwd());
    $this->pipeProcess($proc2);
    return $proc2;
  }
  /**
   * @return object
   * @throws \Exception
   */
  protected function start_process() {
    $descriptor = [
      0 => $this->getInput() ?: ['pipe', 'r'],
      1 => $this->getOutput() ?: ['pipe', 'w'],
      2 => $this->getErrout() ?: ['pipe', 'w'],
    ];
    $process = proc_open($this->getCmd(), $descriptor, $pipes, $this->getCwd(), $this->getEnv());
    
    if( ! $process ) {
      throw new \Exception("proc_open failed");
    }
    $this->current_process->proc = $process;
    $this->current_process->pipes = $pipes;
    $this->current_process->descriptor = $descriptor;
    $this->current_process->stat = proc_get_status($process);
    $this->current_process->start_time = time();
  
    $pid = $this->current_process->stat['pid'];
  
    return $this->current_process;
  }
  
  /**
   * Get input to be pass stdin of process
   * @return resource|array
   */
  public function getInput() {
    return $this->input;
  }
  
  /**
   * Set STIDN for process.
   * $input should be string / fd / filename / stream.
   * if input is not file name, input string pass as tempfile to process stdin.
   * @param resource|string $input
   * @return  \SystemUtil\Process  return $this for method chaining
   */
  public function setInput( $input ):Process {
    if( is_resource($input) ) {
      $this->input = $input;
    } else {
      if( is_string($input) ) {
        if( ! preg_match('#[\n\*\<\>\|:\t\?]#', $input) // chars prohibited using in filename
            && preg_match('#(?<!\\\)(/|\\\)#', $input) ) {
          $this->input = ['file', $input, 'r'];
        } else {
          $fd_in = fopen('php://temp', 'w+');
          fwrite($fd_in, $input);
          fseek($fd_in, 0);
          $this->input = $fd_in;
        }
      }
    }
    return $this;
  }
  
  /**
   * Get process Output as Stream.
   * @return resource
   */
  public function getOutput() {
    if( ! $this->output && $this->current_process->proc && $this->current_process->stat['exitcode'] == 0 ) {
      $raw = $this->current_process->pipes[1];
      $buff = $this->getTempFd($this->use_memory);
      stream_copy_to_stream($raw, $buff);
      rewind($buff);
      $this->output = $buff;
      
      return $buff;
    }
    if( $this->output && is_resource($this->output) && $this->current_process->proc
        && $this->current_process->stat['exitcode'] == 0 ) {
      $meta = stream_get_meta_data($this->output);
      if( $meta['seekable'] == true ) {
        fseek($this->output, 0);
      }
    }
    
    return $this->output;
  }
  
  /**
   * Set command output.
   * !! notice
   * The default ['pipe','w'] cannot handle large data.
   * When more than 1Mb Output Data Expected, you should use this method as direct output.
   * If skip setOutput() and leave null, UnCatchable error will be occurred.
   * And you will encounter many troubles.
   * setOutput( $fd=fopen('php://temp', 'w+')) is better choice, than using default ['pipe', 'w'].
   * For same reason, It might be better to avoid using this with 'php://memory' on large output.
   * @param resource|string $output resource(fd) or string(filename)
   * @return \SystemUtil\Process
   */
  public function setOutput( $output ):Process {
    if( is_resource($output) ) {
      $this->output = $output;
    } else {
      if( is_string($output) ) {
        $this->output = ['file', $output, 'w+'];
      } else {
        // nothing
      }
    }
    return $this;
  }
  
  /**
   * @param bool $use_memory
   * @return bool|resource
   */
  private function getTempFd( $use_memory = true ) {
    if( $use_memory ) {
      return fopen('php://memory', 'w+');
    } else {
      return fopen('php://temp', 'w+');
    }
  }
  
  /**
   * Get process Error output as Stream
   * @return resource  resource
   */
  public function getErrout() {
    if( ! $this->errout && $this->current_process->proc && $this->current_process->stat['exitcode'] == 0 ) {
      $raw = $this->current_process->pipes[2];
      $buff = $this->getTempFd($this->use_memory);
      stream_copy_to_stream($raw, $buff);
      rewind($buff);
      $this->errout = $buff;
      
      return $buff;
    }
    if( $this->errout && is_resource($this->errout) && $this->current_process->proc
        && $this->current_process->stat['exitcode'] == 0 ) {
      $meta = stream_get_meta_data($this->errout);
      if( $meta['seekable'] == true ) {
        fseek($this->errout, 0);
      }
    }
    
    return $this->errout;
  }
  
  /**
   * Set process Error output as Stream
   * @param resource $errout
   */
  public function setErrout( $errout ):void {
    if( is_resource($errout) ) {
      $this->errout = $errout;
    } else {
      if( is_string($errout) ) {
        $this->errout = ['file', $errout, 'w+'];
      } else {
        // do nothing.
      }
    }
  }
  
  /**
   * Get a process command.
   * @return mixed
   */
  public function getCmd() {
    return $this->cmd;
  }
  
  /**
   * Set a process command, return $this for method chain.
   * @param string|array $cmd
   * @return \SystemUtil\Process
   */
  public function setCmd( $cmd ):Process {
    $this->cmd = $cmd;
    return $this;
  }
  
  /**
   * Get a working directory set for process to be execute.
   * @return string
   */
  public function getCwd() {
    return $this->cwd ?? getcwd();
  }
  
  /**
   * Set Process working directory.
   * @param string $cwd
   */
  public function setCwd( $cwd ):void {
    $this->cwd = $cwd;
  }
  
  /**
   * Get Process Environment Array
   * @return mixed
   */
  public function getEnv() {
    return $this->env;
  }
  
  /**
   * Set Environment Array
   * @param array $env
   */
  public function setEnv( $env ):void {
    $this->env = $env;
  }
  
  /**
   * inner function
   * wait process and call closure.
   * This is based on  sample procedure of proc_open.
   */
  protected function wait_process():void {
    
    $this->handleEvent('OnStart');
    // wating
    while($this->isRunning()) {
      $this->handleEvent('OnWait');
      usleep(1000*1);
      $this->checkTimeout();
    }
    $this->handleEvent('OnFinish');
    if( $this->current_process->stat['exitcode'] > 0 ) {
      $this->handleEvent('OnError');
      proc_close($this->current_process->proc);
      $this->handleEvent('OnProcClosed');
      
      return;
    }
    //
    $this->handleEvent('OnSuccess');
    proc_close($this->current_process->proc);
    $this->handleEvent('OnProcClosed');
    
    return;
  }
  
  /**
   * @param string $event
   */
  protected function handleEvent( string $event ) {
    $event = strtolower($event);
    switch($event) {
      case 'onstart':
        $this->handleOnStart();
        break;
      case 'onwait':
        $this->handleOnWait();
        break;
      case 'onerror':
        $this->handleOnError();
        break;
      case 'onsuccess':
        $this->handleOnSuccess();
        break;
      case 'onprocclosed':
        $this->handleOnProcClosed();
        break;
      case 'onfinish':
        $this->handleOnFinish();
    }
  }
  
  /**
   *
   */
  protected function handleOnStart() {
    if( $this->getInput() == null ) {
      $callback_on_start = $this->getInputFdCloseCallback();
      $callback_on_start($this->current_process->pipes);
    }
  }
  
  /**
   * @return \Closure
   */
  protected function getInputFdCloseCallback() {
    $func = function () { };
    if( $this->getInput() == null ) {
      $func = function ( $pipes ) {
        if( isset($pipes[0]) && preg_match('/stream/i', get_resource_type($pipes[0])) ) {
          fclose($pipes[0]);
          usleep(1*1);
        }
      };
    }
    
    return $func;
  }
  
  /**
   *
   */
  protected function handleOnWait() {
    $callback_on_every_waiting = $this->getOnExecuting();
    $callback_on_every_waiting(
      $this->current_process->stat,
      $this->current_process->pipes,
      $this->current_process->proc);
  }
  
  /**
   * Get callback function on process waiting.
   * @return \Closure --- -- function ( $status, $pipes, $prcoess ){..}
   */
  public function getOnExecuting() {
    $default = function ( $status, $pipes, $prcoess ) { };
    
    return $this->on_executing ?? $default;
  }
  
  /**
   *
   */
  protected function handleOnError() {
    $this->is_successful = false;
    $callback_on_error = $this->getOnError();
    $callback_on_error($this->current_process->proc, $this->current_process->buffered_pipes);
  }
  
  /**
   * Get callback function on error.
   * @return \Closure $on_error -- function ( $status, $pipes ){..}
   */
  public function getOnError() {
    $default = function ( $status, $pipes ) { };
    
    return $this->on_error ?? $default;
  }
  
  /**
   * Set on error callback.
   * @param \Closure $on_error  --  function ( $status, $pipes ){..}
   */
  public function setOnError( $on_error ):void {
    $this->on_error = $on_error;
  }
  
  /**
   *
   */
  protected function handleOnSuccess() {
    $this->is_successful = true;
    // call user func
    $callback_on_finished = $this->getOnSuccess();
    $callback_on_finished($this->current_process->stat, $this->current_process->buffered_pipes);
  }
  
  /**
   * Get callback function on success.
   * @return \Closure --  function ( $status, $pipes ){..}
   */
  public function getOnSuccess() {
    $default = function ( $status, $pipes ) { };
    
    return $this->on_success ?? $default;
  }
  
  /**
   * Set callback function on success.
   * @param \Closure $on_success -- function ( $status, $pipes ){..}
   */
  public function setOnSuccess( $on_success ):void {
    $this->on_success = $on_success;
  }
  
  /**
   *
   */
  protected function handleOnProcClosed() {
    $callback_on_proc_closed = $this->getOnProcClosed();
    $callback_on_proc_closed($this->current_process->descriptor[1], $this->current_process->descriptor[2]);
  }
  
  /**
   * get callback function on after proc_close called.
   * @return \Closure
   */
  public function getOnProcClosed():Closure {
    $default = function ( $out, $err ) {
      if( is_resource($out) && get_resource_type($out) != 'Unknown' ) {
        rewind($out);
      }
      if( is_resource($err) && get_resource_type($err) != 'Unknown' ) {
        rewind($err);
      }
      if( is_resource($err) && get_resource_type($err) != 'Unknown' ) {
        $this->current_process = $this->processStruct();
      }
    };
    
    return $this->on_proc_closed ?? $default;
  }
  
  /**
   *
   */
  protected function handleOnFinish() {
    // override pipe(out/err) to 'php://tempfile'.
    $this->current_process->buffered_pipes = $this->getMapPipeToTemp($this->current_process->pipes);
  }
  
  /**
   * @param $pipes
   * @return array array of string [in,out,err]
   */
  protected function getMapPipeToTemp( $pipes ):array {
    
    // stdout/stderr map to php://temp for fseek
    if( $this->getOutput() == null ) {
      $fd_out = $this->getTempFd($this->use_memory);
      stream_copy_to_stream($pipes[1], $fd_out);
      rewind($fd_out);
      $this->output = $fd_out;
    }
    if( $this->getErrout() == null ) {
      $fd_err = $this->getTempFd($this->use_memory);
      stream_copy_to_stream($pipes[2], $fd_err);
      rewind($fd_err);
      $this->errout = $fd_err;
    }
    
    return [$pipes[0] ?? null, $this->output ?? null, $this->errout ?? null];
  }
  
  /**
   * check process is running.
   * @return bool is running status
   */
  public function isRunning() {
    $proc_struct = $this->current_process;
    if( $proc_struct->stat && $proc_struct->stat['running'] ) {
      $proc_struct->stat = proc_get_status($proc_struct->proc);
    }
    
    return $proc_struct->proc
           && preg_match('/process/i', get_resource_type($proc_struct->proc))
           && $this->current_process->stat['running'];
  }
  
  /**
   * check execution time. and kill long execution.
   */
  protected function checkTimeout():void {
    if( ! $this->getTimeout() ) {
      return;
    }
    $this->signal(15);
  }
  
  /**
   * get current set timeout.
   * @return int timeout
   */
  public function getTimeout() {
    return $this->max_execution_time;
  }
  
  /**
   * Send signal to process id
   * @param int $signal
   * @return bool|void result code
   */
  public function signal( int $signal ) {
    if( $this->current_process->proc  && $this->isRunning()) {
      return proc_terminate($this->current_process->proc, $signal);
    }
    
    return;
  }
  
  /**
   * Wait process.
   * @param \Closure|null $waiting
   * @param \Closure|null $success
   * @param \Closure|null $error
   * @return  resource resource of std output
   */
  public function wait( Closure $waiting = null, Closure $success = null, Closure $error = null ) {
    if( $success ) {
      $this->setOnSuccess($success);
    }
    if( $error ) {
      $this->setOnError($error);
    }
    if( $waiting ) {
      $this->setOnWaiting($waiting);
    }
    $this->wait_process();

    return  $this->getOutput();
  }
  
  /**
   * Set Callback function called when waiting process.
   * @param \Closure $on_executing -- function ( $status, $pipes, $prcoess ){..}
   */
  public function setOnWaiting( $on_executing ):void {
    $this->on_executing = $on_executing;
  }
  
  /**
   * Start Process. This is none blocking.
   * array [ 0 -> stdout 1-> stderr ], raw output, not buffered.
   * @return resource[] array of resouorce [ 0 -> stdout,  1-> stderr ]
   * @throws \Exception
   */
  public function start():array {
    if ( !$this->isRunning() ){
      $this->start_process();
    }
    
    return [
      $this->getOutput() ?: $this->current_process->pipes[1],
      $this->getErrout() ?: $this->current_process->pipes[2],
    ];
  }
  
  /**
   * Add Process Environment
   * @param string $k
   * @param string $v
   */
  public function addEnv( $k, $v ):void {
    $this->env = $this->env ?? [];
    $this->env[$k] = $v;
  }
}