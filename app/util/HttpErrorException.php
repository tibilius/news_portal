<?php

namespace app\util;

use lithium\core\Environment;

/**
 * A `HttpErrorException` is thrown if request cannot be processed and response
 * should be of specific HTTP protocol error status. This exception supports
 * per-environment error messages and response options.
 */
class HttpErrorException extends \RuntimeException {

  /**
   * Response options
   *
   * @var array
   */
  protected $options = array ();

  /**
   * Per-environment error text
   *
   * @var array {environment : text, ...}
   */
  protected $messages = array ();

  /**
   * Exception constructor
   *
   * @param  integer      $code     HTTP response status code
   * @param  array|string $messages Error text(s) per environment
   * @param  array        $options  Response options
   * @param  Exception    $previous Previous exception if stacked
   */
  public function __construct($code = null, $messages = array (), array $options = array (), \Exception $previous = null) {
    $this->options = $options + array ('default' => 'production');

    if ( is_array($messages) ) {
      $this->messages = $messages;
    }
    else {
      $this->messages[$this->options['default']] = $messages;
    }

    $message = $this->getMessages(Environment::get());
    if ( !$message ) {
      $message = $this->getMessages($this->options['default']);
    }
    if ( is_array($message) ) {
      $message = '';
    }

    parent::__construct($message, $code ? : 500, $previous);
  }

  /**
   * Response options getter
   *
   * @return array
   */
  public function getOptions($key = null) {
    if ( $key ) {
      return isset($this->options[$key]) ? $this->options[$key] : null;
    }
    return $this->options;
  }

  /**
   * Get all messages or message for particular one
   *
   * @param  string            $env Return message for this environment if passed
   * @return array|string|null      Per-environment messages array or message
   *                                string for concrete environment
   */
  public function getMessages($env = null) {
    if ( $env ) {
      return isset($this->messages[$env]) ? $this->messages[$env] : null;
    }
    return $this->messages;
  }

}
