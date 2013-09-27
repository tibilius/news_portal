<?php

namespace app\tests\cases\extensions\data\source;

use lithium\data\Connections;
use lithium\data\entity\Document;
use lithium\data\collection\DocumentSet;

class FileTest extends \lithium\test\Unit {

  public $source;
  public $query;
  public $model = 'app\tests\mocks\models\MockFile';

  public function setUp() {
    Connections::add('test_file', array ('type' => 'File',));
    $model = $this->model;
    $model::meta('source', 'test');
    $model::meta('connection', 'test_file');
    $model::meta('key', 'filename');
    $this->source = Connections::get('test_file');
  }

  public function tearDown() {
    $this->_clear();
    $model = $this->model;
    $config = Connections::get('test_file')->config();
    $dir = implode(
      DIRECTORY_SEPARATOR, 
      array (
        $config['startPoint'], 
        $model::meta('source')
      )
    );      
    rmdir($dir);
    $this->source->disconnect();
  }

  protected function _clear() {
    $model = $this->model;
    while ($file = $model::find('first')) {
      $file->delete();
    }
  }

  public function testCreate() {
    $model = $this->model;
    $result = $model::create(array ('title' => 'Test News',))
      ->save();
    $this->assertTrue($result);
  }

  public function testRead() {
    $model = $this->model;
    $created = $model::create(array ('title' => 'Test News', 'body' => time()))
      ->save();
    $this->assertTrue($created);
    $result = $model::find(
      'all', 
      array (
        'conditions' => array (
          'title' => 'Test News', 
          'mtime' => array ('>', time() - 86400)
        ),
        'fields' => array ('title', 'ctime', 'mtime', 'filename'),
        'limit' => 10
      )
    );
    $this->assertTrue($result instanceof DocumentSet);
    $this->assertEqual('Test News', $result->first()->title);
    $this->assertEqual(1, $result->count());
  }

  public function testDelete() {
    $model = $this->model;
    $data = array ('title' => 'Delete Me', 'body' => time());
    $created = $model::create($data)->save();
    $conditions = array ('title' => $data['title']);
    $result = $model::find('all', compact('conditions'));
    $expected = 1;
    $this->assertEqual($expected, $result->count());
   
    $record = $result->first()->data();
    $filename = $record['filename'];
    $result = $model::remove(compact('filename'));
    $this->assertTrue($result);
    unset($result);
    
    $result = $model::find('all', compact('conditions'));
    $expected = 0;
    $this->assertEqual($expected, $result->count());

    $created = $model::create($data)->save();
    $this->assertTrue($created);
    $deleted = $model::remove($conditions);
    $this->assertTrue($deleted);
  }

  public function testUpdate() {
    $model = $this->model;
    $time = time();
    $conditions = array ('title' => 'Test News');
    $created = $model::create(array ('title' => 'Test News', 'body' => time()))
      ->save();
    $this->assertTrue($created);
    $result = $model::find('all', compact('conditions'));
    $this->assertTrue($result instanceof DocumentSet);
    $this->assertEqual('Test News', $result->first()->title);
    $this->assertEqual(1, $result->count());
    $result->first()->body = '55555';
    $conditions['title'] = 'New Test News';
    $result->first()->title = 'New Test News';
    $result->first()->perms = '0777';
    $result->first()->ctime = $time - 60;
    $result->first()->mtime = $time;
    $success = $result->first()->save();
    $this->assertTrue($success);
    unset($result);
    $result = $model::find('all', compact('conditions'));
    $this->assertTrue($result instanceof DocumentSet);
    $this->assertEqual('55555', $result->first()->body);
    $this->assertEqual($time, $result->first()->mtime);
    $this->assertEqual('0777', $result->first()->perms);
    unset($result);
    $update = $model::update(array ('body' => '7777', 'title' => '1'), $conditions);
    $this->assertTrue($update);
    $conditions['title'] = '1';
    $result = $model::find('first', compact('conditions'));
    $this->assertTrue($result instanceof Document);
    $this->assertEqual('7777', $result->body);
  }

}