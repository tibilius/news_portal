<?php

use lithium\analysis\Logger;
use lithium\core\Environment;
use \Exception;

Logger::config(array ('default' => array ('adapter' => 'File')));

// Exception-to-string transformation function
$output = function ($exception, $options) use (&$output) {
    $options += array ('trace' => true, 'previous' => true);

    $class = get_class($exception);
    $message = $exception->getMessage() ? : '(no message)';
    $code = $exception->getCode() ? : 'no code';
    $file = $exception->getFile() ? : 'inline code';
    $line = $exception->getLine() ? : '???';

    $return = "[{$code}] {$message}\n\n{$class} in {$file}:{$line}";

    if ( $options['trace'] ) {
      $trace = $exception->getTraceAsString();
      $return .= "\n\nStack trace:\n{$trace}";
    }

    if ( $options['previous'] && $prev = $exception->getPrevious() ) {
      $prevMsg = $output($prev, array ('trace' => false));
      $return .= "\n\nPrevious exception: {$prevMsg}";
    }

    return trim($return) . "\n\n------------------------------------------------";
  };

// Apply `write` filter to process exceptions into messages
Logger::applyFilter('write', function ($self, $params, $chain) use (&$output) {
  if ( $params['message'] instanceof \Exception ) {
    $params['message'] = $output($params['message'], $params['options']);
  }
  return $chain->next($self, $params, $chain);
});