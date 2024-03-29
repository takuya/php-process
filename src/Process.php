<?php

namespace SystemUtil;

use Closure;


/**
 * Class Process
 * @license  GPL-3.0 or later
 * @package  SystemUtil\Process
 * @author   takuya_1st <https://github.com/takuya/php-process>
 * @since    2020-03-13
 * @version  1.0
 */
class Process {
  
  const STDOUT=1;
  const STDERR=2;
  const ERR=self::STDERR;
  
  /**
   * @var int microsecond.
   */
  protected $wait_time = 1000*0.5;
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
   * @var \SystemUtil\Process
   */
  protected $pipedNextProcess;
  /**
   * @var bool flag enables I/O buffering on wait. default = true.
   */
  protected $enable_buffering_on_wait = false;
  /**
   * @var object
   */
  private $current_process;
  /**
   * @var array
   */
  private $on_changed   = [];
  
  /**
   * Process constructor.
   * @param       $cmd
   * @param array $env
   * @param null  $cwd
   */
  public function __construct( $cmd = null, $env = [], $cwd = null ) {
    $this->cwd = $cwd;
    $this->env = $env;
    $this->cmd = $cmd;
    $this->current_process = $this->processStruct();
    $this->enableBufferingOnWait();
    $this->env = ! empty($this->env) ? $this->env : getenv();
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
  
  public function enableBufferingOnWait():void {
    $this->enable_buffering_on_wait = true;
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
   * @return object object of anonymous class.
   */
  public function getCurrentProcess() {
    return $this->current_process;
  }
  
  /**
   * Set a timeout for process to limit max execution time.
   * @param double $timeout
   * @return \SystemUtil\Process
   */
  public function setTimeout( $timeout ):Process {
    if( is_double($timeout) ) {
      $this->max_execution_time = $timeout;
    } else {
      if( is_numeric($timeout) ) {
        $timeout = doubleval($timeout);
        $this->max_execution_time = $timeout;
      }
    }
    
    return $this;
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
   * @param \Closure|null $on_changed --  function ( $status_code , string $buff ){..} for Symfony::Process Compatible.
   * @return resource[]  fd array, The struct is [ 1=> output , 2=> error]. Both of fd is 'buffered' to enable fseek.
   * @throws \Exception
   */
  public function run( $func = null ) {
    if (!is_null($func) ){
      return $this->run_with_callback($func);
    }
    
    $this->process_exec();
    return [1 => $this->getOutputStream(), 2 => $this->getErrorOutStream()];
  }
  /*
   * Run process with callback.
   * @param \Closure|null $on_changed --  function ( $status_code , string $buff ){..} for Symfony::Process Compatible.
   */
  public function run_with_callback($callback) {
    $this->setOnErrputChanged( function($buff) use($callback){$callback(2,$buff);} );
    $this->setOnOutputChanged( function($buff) use($callback){$callback(1,$buff);} );
    $this->process_exec();
  
    return [ 1=> $this->getOutputStream(), 2 => $this->getErrorOutStream()];
  }
  
  /**
   * @throws \Exception
   */
  protected function process_exec() {
    //
    $this->isNotRunning() && $this->start_process();
    //
    $this->wait_process();
    
    return;
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isNotRunning() {
    return ! $this->isRunning();
  }
  
  /**
   * check process is running.
   * @return bool is running status
   */
  public function isRunning() {
    $proc_struct = $this->current_process;
    if( $this->isNotStarted() ) {
      return false;
    }
    // update status when running.
    if( $this->isStarted() && $this->isNotFinished() && $this->isProcessOpen() ) {
      // Call proc_get_status() on to finished $proc that lost real [exit_code] and get only '-1'.
      // Stay conscious of number and times proc_get_status() called.
      // Be Carefully to call proc_get_status() to avoid lost [exit_code].
      $proc_struct->stat = proc_get_status($proc_struct->proc);
    }
    
    // return last running status, proc_get_status() called.
    return $this->current_process->stat['running'];
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isNotStarted() {
    return ! $this->isStarted();
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isStarted() {
    return $this->current_process->proc != null;
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isNotFinished() {
    return ! $this->isFinished();
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isFinished() {
    
    return $this->current_process->start_time !== null
           && $this->current_process->stat
           && $this->current_process->stat['running'] === false;
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isProcessOpen() {
    return is_resource($this->current_process->proc) === true
           && get_resource_type($this->current_process->proc) == 'process';
  }
  
  /**
   * @return object
   * @throws \Exception
   */
  protected function start_process() {
    $descriptor = [
      0 => $this->getInput() ?: ['pipe', 'r'],
      1 => $this->getOutputStream() ?: ['pipe', 'w'],
      2 => $this->getErrorOutStream() ?: ['pipe', 'w'],
    ];
    $process = proc_open($this->getCommandLine(), $descriptor, $pipes, $this->getCwd(), $this->getEnv());
    if( ! $process ) {
      throw new \Exception("proc_open failed");
    }
    $this->current_process->proc = $process;
    $this->current_process->pipes = $pipes;
    $this->current_process->descriptor = $descriptor;
    $this->current_process->stat = proc_get_status($process);
    $this->current_process->start_time = microtime(true);
    
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
        if( $this->isFileNameString($input) ) {
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
   * Set command output.
   * !! notice
   * The default ['pipe','w'] cannot handle large data.
   * When more than 65536 bytes Output expected, you should use this method as direct output.
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
  
  protected function getOutStream( int $i ) {
    $out = ( $i === 1 ? $this->output : ( $i === 2 ? $this->errout : null ) );
    if( $this->isFinished() ) {
      if( $out == null ) {
        if( $this->isProcessClosed() ) {
          $out = $out ?? $this->getBufferedPipe($i);
          if( is_array($out) && $out[0] == 'file' ) {
            $out = fopen($out[1], 'r+');
          }
        } else {
          $out = $this->getBufferedPipe($i) ?? $this->getTempFd($this->use_memory);
          if( ! $this->canceled() ) {
            stream_copy_to_stream($this->getPipe($i), $out);
          }
          $this->current_process->buffered_pipes[$i] = $out;
        }
      }
      is_resource($out)
      && get_resource_type($out) == 'stream'
      && stream_get_meta_data($out)['seekable']
      && rewind($out);
    } else if ( is_null( $out ) && $this->isRunning()){
      return $this->current_process->pipes[$i];
    }
    
    return $out;
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isProcessClosed() {
    return ! $this->isProcessOpen();
  }
  
  protected function getBufferedPipe( int $i ) {
    if( empty($this->current_process->buffered_pipes[$i]) ) {
      return null;
    }
    
    return $this->current_process->buffered_pipes[$i];
  }
  
  /**
   * @param bool $use_memory
   * @return bool|resource
   */
  private function getTempFd( $use_memory = true ) {
    return fopen($use_memory ? 'php://memory' : 'php://temp', 'w+');
  }
  
  /**
   * process is cancled by signal.
   * @return bool is process signaled.
   */
  public function canceled():bool {
    return $this->isNotRunning() && $this->current_process->stat['signaled'];
  }
  
  protected function getPipe( int $i ) {
    return $this->current_process->pipes[$i] ?? null;
  }
  
  /**
   * Get process Error output as Stream
   * @return resource  resource
   */
  public function getErrorOutStream() {
    if( $this->pipedNextProcess ) {
      return $this->pipedNextProcess->getErrorOutStream();
    }
    
    return $this->getOutStream(2);
  }
  /**
   * Get process Output as Stream.
   * @return resource
   */
  public function getOutputStream() {
    if( $this->pipedNextProcess ) {
      return $this->pipedNextProcess->getOutputStream();
    }
    return $this->getOutStream(1);
  }
  
  /**
   * Get process Error output as String for compatibility
   * @return string standard output
   */
  public function getOutput(){
    return stream_get_contents($this->getOutputStream());
  }
  
  /**
   * Get process Error output as String for compatibility
   * @return string error output
   */
  public function getErrorOutput(){
    return stream_get_contents($this->getErrorOutStream());
  }
  
  /**
   * Get process Command Line as String for compatibility
   * @return string command line
   */
  public function getCommandLine(){
    $cmd = $this->cmd;
    //
    preg_match('|^([\d.]+)|',phpversion(),$m);
    if( is_array($cmd) && sizeof($m)>0 && floatval($m[0]) && floatval($m[0]) < 7.4 ){
      $cmd = array_map( function($e){
        return preg_match('/\s|\'|"/', $e)?escapeshellarg($e):$e;
      }, $cmd);
      $cmd = join(' ',$cmd);
      return $cmd;
    }
    return $cmd;
  }
  
  /**
   * Process status.
   * for compatibility.
   * @return int exit code.  -1 .. 255.
   */
  public function getExitCode(){
    return $this->getExitStatusCode();
  }
  
  /**
   * Set process Error output as Stream
   * @param resource|string $errout
   * @return \SystemUtil\Process return $this.
   */
  public function setErrout( $errout ):Process {
    if( is_resource($errout) ) {
      $this->errout = $errout;
    } else {
      if( is_string($errout) ) {
        $this->errout = ['file', $errout, 'w+'];
      } else {
        // do nothing.
      }
    }
    
    return $this;
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
    
    $this->triggerEvent('OnStart');
    // wating
    while($this->isRunning()) {
      $this->triggerEvent('OnWait');
      usleep($this->wait_time);
      $this->checkTimeout();
    }
    $this->triggerEvent('OnFinish');
    if( ! $this->isSuccessful() || $this->canceled() ) {
      $this->triggerEvent('OnError');
      proc_close($this->current_process->proc);
      $this->triggerEvent('OnProcClosed');
      
      return;
    }
    //
    $this->triggerEvent('OnSuccess');
    proc_close($this->current_process->proc);
    $this->triggerEvent('OnProcClosed');
    
    return;
  }
  
  /**
   * @param string $event
   */
  protected function triggerEvent( string $event ) {
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
      // Avoid to read blocking pipes[0] must be closed. this function is ensure.
      $callback__func_on_start = $this->getInputFdCloseCallback();
      $callback__func_on_start($this->current_process->pipes);
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
    
    $this->checkProcessPipesHasUpdated();
    $callback_on_every_waiting = $this->getOnWaiting();
    $callback_on_every_waiting(
      $this->current_process->stat,
      $this->current_process->pipes,
      $this->current_process->proc);
  }
  
  protected function checkProcessPipesHasUpdated() {
    
    $this->getPipe(1) && $this->handleOnChange(1);
    $this->getPipe(2) && $this->handleOnChange(2);
  }
  
  protected function handleOnChange( int $i ) {
    
    if( $this->enable_buffering_on_wait || isset($this->on_changed[1]) ) {
      // read from pipe.
      stream_set_blocking($this->getPipe($i), false);
      $updated = "";
      do {
        $str = fread($this->getPipe($i), 1024);
        $updated = $updated.$str;
      } while($str);
      stream_set_blocking($this->getPipe($i), true);
      //
      if( $updated && $this->enable_buffering_on_wait ) {
        $buff = $this->getBufferedPipe($i);
        if( $buff == null ) {
          $buff = $this->current_process->buffered_pipes[$i] = $this->getTempFd($this->use_memory);
        }
        fwrite($buff, $updated);
      }
      if( $updated && isset($this->on_changed[$i]) ) {
        $on_change = $this->on_changed[$i];
        $on_change($updated);
      }
    }
  }
  
  /**
   * Get callback function on process waiting.
   * @return \Closure --- -- function ( $status, $pipes, $process_resouce ){..}
   */
  public function getOnWaiting() {
    $default = function ( $status, $pipes, $prcess_res ) { };
    
    return $this->on_executing ?? $default;
  }
  
  /**
   *
   */
  protected function handleOnError() {
    $this->is_successful = false;
    $callback_func_on_error = $this->getOnError();
    $callback_func_on_error($this->current_process->stat, $this->current_process->buffered_pipes);
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
   * @param \Closure $on_error --  function ( $status, $pipes ){..}
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
    $callback_func_on_success = $this->getOnSuccess();
    $callback_func_on_success($this->current_process->stat, $this->current_process->buffered_pipes);
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
   * @return \SystemUtil\Process return this.
   */
  public function setOnSuccess( $on_success ):Process {
    $this->on_success = $on_success;
    
    return $this;
  }
  
  /**
   *
   */
  protected function handleOnProcClosed() {
    $callback_func_on_proc_closed = $this->getOnProcClosed();
    $callback_func_on_proc_closed($this->current_process->stat, $this->current_process->buffered_pipes);
  }
  
  /**
   * get callback function on after proc_close called.
   * @return \Closure
   */
  public function getOnProcClosed():Closure {
    $default = function ( $out, $err ) { };
    
    return $this->on_proc_closed ?? $default;
  }
  
  /**
   *
   */
  protected function handleOnFinish() {
    
    // ensure  onOutputChangedCallback can read all
    $this->checkProcessPipesHasUpdated();
    //
    $this->getOutputStream();// fetch unread chars.
    $this->getErrorOutStream();// fetch unread chars.
  }
  
  /**
   * Check execution time. and kill long execution.
   */
  protected function checkTimeout():void {
    if( ! $this->getTimeout() ) {
      return;
    }
    $proc_struct = $this->current_process;
    if( $this->isNotRunning() ) {
      return;
    }
    if( microtime(true) > $this->getTimeout() + $proc_struct->start_time ) {
      $this->signal(15);//SIGTERM
      
      return;
    }
  }
  
  /**
   * get current set timeout.
   * @return double timeout.
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
    //
    $proc_struct = $this->current_process;
    if( $this->isNotRunning() ) {
      return;
    }
    proc_terminate($proc_struct->proc, $signal);
    usleep(100); // for linux. pass threading context.wait for signal sent.
    // for sure.
    // retry if proc is stile alive.
    foreach (range(0, 10) as $idx) {
      if( $this->isNotRunning() ) {
        break;
      }
      proc_terminate($proc_struct->proc, $signal);
      usleep(100);
    }
    
    return;
  }
  
  /**
   * Check Process finished Successfully.
   * @return bool
   */
  public function isSuccessful() {
    return ( $this->isFinished() || $this->isProcessClosed() ) && $this->getExitStatusCode() === 0;
  }
  
  /**
   * Process status.
   * @return int exit code.  -1 .. 255.
   */
  protected function getExitStatusCode():int {
    return $this->current_process->stat['exitcode'];
  }
  
  /**
   * @param $input
   * @return bool
   */
  private function isFileNameString( $input ):bool {
    return ! preg_match('#[\n\*\<\>\|:\t\?]#', $input) // Not Contain chars prohibited in filename
           && preg_match('#(?<!\\\)(/|\\\)#', $input); // contain directory separator.
  }
  
  public function setOnOutputChanged( $function_on_change ):Process {
    $this->setOnOChanged(1, $function_on_change);
    
    return $this;
  }
  
  protected function setOnOChanged( $i, $function_on_change ) {
    $this->on_changed[$i] = $function_on_change;
  }
  
  public function setOnErrputChanged( $function_on_change ):Process {
    $this->setOnOChanged(2, $function_on_change);
    
    return $this;
  }
  
  /**
   * pipe command process
   * @throws \Exception
   */
  public function pipe( $cmd ):Process {
    $proc2 = new Process($cmd, $this->getEnv(), $this->getCwd());
    $this->pipeProcess($proc2);
    
    return $proc2;
  }
  
  /**
   * pipe command process
   * @throws \Exception
   */
  public function pipeProcess( Process $proc2 ):Process {
    [$out, $err] = $this->start();
    $proc2->setInput($out);
    $proc2->start();
    $this->pipedNextProcess = $proc2;
    
    return $proc2;
  }
  
  /**
   * Start Process. This is none blocking.
   * array [ 0 -> stdout 1-> stderr ], raw output, not buffered.
   * @return resource[] array of resouorce [ 0 -> stdout,  1-> stderr ]
   * @throws \Exception
   */
  public function start():array {
    if( ! $this->isRunning() ) {
      $this->start_process();
    }
    
    return [
      $this->getOutputStream() ?: $this->getPipe(1),
      $this->getErrorOutStream() ?: $this->getPipe(2),
    ];
  }
  /**
   * pseudo-thread style coding are supported by this function.
   * @return bool|resource|null
   */
  public function join(){
    return $this->wait();
  }
  /**
   * pseudo-thread style coding are supported by this function.
   * @return bool|resource|null
   */
  public function stop(){
    return $this->signal(15);
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
    
    return $this->getOutputStream();
  }
  
  /**
   * Set Callback function called when waiting process.
   * Default onWait buffering  will be override.
   * Don't use blocking IO wait EOF functions  (ie. stream_get_contents) in this callback.
   * Using whole read Blocking IO function like stream_get_contents cannot get realtime output.
   * @param \Closure $on_executing -- function ( $status, $pipes, $prcoess ){..}
   * @return \SystemUtil\Process return this.
   */
  public function setOnWaiting( $on_executing ):Process {
    $this->disableBufferingOnWait();
    $this->on_executing = $on_executing;
    
    return $this;
  }
  
  /**
   * disable buffering proc_open pipes=[ 1 =>['pipe','w'], 2 =>['pipe','w']] .
   */
  public function disableBufferingOnWait():void {
    $this->enable_buffering_on_wait = false;
  }
  
  /**
   * Unset waiting function
   */
  public function removeOnWaiting() {
    $this->on_executing = null;
  }
  
  public function getWaitTime() {
    return $this->wait_time;
  }
  
  public function setWaitTime( int $microseconds ) {
    $this->wait_time = $microseconds;
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
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isError() {
    return ( $this->isFinished() || $this->isProcessClosed() ) && $this->getExitStatusCode() > 0;
  }
}