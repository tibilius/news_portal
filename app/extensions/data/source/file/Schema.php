<?php

namespace app\extensions\data\source\file;

class Schema extends \lithium\data\DocumentSchema {
 	protected $_handlers = array();

  public function __construct(array $config = array()) {
		$defaults = array('fields' => array(
      'filename' => array('type' => 'string'),
      'title' => array ('type' => 'string'),
      'body' => array ('type' => 'string'),
      'extension' => array ('type' => 'string'),
      'ctime' => array ('type' => 'integer'),
      'mtime' => array ('type' => 'integer'),
      'perms' => array ('type' => 'integer'),
      'uid' => array ('type' => 'integer'),
      'gid' => array ('type' => 'integer'),
      
      )
    );
		parent::__construct(array_filter($config) + $defaults);
	}  
 
}