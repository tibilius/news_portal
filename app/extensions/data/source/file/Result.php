<?php

namespace app\extensions\data\source\file;

/**
 * Description of Result
 *
 * @author tibilius
 */
class Result extends \lithium\data\source\Result {
  
  public function rewind() {
    parent::rewind();
    $this->_iterator = 0;
  }

  public function prev() {
    if (  $this->_resource && isset($this->_resource[$this->_iterator-1])) {
      $result = $this->_resource[$this->_iterator-1];			
			$this->_key = $this->_iterator-1;
			$this->_current = $result;
      $this->_iterator--;
			return true;
    }
		return null;
	}

	protected function _fetchFromCache() {
		return null;
	}

	/**
	 * Fetches the result from the resource and caches it.
	 *
	 * @return boolean Return `true` on success or `false` if it is not valid.
	 */
	protected function _fetchFromResource() {
		if ($this->_resource && isset($this->_resource[$this->_iterator])) {
			$result = $this->_resource[$this->_iterator];			
			$this->_key = $this->_iterator;
			$this->_current = $result;
      $this->_iterator++;
			return true;
		}
		return false;
	}  

}