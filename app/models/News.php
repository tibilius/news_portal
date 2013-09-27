<?php

namespace app\models;

class News extends \lithium\data\Model {

  public static function config(array $config = array ()) {
    parent::config($config);
    if (static::meta('connection') === 'file') {
      static::meta('source', 'news');
      static::meta('key', 'title');
    }
  }
 

}