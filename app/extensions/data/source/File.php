<?php

namespace app\extensions\data\source;

use Exception;
use lithium\analysis\Logger;

class File extends \lithium\data\Source {

  /**
   * Classes used by this class.
   *
   * @var array
   */
  protected $_classes = array (
    'schema' => 'app\extensions\data\source\file\Schema',
    'entity' => 'lithium\data\entity\Document',
    'set' => 'lithium\data\collection\DocumentSet',
    'array' => 'lithium\data\collection\DocumentArray',
    'result' => 'app\extensions\data\source\file\Result',
    'exporter' => 'app\extensions\data\source\file\Exporter',
  );

  /**
   * Stores the status of this object's connection. Updated when `connect()` or `disconnect()` are
   * called, or if an error occurs that closes the object's connection.
   *
   * @var boolean
   */
  protected $_isConnected = true;

  /**
   * List of configuration keys which will be automatically assigned to their corresponding
   * protected class properties.
   *
   * @var array
   */
  protected $_autoConfig = array ('classes' => 'merge');
  protected $_fileDefaults = array (
    'content-type' => 'text\plain',
    'extension' => 'txt',
    'perms' => 0755,
    'uid' => 1000,
    'gid' => 1000,
  );

  public function config (array $config = array()){
    if (!$config){
      return $this->_config;
    }
    return $this->_config = $config;
  }

  public function __construct(array $config = array ()) {
    $defaults = array (
      'startPoint' => LITHIUM_APP_PATH . '/resources/files/',
      'attempts' => 5,
      'sleep' => 100, // delay between request attempts in MICRO seconds
      'increase' => 50, // delay increase in MICRO seconds
    );
    parent::__construct($config + $defaults);
  }

  public function connect() {
    return $this->_isConnected = true;
  }

  public function disconnect() {
    return false;
  }

  public static function enabled($feature = null) {
    return true;
  }

  /**
   * Create a record. This is the abstract method that is implemented by specific data sources.
   * This method should take a query object and use it to create a record in the data source.
   *
   * @param mixed $query An object which defines the update operation(s) that should be performed
   *        against the data store.  This can be a `Query`, a `RecordSet`, a `Record`, or a
   *        subclass of one of the three. Alternatively, `$query` can be an adapter-specific
   *        query string.
   * @param array $options The options from Model include,
   *              - `validate` _boolean_ default: true
   *              - `events` _string_ default: create
   *              - `whitelist` _array_ default: null
   *              - `callbacks` _boolean_ default: true
   *              - `locked` _boolean_ default: true
   * @return boolean Returns true if the operation was a success, otherwise false.
   */
  public function create($query, array $options = array ()) {
    return $this->_tryCatch('_create', array ($query, $options));
  }

  /**
   * Abstract. Must be defined by child classes.
   *
   * @param mixed $query
   * @param array $options
   * @return boolean Returns true if the operation was a success, otherwise false.
   */
  public function read($query, array $options = array ()) {
    return $this->_tryCatch('_read', array ($query, $options));
  }

  /**
   * Updates a set of records in a concrete data store.
   *
   * @param mixed $query An object which defines the update operation(s) that should be performed
   *        against the data store.  This can be a `Query`, a `RecordSet`, a `Record`, or a
   *        subclass of one of the three. Alternatively, `$query` can be an adapter-specific
   *        query string.
   * @param array $options Options to execute, which are defined by the concrete implementation.
   * @return boolean Returns true if the update operation was a success, otherwise false.
   */
  public function update($query, array $options = array ()) {
    return $this->_tryCatch('_update', array ($query, $options));
  }

  /**
   * @param mixed $query
   * @param array $options
   * @return boolean Returns true if the operation was a success, otherwise false.
   */
  public function delete($query, array $options = array ()) {
    return $this->_tryCatch('_delete', array ($query, $options));
  }

  /**
   * Gets the column schema for a given entity (such as a database table).
   *
   * @param mixed $entity Specifies the table name for which the schema should be returned, or
   *        the class name of the model object requesting the schema, in which case the model
   *        class will be queried for the correct table name.
   * @param array $schema
   * @param array $meta The meta-information for the model class, which this method may use in
   *        introspecting the schema.
   * @return array Returns a `Schema` object describing the given model's schema, where the
   *         array keys are the available fields, and the values are arrays describing each
   *         field, containing the following keys:
   *         - `'type'`: The field type name
   */
  public function describe($entity, $schema = array (), array $meta = array ()) {
    return $this->_instance('schema', compact('fields'));
  }

  /**
   * Returns a list of objects (sources) that models can bind to, i.e. a list of tables in the
   * case of a database, or REST collections, in the case of a web service.
   *
   * @param string $class The fully-name-spaced class name of the object making the request.
   * @return array Returns an array of objects to which models can connect.
   */
  public function sources($class = null) {
    return $this->_ls(
        $this->_config['startPoint'], array ('recursive' => false), function ($filepath) {
          if (is_dir($filepath)) {
            return true;
          }
          return false;
        }
    );
  }

  /**
   * Defines or modifies the default settings of a relationship between two models.
   *
   * @param $class the primary model of the relationship
   * @param $type the type of the relationship (hasMany, hasOne, belongsTo)
   * @param $name the name of the relationship
   * @param array $options relationship options
   * @return array Returns an array containing the configuration for a model relationship.
   */
  public function relationship($class, $type, $name, array $options = array ()) {
    if (isset ($this->_classes['relationship'])) {
      return $this->_instance(
          'relationship', compact('type', 'name') + $options
      );
    }
    return null;
  }

  public function statistics($filename) {
    return $this->_tryCatch(
        '_fileStat', array (
        $filename,
        array (
          'stat' => true,
          'full' => true,
          'callable' => false,
        )
        )
    );
  }

  protected function _tryCatch($method, $args) {
    $increase = $this->_config['increase'];
    $attempts = $this->_config['attempts'];
    $sleep = $this->_config['sleep'];
    $attempt = $attempts;
    do {
      --$attempt;
      try {
        return $this->invokeMethod($method, $args);
      }
      catch (Exception $e) {
        if ($attempt <= 0) {
          Logger::write('critical', $this->_exceptionMessage($e));
          if (!$attempts) {
            $attempt = 1;
          }
        }

        if ($attempt) {
          usleep($sleep);
          $sleep += $increase;
        }
      }
    } while ($attempt > 0);

    // Fail after spend reconnects limit
    $msg = "File data source `{$method}` failed after {$attempts} reconnects";
    throw new Exception($msg, 503, isset ($e) ? $e : null);
  }

  /**
   * Listing path
   * @param string $path
   * @param array $options
   * 	allowed keys:
   * 		`'recursive'` make sense only for dirtectories
   * @param callable $callable  runs every time read a file, signature is `($filepath)`,
   *  if $callable return `'false'`, file have not placed in result array
   * @return array
   */
  protected function _ls($path, array $options = array (), $callable = false) {
    $options += array (
      'recursive' => false,
      'getDirs' => true,
      'collect' => false,
      'filters' => array (),
      'context' => stream_context_get_default(),
      'full' => false,
      'stat' => true,
    );
    if ($options['recursive']) {
      $options['full'] = true;
    }
    $path = realpath($path);
    return $this->_tryCatch('_scan', array (compact('path', 'callable') + $options));
  }

  protected function _create($query, array $options = array ()) {
    $options += $this->_fileDefaults;
    $params = compact('query', 'options');
    $_config = $this->_config;
    $_exp = $this->_classes['exporter'];
    return $this->_filter(get_called_class() . '::create', $params, function($self, $params) use ($_config, $_exp) {
      $query = $params['query'];
      $options = $params['options'];
      $args = $query->export($self, array ('source', 'title', 'body', 'perms'));
      $data = $_exp::get('create', $args['data']);
      $dirname = implode(
        DIRECTORY_SEPARATOR, array (
        rtrim($_config['startPoint'], DIRECTORY_SEPARATOR),
        rtrim($args['source'], DIRECTORY_SEPARATOR),
        )
      );
      $filename = $data['create']['title'] . '.' . $options['extension'];
      $data = isset($data['create']['body']) ? $data['create']['body'] : null;

      $success = $self->invokeMethod(
        '_fileSave', 
        array (
          $dirname,
          $filename,
          (string)$data,
          $options['perms']
        )
      );

      if ($success) {
        /// get stat for file and sync with entity;
        $stat = $self->statistics(
          implode(DIRECTORY_SEPARATOR, array ($dirname, $filename))
        );
        foreach ($stat as $key => $value) {
          $params['query']->entity()->{$key} = $value;
        }
        $query->entity()->sync();
      }
      return $success;
    });
  }

  protected function _read($query, array $options = array ()) {
    $params = compact('query', 'options');
    $_config = $this->_config;

    return $this->_filter(get_called_class() . '::read', $params, function($self, $params) use ($_config) {
          $query = $params['query'];
          $options = $params['options'];
          $args = $query->export($self, array ('conditions'));
          $source = implode(
            DIRECTORY_SEPARATOR, 
            array (
              rtrim($_config['startPoint'], DIRECTORY_SEPARATOR),
              rtrim($args['source'], DIRECTORY_SEPARATOR)
            )
          );
          if ($query->calculate()) {
            return $result;
          }
          $resource = $self->invokeMethod('_search', array ($options + compact('source')));
          $result = $self->invokeMethod('_instance', array ('result', compact('resource')));
          $config = compact('result', 'query') + array ('class' => 'set');
          return $self->item($query->model(), array (), $config);
        });
  }

  protected function _update($query, array $options = array ()) {
    $options += $this->_fileDefaults;
    $params = compact('query', 'options');
    $_config = $this->_config;
    $_exp = $this->_classes['exporter'];

    return $this->_filter(get_called_class() . '::update', $params, function($self, $params) use ($_config, $_exp) {
      $options = $params['options'];
      $query = $params['query'];
      $args = $query->export($self, array ('keys' => array ('conditions', 'data', 'source')));
      $data = $args['data'];

      if ($query->entity()) {
        $data = $_exp::get('update', $data);
        if (isset($data['update']['title'], $data['update']['filename'])) {
          if (strstr($data['update']['filename'], $data['update']['title']) === false) {
            return false;
          }
        }
        return $self->invokeMethod(
          '_fileUpdate', 
          array (              
            $query->entity()->filename,
            $data['update']
          )
        );

      }             
      $success =  false;
      $source = implode(
        DIRECTORY_SEPARATOR, 
        array (
          rtrim($_config['startPoint'], DIRECTORY_SEPARATOR),
          rtrim($args['source'], DIRECTORY_SEPARATOR)
        )
      );
      $resource = $self->invokeMethod('_search', array ($options + compact('source')));
      foreach ($resource as $file) {
        $success = $self->invokeMethod(
          '_fileUpdate', 
          array (              
            $file['filename'],
            $data
          )
        );
        if (! $success) {
          return false;
        }
      }
      return $success;
    });
  }

  protected function _delete($query, array $options = array ()) {
    $options += (array) $this->_fileDefaults;
    $params = compact('query', 'options');
    $_config = $this->_config;
    $_exp = $this->_classes['exporter'];
    
    return $this->_filter(get_called_class() . '::delete', $params, function($self, $params) use ($_config, $_exp) {
      $options = $params['options'];
      $query = $params['query'];
      $args = $query->export($self, array ('keys' => array ('conditions', 'data', 'source')));
      $filename = false;
      
      if ($query->entity()) {       
        $filename = $query->entity()->filename;
      }
      elseif (isset ($args['filename'])) {
        $filename = $args['filename'];
      }
      elseif (isset ($args['conditions']['filename'])) {
        $filename = $args['conditions']['filename'];
      }

      if ($filename){
        
        foreach ((array) $filename as $file){
          clearstatcache(true, $file);
          if (! file_exists($file)) {
            continue;
          }
          if(! unlink($file)){ 
            return false;
          }
          clearstatcache(true, $file);
        }
        return true;
      }
      $source = implode(
        DIRECTORY_SEPARATOR, 
        array (
          rtrim($_config['startPoint'], DIRECTORY_SEPARATOR),
          rtrim($args['source'], DIRECTORY_SEPARATOR)
        )
      );
      
      $resource = $self->invokeMethod('_search', array ($options + compact('source')));
      
      foreach ($resource as $file){
        if (! unlink($file['filename'])){
          return false;
        }          
      }
      return true;
      
    });
  }

  protected function _search($options) {
    $files = array ();
    $counter = 0;
    $options += array ('fields' => null, 'conditions' => null, 'limit' => null);
    $schema = $options['model']::schema();
    $fields = $schema->names();
    $schemaNullValues = array_fill_keys($fields, null);
    $conditions = (array) $options['conditions'] + $schemaNullValues + array ('title' => null, 'filename' => 'null');
    
    $intF = function ($type, $v) use ($conditions) {
      if ($conditions[$type] === null) {
        return true;
      }
      if (! is_array($conditions[$type])) {
        return $v == $conditions[$type];
      }

      $s = array ('==', '!=', '>', '<', '>=', '<=');
      $goodCondition = in_array($conditions[$type][0], $s) && is_numeric($conditions[$type][1]);
      if (!$goodCondition) {
        return false;
      }
      return eval("return $v {$conditions[$type][0]} {$conditions[$type][1]};");
    };
    $titleF = ($conditions['title'] === null) 
      ? function() { return true; } 
      : function ($v, $stat) use ($conditions) {
          $v = basename($v, "." . $stat['extension']);
          if (!is_array($conditions['title'])) {
            return $v === $conditions['title'];
          }
          if (lowercase($conditions['title'][0]) === 'like') {
            return preg_match($conditions['title'][0], $v);
          }
          return false;
        };

    $pathF = ($conditions['filename'] === null) 
      ? function () { return true; } 
      : function ($v) use ($conditions) {
          return realpath($v) === realpath($conditions['filename']);
        };

    $closure = function ($filename, $stat) use ($intF, $titleF, $pathF, $schema) {
      if (! $pathF($filename)) {
        return false;
      }

      foreach ($schema->names() as $field) {
        if ($schema->type($field) === 'integer') {
          if (! $intF($field, $stat[$field])) {
            return false;
          }
        }
      }
      if (! $titleF($filename, $stat)) {
        return false;
      }
      return true;
    };

    $result = $this->_ls(
      $options['source'], array ('stat' => true, 'full' => true, 'getDirs' => false), $closure
    );
    if (!$result) {
      return array ();
    }
    $notRequiedFields = array_diff($fields, (array) $options['fields'] ?:$fields);
    $fileFields = array_keys(reset($result));
    $notSchemaFields = array_diff($fileFields + $fields, $fields);

    foreach ($result as $file) {
      if ($file['filename'] === $options['source']) {
        continue;
      }
      if ($options['limit'] && $counter > $options['limit']) {
        break;
      }
      $file['body'] = file_get_contents($file['filename']);
      $file = array_diff_key($file, array_fill_keys($notSchemaFields, null));
      $record = array (
        'title' => basename($file['filename'], '.' . $file['extension']),
        'filename' => $file['filename'],
        ) + array_diff_key($file, array_fill_keys($notRequiedFields, null));
      $counter++;
      $files[] = $record;
    }

    return $files;
  }

  /**
   * Get files tree which inlude filepathes and stat about one
   * @return array
   */
  protected function _scan($options) {
    $tempArray = array ();
    $dir = rtrim($options['path'], DIRECTORY_SEPARATOR);
    clearstatcache(true, $dir);
    if (! file_exists($dir)) {
      return array ();
    }
    if (! is_dir($dir)) {
      return $this->_fileStat($dir, $options);
    }
    // Listing directory
    $handleDirectory = opendir($dir);
    if (! is_resource($handleDirectory)) {
      throw new Exception("Cannot open directory `'{$dir}'`");
    }
    while (false !== ($filename = readdir($handleDirectory))) {
      $path = $dir . '/' . $filename;
      if (in_array($filename, array ('.', '..'))) {
        continue;
      }
      if (! is_dir($path)) {
        $tempArray[] = $this->_tryCatch(
          '_scan', array (compact('path') + $options)
        );
        continue;
      }
      if (! $options['recursive']) {
        if ($data = $this->_fileStat($path, $options, true)) {
          $tempArray[] = $data;
        }
        unset ($data);
        continue;
      }

      $tempArray = array_merge(
        $tempArray, $this->_tryCatch('_scan', array (compact('path') + $options))
      );
    }

    $self = $this->_fileStat($options['path'], $options, true);
    if ($self) {
      $tempArray[] = $self;
    }
    closedir($handleDirectory);
    return array_filter($tempArray);
  }

  protected function _fileStat($path, $options, $dir = false) {
    $callable = $options['callable']? : function() { return true; };
    clearstatcache(); /// need to think about clear cache here;
    $perms = substr(sprintf('%o', fileperms($path)), -4);
    $perms_dec = octdec($perms);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $stat = (array) array_slice(stat($path), 13) 
      + compact('perms', 'dir', 'perms_dec', 'extension');
    if ($callable($path, $stat) === false) {
      return array ();
    }
    $result = array ();
    $result['filename'] = $options['full'] ? $path : basename($path);
    if ($options['stat']) {
      $result += $stat;
    }
    return $result;
  }

  protected function _fileSave($dirname, $filename, $data, $perms) {
    if (file_exists($dirname) && ! is_dir($dirname)) {
      return false;      
    }    
    if(! is_dir($dirname) && ! mkdir($dirname, $perms, true)) {
      return false;
    }
    $path = implode(DIRECTORY_SEPARATOR, array ($dirname, $filename));
    return file_put_contents($path, $data) !== false 
      && chmod($path, is_integer($perms) ? $perms : octdec($perms));
  }
  protected function _fileUpdate($filename, $update) {
    $success = true;
    $file = pathinfo($filename);
    $stat = $this->_fileStat($filename, array ('stat'=>true,'full'=>true,'callable'=>false));
    foreach ($update as $key => $value) {
      if (! $success){
        return false;
      }
      switch ($key) {
        case 'title' :          
          $value = $file['dirname'] . DIRECTORY_SEPARATOR . $value . '.' 
            . $file['extension'];
        case 'filename' :
          if(! rename($filename, $value)){
            clearstatcache(true, $filename);
            return false;
          }  
          if (! isset($update['perms'])){
            if (! chmod($value, $stat['perms_dec'])){
              return false;
            }
          }
          $filename = $value;
          $file = pathinfo($filename);
          break;
        case 'body' :
          $success = $this->_fileSave(
            $file['dirname'], 
            $file['basename'], 
            $value, 
            isset($update['perms']) ? $update['perms'] : $stat['perms_dec']
          );
          break ;
        case 'perms':
          $success = chmod($filename, is_integer($value) ? $value : octdec($value));
          break;
        case 'uid' :
          $success = chown($filename, $value);
          break;
        case 'gid' :
          $success = chgrp($filename, $value);
          break;
        case 'ctime' :
          break;
        case 'mtime' :
          $success = touch($filename, $value);
          break;
        default :
          break;
      }            
    }
    return $success;   
  }
  
  /**
	 * Format oneliner with exception core info
	 *
	 * @param  Exception $e Source exception
	 * @return string
	 */
	protected function _exceptionMessage($e) {
		$message = $e->getMessage();
		$class = get_class($e);
		$code = $e->getCode();
		$file = $e->getFile();
		$line = $e->getLine();
		return "{$class} [{$code}] {$message} in {$file}:{$line}";
	}

}
