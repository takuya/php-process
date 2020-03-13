<?php

use SystemUtil\Process;

require_once '../src/Process.php';



function method_doc( ReflectionMethod $method ):array {
  
  $params = $method->getParameters();
  $return = $method->hasReturnType() ? @(string)$method->getReturnType() : null;
  $doc = $method->getDocComment();
  $doc = parse_doc($doc);
  
  return ['name' => $method->getShortName(), 'params' => $params, 'return' => $return, 'docs' => $doc];
}

function parse_doc( $doc_string ) {
  // var_dump($doc_string);
  preg_match_all(
    '/^\s*\*\s*(?<anon>@\s*[a-z]+)\s+(?<type>[^\s]*)\s*(?<name>\$[_\d\w]+)\s*(?<comm>[^\s\*]*)$/m',
    $doc_string,
    $maches);
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
  preg_match_all(
    '/^\s*\*\s+([^@][^\n]+)$/m',
    $doc_string,
    $maches);
  $description = join("\n", array_map(function( $e ){ return $e.'    '; }, $maches[1]));
  
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
  $data['return'] = ( $args['return'] == null ) ? 'void' : $args['return'];
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
      $args['docs']['anons']),
    function ( $e ) { return $e; });
  
  return $data;
}

function format_markdown( $data ) {
  
  $data = var_export($data, true);
  $src = "
<?php
extract($data);?>".'
## <?php  echo $name;?>

<?php  echo $comment;?>


### Descriptoin
```php
<?php echo $name;?> (<?php echo $func_params;?>) :<?php echo $return;?>

```

### Pramters
<?php  foreach( $parameters as $param ) :?>
- ***<?php echo $param["var_name"];?>***:<?php echo $param["var_type"];?> <?php echo $param["var_summery"];?>

<?php endforeach;?>

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
    $ret = method_doc($method);
    $ret = prepare_data_for_template($ret);
    $ret = format_markdown($ret);
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


