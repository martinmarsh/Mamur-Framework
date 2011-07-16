<?php
/**
 * mamurDataObject encapsulates data in an array structure.
 * The concept is that instead of using session arrays similar
 * data items can be grouped into meaningful data objects which
 * can be persisted to session data or to an ORM or database. 
 * mamurDataObject can be a single data item or a list of items  
 * accessed using $mydataObjectClass->$myVariableName object
 * magic methods.
 * Also dataobjects can support mutiple rows to form a table 
 * There are methods to switch rows and to index through
 * row items.
 * Note unlike using an ORM everything is stored in memory so it
 * is designed for use of small datasets or subsets of data from
 * a query.
 * @name mamurDataObject 
 * @package mamur 
 * @subpackage model
 * @version 105
 * @mvc model
 * @release Mamur 1.10
 * @releasetag 105
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2011,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3
 *                   
 *  					          
 */ 


class mamurDataObject{
	
    private $data,$record,$attributes,$status;
    /**
     * 
     * Constructor initialises data in a simple associated data array
     *   $dataSet['table'][$tableName][$record][$fieldName]=$value;
     * @return void
     */
    public function __construct(){
    	$this->data=array();
    	$this->data[0]=array();
    	$this->record=0;
    	$this->setStatus('data','none');
    	$this->setStatus('read','no');
    	$this->setStatus('save','no');
    }
    

    /**
     * 
     * getAll method returns entire data table array
     * @return array of records
     */
    public function getAll(){
    	return $this->data;
    }
    
     /**
     * 
     * getRecord method returns assoicated array of a record
     * @return array of record items
     */
    public function getRecord(){
    	$ret=array();
    	if(isset($this->data[$this->record])){
    		$ret=$this->data[$this->record];
    	}
    	return $ret;
    }
    
  
     /**
     * 
     * returns current record number
     * @return array of record items
     */
    public function getCurrentRecordNumber(){
    	return $this->record;
    }
    
    /**
     * 
     * Sets current record returning the new record number set
     * @param integer valid record number
     * @return record or false
     */
    public function setCurrentRecordNumber($record){
    	$ret=false;
    	if(isset($this->data[$record])){
    		$this->record=$record;
    		$ret=$record;
    	}
    	return $ret;
    }
    
    /**
     * 
     * Appends a new record defined by an associated array
     * or a blank record if not given and sets the record pointer 
     * to point to the new record allowing access to variables
     * @param array $recordData an associated array
     */
    public function appendRecord($recordData=array()){
    	$this->modified();
    	$this->data[]=$recordData;
    	return $this->last();
    }
    
   
    
    /**
     * 
     * Deletes specified record or if not given the current record
     * If the record deleted is the current record the current record
     * will be set to  the next record or if not possible the last record
     * @param integer $record record number
     * @return integer the current record number
     */
    public function deleteRecord($record=-1){
    	$rec=$record;
    	$ret=false;
    	if($record==-1)$rec=$this->record; 
    	
    	if($rec>=0 && isset($this->data[$rec])){
    		unset($this->data[$rec]);
    		$this->modified();
    		if($rec==$this->record){
	    		if($this->next()===false){
	    			if($this->last()===false){
	    				$this->record=0;
	    			}
	    		}
    		}
    		$ret=$this->record;
    	}    	
    	return $ret;
    }

    /**
     * 
     * If possible Moves record pointer forward by one 
     * @return integer the record number moved too or false if not moved
     */
     
    public function next(){
    	$ret=false;
    	$try=$this->record+1;
    	if(isset($this->data[$try])){
    		$this->record=$try;
    		$ret=$try;
    	}
    	return $ret;
    }
    
    public function back(){
    	$ret=false;
    	$try=$this->record-1;
    	if($try>=0 && isset($this->data[$try])){
    		$this->record=$try;
    		$ret=$try;
    	}
    	return $ret;
    }
    
    public function last(){
    	$ret=false;
    	$try=count($this->data)-1;
    	if($try>=0 && isset($this->data[$try])){
    		$this->record=$try;
    		$ret=$try;
    	}
    	return $ret;
    	
    }
    
    public function setAttribute($name,$value){
    	$this->modified();
    	$this->attributes[$name]=$value;
    	return $value;
    }
    
    public function getAttribute($name){
    	$attr=false;
    	if(isset($this->attributes[$name])){
    		$attr=$this->attributes[$name];
    	}
    	return $attr;
    }
    
    public function setStatus($name,$value){
    	$this->status[$name]=$value;
    	return $value;
    }
    
    public function getStatus($name){
    	$status=null;
    	if(isset($this->status[$name])){
    		$status=$this->status[$name];
    	}
    	return $status;
    }
    
    public function persist(){
    	$this->setStatus('save','persist');
    	return true;
    }
    
    public function read(){
    	$this->setStatus('read','read');
    	return true;
    }
    
    public function modified(){
    	$this->setStatus('data','modified');
    	return true;
    }
    
    /**
     * 
     * __get magic method allows
     * $dataClass->variable contructs
     * @param $variable an item 
     * @return void
     */
	public function __get($variable){
		if(isset($this->data[$this->record][$variable])){
			return $this->data[$this->record][$variable];
		}else{
			return null;
		}
	
	}
	
	
	/**
	 * 
	 *  __isset magic method allows isset($dataClass->variable) construct
	 * @param $variable
	 * @return void
	 */

	public function __isset($variable){
	  return isset($this->data[$this->record][$variable]);
	}
	
	/**
	 * 
	 *  __set magic method allows $dataClass->variable=value contruct
	 * @param $variable
	 * @return void
	 */
	
	
    public function __set($variable,$value){
    	$this->modified();
		$this->data[$this->record][$variable]=$value;
	}
	
    /**
     * 
     * allows unset($dataclass->variable) construct
     * @param unknown_type $variable
     */
    public function __unset($variable){
    	$this->modified();
		unset($this->data[$this->record][$variable]);
	
	}
	
}
