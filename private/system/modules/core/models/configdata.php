<?php


class mamurConfigData{
	
    private $data;
    
    public function __construct($settings){
    	$this->data=$settings;
    }

    public function getAll(){
    	return $this->data;
    }
	public function __get($variable){
		if(isset($this->data[$variable])){
			return $this->data[$variable];
		}else{
			return null;
		}
	
	}
	
     public function get($variable){
		if(isset($this->data[$variable])){
			return $this->data[$variable];
		}else{
			return null;
		}
	
	}

	public function __isset($variable){
	  return isset($this->data[$variable]);
	}
	
    public function __set($variable,$value){
		$this->data[$variable]=$value;
	
	}
	
   public function set($variable,$value){
		$this->data[$variable]=$value;
	
	}  
	
}
