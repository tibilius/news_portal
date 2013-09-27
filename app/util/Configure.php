<?php

namespace app\util;

use lithium\core\Libraries;
use lithium\data\Connections;

class Configure extends \lithium\core\StaticObject {

  static protected $_configurations;
  static protected $_configPath;

  public static function __init() {
    static::$_configurations['connections'] = array ();
    static::$_configurations['models'] = array ();
    static::$_configPath = LITHIUM_APP_PATH . '/config/configurations';
    static::reset();
  }

  public function configPath($path = null) {
    if (!$path) {
      return static::$_configPath;
    }
    return static::$_configPath = $path;
  }
  
  public static function reset() {
    if (file_exists(static::$_configPath . '/connections.json')) {
      $json = file_get_contents(static::$_configPath . '/connections.json');
      static::$_configurations['connections'] = json_decode($json, true);
    }
    if (file_exists(static::$_configPath . '/models.json')) {
      $json = file_get_contents(static::$_configPath . '/models.json');
      static::$_configurations['models'] = json_decode($json, true);
    }
  }

  public static function run() {
    foreach (static::$_configurations['connections'] as $name => $config) {
      Connections::add($name, $config);
    }
    foreach (static::$_configurations['models'] as $model => $config) {
      if ($model = Libraries::locate('models', $model)) {
        $model::config($config);
      }
    }
  }

}