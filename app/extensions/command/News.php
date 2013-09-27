<?php

namespace app\extensions\command;

use app\models\News as Newss;

class News extends \lithium\console\Command {

  public $title;
  public $body;

  public function run() {
    $news = Newss::find('all');
    foreach ($news as $post) {
      $this->out("{$post->title}  {$post->ctime}");
    }
  }

  public function add() {
    $data = array ('title' => $this->title, 'body' => $this->body);
    if (Newss::create($data)->save()) {
      $this->out('News saved');
    }
    else {
      $this->out('News not saved');
    }
  }
  
  public function delete(){
    if (Newss::remove(array('title'=>  $this->title))) {
      $this->out('News removed');
    }
    else {
      $this->out('News not removed');
    }
  }
  
  public function edit(){
    $data = array ('title' => $this->title, 'body' => $this->body);
    if (Newss::update($data, array('title'=>  $this->title))) {
      $this->out('News removed');
    }
    else {
      $this->out('News not removed');
    }
  }

}