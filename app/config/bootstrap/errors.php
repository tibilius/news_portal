<?php

/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
use lithium\action\Response;
use lithium\analysis\Logger;
use lithium\core\ErrorHandler;

ini_set('display_errors', 1);

ErrorHandler::apply(
  'lithium\action\Dispatcher::run', 
  array ('type' => 'app\util\HttpErrorException'), 
  function ($info) {    
    if ($info['exception']->getCode() == 404) {
      $config = array (        
        'router' => 'lithium\net\http\Router',
        'location' => array ('controller' => 'pages', 'action' => 'not_found'),       
      );
      return new Response($config);
    }
    return $info['exception']->getMessage().$info['exception']->getCode();    
  });
ErrorHandler::apply(
  'lithium\action\Dispatcher::run', 
  array ('type' => 'Exception'), 
  function ($info) {    
    Logger::critical($info['exception']->getTraceAsString());
    $config = array (        
      'router' => 'lithium\net\http\Router',
      'location' => array ('controller' => 'pages', 'action' => 'error'),       
    );
    return new Response($config);
  });  
/**
 * Start errors handling
 */
ErrorHandler::run();
