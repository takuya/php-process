<?php

namespace SystemUtil;

use Closure;
use phpDocumentor\Reflection\Types\This;

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
   * @var int microsecond.
   */
  protected $wait_time = 1000;
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
   * @var array
   */
  
  private $pipe_changed = [];
  
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
   * @return object object of anonymous class.
   */
  public function getCurrentProcess() {
    return $this->current_process;
  }
  
  /**
   * Process status.
   * @return int exit code.  -1 .. 255.
   */
  public function getExitStatusCode():int{
    return $this->current_process->stat['exitcode'];
  }
  
  /**
   * process is cancled by signal.
   * @return bool is process signaled.
   */
  public function canceled():bool{
    return $this->isNotRunning() && $this->current_process->stat['signaled'];
  }
  protected function getPipe(int $i){
    return $this->current_process->pipes[$i];
  }
  protected function getBufferedPipe(int $i){
    if ( empty($this->current_process->buffered_pipes[$i])){
      return null;
    }
    return $this->current_process->buffered_pipes[$i];
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
   * @return object
   * @throws \Exception
   */
  protected function start_process() {
    $descriptor = [
      0 => $this->getInput()  ?: ['pipe', 'r'],
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
   * @param $input
   * @return bool
   */
  private function isFileNameString( $input ):bool {
    return ! preg_match('#[\n\*\<\>\|:\t\?]#', $input) // Not Contain chars prohibited in filename
           && preg_match('#(?<!\\\)(/|\\\)#', $input); // contain directory separator.
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
  
  /**
   * @param bool $use_memory
   * @return bool|resource
   */
  private function getTempFd( $use_memory = true ) {
    return fopen($use_memory? 'php://memory':'php://temp', 'w+');
  }
  
  /**
   * Get process Error output as Stream
   * @return resource  resource
   */
  public function getErrout() {
    if ( $this->isNotStarted()){
      return $this->errout;
    }
  
    if ( $this->errout === null  && $this->isSuccessful() ){
      $raw = $this->getPipe(2);
      $buff = $this->getTempFd($this->use_memory);
      stream_copy_to_stream($raw, $buff);
      rewind($buff);
      $this->errout = $buff;
      
      return $buff;
    }
    if ( $this->errout !== null  && $this->isSuccessful() ){
      $meta = stream_get_meta_data($this->errout);
      if( $meta['seekable'] == true ) {
        fseek($this->errout, 0);
      }
    }
    
    return $this->errout;
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
    
    $this->handleEvent('OnStart');
    // wating
    while($this->isRunning()) {
      $this->handleEvent('OnWait');
      usleep($this->wait_time);
      $this->checkTimeout();
    }
    $this->handleEvent('OnFinish');
    
    if( !$this->isSuccessful() || $this->canceled() ) {
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
  protected function handleOnStart() {
    if( $this->getInput() == null ) {
      // Avoid to read blocking pipes[0] must be closed. this function is ensure.
      $callback__func_on_start = $this->getInputFdCloseCallback();
      $callback__func_on_start($this->current_process->pipes);
    }
    
    $this->registerPipeChangedChecker();
  }
  protected function registerPipeChangedChecker() {
    $checker_generator = function () {
      $last_mtime = null; // bind mtime  in closure.
      $check_output = function ($fd) use ( &$last_mtime ) {
        if( $last_mtime == fstat($fd)['mtime'] ) {
          return false;
        }
        $last_mtime = fstat($fd)['mtime'];
        return true;
      };
      return $check_output;
    };
    // register callback and checker
    foreach ( $this->pipe_changed as $idx => $item) {
      $this->current_process->buffered_pipes[$idx] =  $this->pipe_changed[$idx]['buffered'] ?  $this->getTempFd($this->use_memory):null;
      $this->pipe_changed[$idx]['checker'] = $checker_generator();
      stream_set_blocking($this->getPipe($idx), $this->pipe_changed[$idx]['blocking']);
    }
  }
  protected function checkPipeUpdated(int $i){
    $checker = $this->pipe_changed[$i]['checker'];
    return $checker($this->getPipe($i));
    
  }
  protected function checkOutputHasChanged(){
    return $this->checkPipeUpdated(1);
  }
  // protected function checkErrOutHasChanged(){
  //   return ($this->function_check_pipe2_function)();
  // }
  public function setOnOutputChanged( $function_on_change, $opt = ['blocking'=>true, 'buffered'=>true] ){
    if ( empty($this->pipe_changed[1]) ){
      $this->pipe_changed[1] = [
        'callback'=> null,
        'checker' => null,
        'blocking' => true,
        'buffered' => true,
      ];
      $this->pipe_changed[1]['callback'] = null;
    }
    $this->pipe_changed[1]['callback'] = $function_on_change;
    $this->pipe_changed[1]['blocking'] = $opt['blocking']??true;
    $this->pipe_changed[1]['buffered'] = $opt['buffered']??true;
  }
  
  protected function getOnOutputChanged():?Closure{
    if ( empty($this->pipe_changed[1])  ){
      return null;
    }
    return $this->pipe_changed[1]['callback'];
  }
  // public function setOnErroutChanged( $function_on_change ){
  //   $this->function_on_pipe2_changed = $function_on_change;
  // }
  // protected function getOnErroutChanged(){
  //   return $this->function_on_pipe2_changed;
  // }
  protected function checkProcessPipesHasUpdated(){
    if ( $this->getOnOutputChanged() && $this->checkOutputHasChanged() ){
      $this->handleOnOutputChanged();
    }
    // if ( $this->getOnErroutChanged() && $this->checkErrOutHasChanged() ){
    //   $str = fread($this->current_process->pipes[2], 1024);
    //   ($this->getOnErroutChanged())($str);
    // }
  }
  protected function handleOnOutputChanged(){
    $str = fread($this->current_process->pipes[1], 1024);
    fwrite($this->getBufferedPipe(1), $str);
    $callback = $this->getOnOutputChanged();
    $callback($str);
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
    $callback_func_on_proc_closed($this->current_process->descriptor[1], $this->current_process->descriptor[2]);
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
   * Get process Output as Stream.
   * @return resource
   */
  public function getOutput() {
    if ( $this->isNotStarted()){
      return $this->output;
    }
    if ( $this->output === null && $this->getBufferedPipe(1)  && $this->isSuccessful() ){
      $this->output = $this->getBufferedPipe(1);
      rewind($this->output);
      return $this->output;
      
    }
    
    if ( $this->output === null  && $this->isSuccessful() ){
      $raw = $this->getPipe(1);
      $buff = $this->getTempFd($this->use_memory);
      stream_copy_to_stream($raw, $buff);
      rewind($buff);
      $this->output = $buff;
      
      return $buff;
    }
    
    if( $this->output && $this->isSuccessful()  && stream_get_meta_data($this->output)['seekable']) {
      fseek($this->output, 0);
    }
    
    return $this->output;
  }
  
  /**
   *
   */
  protected function handleOnFinish() {
    // override pipe(out/err) to 'php://tempfile'.
    if (  !empty($this->pipe_changed[1]) ){
      if ( $this->pipe_changed[1]['buffered'] ) {
        while( !feof( $this->getPipe(1)) ){ fwrite($this->getBufferedPipe(1), fread($this->getPipe(1), 1024)); }
      }else{
        // ensure read all
        $this->handleOnOutputChanged();
      }
    }
  
    $this->current_process->buffered_pipes = $this->mapPipeToTemp($this->current_process->pipes);
  }
  
  /**
   * @param $pipes
   * @return array array of string [in,out,err]
   */
  protected function mapPipeToTemp( $pipes ):array {
  
    $copy_stream = function($fd_from, $fd_to ){

      if ( !$this->canceled() ){
        stream_copy_to_stream($fd_from, $fd_to );
      }else
        {
        // If forked child has forked child, Canceled has no no eof available in linux.
        // Read remained chars from pipe.
        $bytes_unread = stream_get_meta_data($fd_from)['unread_bytes'];
        $bytes_unread > 0 ? fwrite($fd_to, fread($fd_from, $bytes_unread)) : null;
      }
      rewind($fd_to);
    };
  
    $this->getOutput() ?:  $copy_stream( $pipes[1], $this->output = $this->getTempFd($this->use_memory) );
    $this->getErrout() ?:  $copy_stream( $pipes[2], $this->errout = $this->getTempFd($this->use_memory) );
    
    return [$pipes[0] ?? null, $this->output ?? null, $this->errout ?? null];
  }
  
  /**
   * check process is running.
   * @return bool is running status
   */
  public function isRunning() {
    $proc_struct = $this->current_process;
    if ( $this->isNotStarted() ){
      return false;
    }
    // update status when running.
    if( $this->isStarted() && $this->isNotFinished() && $this->isNotProcessClosed() ) {
      // Call proc_get_status() on to finished $proc that lost real [exit_code] and get only '-1'.
      // Stay conscious of number and times proc_get_status() called.
      // Be Carefully to call proc_get_status() to avoid lost [exit_code].
      $proc_struct->stat = proc_get_status($proc_struct->proc);
    }
    // return last running status, proc_get_status() called.
    return $this->current_process->stat['running'];
  }
  
  /**
   * Check Process finished Successfully.
   * @return bool
   */
  public function isSuccessful(){
    return ($this->isFinished() || $this->isProcessClosed() ) &&  $this->getExitStatusCode() === 0 ;
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isError(){
    return ($this->isFinished() || $this->isProcessClosed() ) &&  $this->getExitStatusCode() > 0 ;
  }
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isNotRunning(){
    return !$this->isRunning();
  }
  
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isStarted(){
    return is_resource($this->current_process->proc) == true
          &&  $this->current_process->start_time !== null;
  }
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isNotStarted(){
    return !$this->isStarted();
  }
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isFinished(){
    return $this->isStarted()
     && $this->current_process->stat['running'] == false;
  }
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isNotFinished(){
    return  !$this->isFinished();
  }
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isProcessClosed(){
    return is_resource($this->current_process->proc) == false
           && get_resource_type($this->current_process->proc) == 'Unknown';
  }
  /**
   * This method made for readable code.
   * return the process is exit_code > 0;
   * process not started.(exit_code= -1 or null then return false ;
   * @return  bool
   */
  protected function isNotProcessClosed(){
    return ! $this->isProcessClosed();
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
    if(  $this->isNotRunning() ) {
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
    list($out, $err) = $this->start();
    $proc2->setInput($out);
    $proc2->start();
    
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
      $this->getOutput() ?: $this->getPipe(1),
      $this->getErrout() ?: $this->getPipe(2),
    ];
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
    
    return $this->getOutput();
  }
  
  /**
   * Set Callback function called when waiting process.
   * Don't use blocking IO functions(ie. stream_get_contents) in this callback.
   * stream_get_contents is blocking IO. stream_get_contents cannot be used as realtime output.
   * @param \Closure $on_executing -- function ( $status, $pipes, $prcoess ){..}
   * @return \SystemUtil\Process return this.
   */
  public function setOnWaiting( $on_executing ):Process {
    $this->on_executing = $on_executing;
    return $this;
  }
  // TODO::
  // /**
  //  * Unset Listener
  //  */
  // public function removeOnWaiting() {
  //   $this->on_executing = null;
  // }
  
  public function setWaitTime(int $microseconds){
    $this->wait_time = $microseconds;
  }
  public function getWaitTime(){
    return $this->wait_time;
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