<?php

namespace app\controllers;

use app\models\News;
use app\util\HttpErrorException;

class NewsController extends \lithium\action\Controller {

  protected $_key = null;
  protected $_dateFormat = '';

  public function _init() {
    parent::_init();
    $this->_key = News::meta('key');
    $this->_dateFormat = "d M Y, H:i";
  }

  public function index() {   
    $news = News::find('all');
    $title = 'News list';
    return compact('news', 'title') + array (
      'key' => $this->_key,
      'dateFormat' => $this->_dateFormat
    );
  }

  public function add() {
    $success = false;
    if ( $this->request->data ) {
      $time = time();
      $additional = array ('ctime' => $time, 'mtime' => $time);
      $new = News::create($this->request->data + $additional);
      $success = $new->save();
    }
    return compact('success');
  }

  public function edit($key = null) {
    $conditions = array ($this->_key => urldecode($key));     
    if ( $this->request->data ) {
      
      $success = false;
      $data = array ('mtime' => time()) + $this->request->data;
      $success = News::update($data, $conditions);
      return $this->redirect('');
    }

    if ( $key === null ) {
      throw new HttpErrorException(
        404,
        'Requested news not found'
      );
    }
    $news = News::find('all', compact('conditions'));
    if ( ! $news || ! $news = $news->first() ) {
      throw new HttpErrorException(
        404,
        'Requested news not found'
      );
    }
    return compact('news') + array (
      'key' => $this->_key,
      'dateFormat' => $this->_dateFormat,
      'success' => false,
    );
  }

  public function delete($key = null) {  
    $conditions = array ("$this->_key" => urldecode($key),);
    $success = News::remove($conditions);      
    return $this->redirect('');
  }

  public function node($key = null) {
    if ( $key === null ) {
      throw new HttpErrorException(
        404,
        'Requested news not found'
      );
    }   
    $news = News::find(
      'all',
      array ('conditions' => array ($this->_key => urldecode($key)))
    );    
    if ( ! $news || ! $news = $news->first() ) {
      throw new HttpErrorException(
        404,
        'Requested news not found'
      );
    }
    return compact('news') + array (
      'key' => $this->_key,
      'dateFormat' => $this->_dateFormat
    );
  }

}

