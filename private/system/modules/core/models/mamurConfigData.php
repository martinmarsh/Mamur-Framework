<?php
/**
 * mamurConfigData encapuslates a simple data array used to hold configuation data 
 * so that name=variable configuations can be accessed by $dataClass->$myVariable
 * contructs.
 * However, some configuations have multiple attributes per variable in which case
 * the data encapuslated is an array of mamurConfigData objects so that
 * $dataClass->$myVariable->$myAttribute can be used.
 * @name mamurConfigData
 * @package mamur 
 * @subpackage config
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


class mamurConfigData{
	
    private $data;
    /**
     * Constructor initialises data in a simple associated data array
     * of values or for multi-attribute configuration an array
     * of mamurConfigData objects. 
     * @param $settings - array of values or mamurConfigData objects
     * @return void
     */
    public function __construct(){
    	$this->data=array();
    }

    /**
     * getAll method returns entire data array
     * @return void
     */
    public function getAll(){
    	return $this->data;
    }
    
    /**
     * __get magic method allows
     * $dataClass->variable and $dataClass->$mayvariable contructs
     * @param $variable an item (value or array) in the data array 
     * @return void
     */
	public function __get($variable){
		if(isset($this->data[$variable])){
			return $this->data[$variable];
		}else{
			return null;
		}
	
	}
	
	
	/**
	 *  __isset magic method allows isset($dataClass->variable) construct
	 * @param $variable
	 * @return void
	 */

	public function __isset($variable){
	  return isset($this->data[$variable]);
	}
	
	/**
	 *  __set magic method allows $dataClass->variable=value contruct
	 *  if multi-level data use
	 *  $dataClass->variable=new mamurConfigData($array) so that
	 *  $dataClass->variable->variable works
	 * @param $variable
	 * @return void
	 */
	
	
    public function __set($variable,$value){
		$this->data[$variable]=$value;
	
	}
	
    /**
     * 
     * allows unset($dataclass->variable) construct
     * @param unknown_type $variable
     */
    public function __unset($variable){
		unset($this->data[$variable]);
	
	}
	
}
