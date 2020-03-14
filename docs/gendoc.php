<?php

use SystemUtil\Process;

require_once '../src/Process.php';

// set_error_handler(function($e){ throw new Exception(''); }, E_ALL);


function method_doc( ReflectionMethod $method ):array {
  
  $params = $method->getParameters();
  $return = $method->hasReturnType() ? @(string)$method->getReturnType() : null;
  $doc = $method->getDocComment();
  $doc = parse_doc($doc);
  
  return ['name' => $method->getShortName(), 'params' => $params, 'return' => $return, 'docs' => $doc];
}

function parse_doc_string_param($doc_string){
  // var_dump($doc_string);
  preg_match_all(
    '/^\s*\*\s*(?<anon>@\s*[a-z]+)\s+(?<type>[^\s]*)\s*(?<name>\$[_\d\w]+)\s*(?<comm>[^\s\*]*)$/m',
    $doc_string,
    $maches);
  // var_dump($maches);
  $annotations = [];
  // var_dump($maches);exit;;
  foreach ($maches['anon'] as $idx => $e) {
    
    $annotations[] = [
      $maches['anon'][$idx],
      $maches['type'][$idx],
      $maches['name'][$idx],
      trim($maches['comm'][$idx]),
    ];
  }
  return $annotations;
}
function parse_doc_string_return($doc_string){
  // var_dump($doc_string);
  preg_match_all(
    '/^\s*\*\s*(?<anon>@return)\s*(?<type>[^\s]+)\s*(?<comm>[^\s]*[^\n]*)\n/m',
    $doc_string,
    $maches);
  $annotation_return = [];
  if (sizeof($maches['anon'])){
    $annotation_return = [
      $maches['anon'][0],
      $maches['type'][0],
      trim($maches['comm'][0]),
    ];
    // var_dump($annotation_return);
    // exit();;;
  }
  // var_dump($annotation_return);exit();;;
  return $annotation_return;
  
}

function parse_doc( $doc_string ) {
  // var_dump($doc_string);
  preg_match_all(
    '/^\s*\*\s+([^@][^\n]+)$/m',
    $doc_string,
    $maches);
  $description = join("\n", array_map(function( $e ){ return $e.'    '; }, $maches[1]));
  
  $annotations = [];
  $annotations['return'] = parse_doc_string_return($doc_string);
  $annotations['params'] = parse_doc_string_param($doc_string);
  
  // var_dump($annotations);exit();;;
  
  return ['descreption' => $description, 'anons' => $annotations];
}

function prepare_data_for_template( $args ) {
  $data = [];
  $data['name'] = $args['name'];
  $data['func_params'] = join(
    ", ",
    array_map(
      function ( ReflectionParameter $e ) {
        
        $name = $e->getName();
        //
        $default = '';
        try {
          $default = $e->allowsNull() ? '= null' : $default;
          $default = $e->getDefaultValue() === null ? $default : '= '.json_encode($e->getDefaultValue());
        } catch (\ReflectionException $ex) {
          // no defaut value for this param
        }
        $type = $e->hasType() ? @$e->getType()->__toString() : '';
        //
        $str = " $type \$$name $default ";
        
        return trim($str);
      },
      $args['params']));
  $data['return'] = ( $args['return'] == null ) ? null : $args['return'];
  $data['return'] = (sizeof($args['docs']['anons']['return'])) ? $args['docs']['anons']['return'][1]: "";
  // var_dump($args['docs']['anons']);exit;;
  $data['return_comment'] = (sizeof($args['docs']['anons']['return'])) ? $args['docs']['anons']['return'][2]: "";
  $data['comment'] = $args['docs']['descreption'];
  $data['parameters'] = array_filter(
    array_map(
      function ( $e ) {
        if( ! preg_match('/@param/', trim($e[0])) ) {
          return null;
        }
        $anon_type = $e[0];
        $var_type = $e[1] == 'null' ? '' : $e[1];
        $var_name = $e[2];
        $var_summery = $e[3];
        $var_type = str_replace('|', ' | ', $var_type);
        
        return compact('anon_type', 'var_type', 'var_name', 'var_summery');
      },
      $args['docs']['anons']['params']),
    function ( $e ) { return $e; });
  
  return $data;
}

function format_markdown( $data ) {
  
  $data = var_export($data, true);
  $src = "
<?php
extract($data);?>".'
# <?php  echo $name;?>

<?php  echo $comment;?>

```php
<?php echo $name;?> (<?php echo $func_params;?>) :<?php echo $return ? $return : "void";?>

```
<?php if(sizeof($parameters)):?>
### Pramters

<?php  foreach( $parameters as $param ) :?>
- ***<?php echo $param["var_name"];?>***:<?php echo $param["var_type"];?> <?php echo $param["var_summery"];?>

<?php endforeach;?>
<?php endif;?>

<?php if($return):?>
### Return

<?php echo $return;?> <?php echo $return_comment ? ": ".$return_comment:""; ?>
<?php endif;?>


';
  $proc = new  Process('php');
  $proc->setInput($src);
  $proc->run();
  $fd_out = $proc->getOutput();
  $out = stream_get_contents($fd_out);
  
  return $out;
}

function top_of_contents( ) {
  $ref = new ReflectionClass(Process::class);
  $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

  $str = [];
  $str[] = '';
  $str[] = '# public methods';
  $str[] = '';
  /** @var \ReflectionMethod $method */
  foreach ($methods as $method) {
    $func_name = $method->getShortName();
    $str[] = "- [{$func_name}](#{$func_name})";
  }
  
  return join("\n", $str)."\n";
}

function content_header() {
  $ref = new ReflectionClass(Process::class);
  $comment = $ref->getDocComment();
  $str = "
  # Process class Documentation
  
  ```
  $comment
  ```
  
";
  
  return $str;
}
function content_methods(){
  $content = "";
  $ref = new ReflectionClass(Process::class);
  $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
  foreach ($methods as $method) {
    
    // if ( $method->getShortName() !=='getCurrentProcess' )continue;
    
    $ret = method_doc($method);
    $ret = prepare_data_for_template($ret);
    $ret = format_markdown($ret);
    // var_dump($ret);exit;;;;
    $content = $content.$ret;
  }
  return $content;
}

function main() {
  
  $fd = fopen(__DIR__."/procss.funcs.md", 'w');
  fwrite($fd, content_header());
  fwrite($fd, top_of_contents());
  fwrite($fd, content_methods());
  fclose($fd);
}


main();
// content_methods();

