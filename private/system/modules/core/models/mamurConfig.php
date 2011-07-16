<?php
/**
 * This file contains the configuration class
 * @name static configuration class mamurConfig
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


/**
 * The class provides a single point of reference to get all configuration details
 * Configuration is set up in index.php, the main configuration file configuration.xml
 * The bootstrap is responsible for setting up this config class by calling the method processConfig
 * Configuration details can then be read directly by getting the appropriate data class
 * and using the magic __get methods to get the appropriate variables; for further
 * details see mamurConfig::$globalSet. Also Magic set method allows configuration data to be
 * added at run time but will be lost when the request terminates. To update the
 * xml configuation an explicit config update and flush must be called. 
 * class methods can write and persist configuration details to the configuation.xml file
 * or perform configuration related tasks.
 * Do not modify - core system class overwritten by updates
 * @package mamur
 * @subpackage config
 */

class mamurConfig{
	
	private static $data;
	
	private static $xmlConfigfile,$configXML,$persist;
	
	// Hold an instance of the class
    private static $instance;
    
    // A private constructor; prevents direct creation of object
    private function __construct()
    {
    }
    
    // The singleton method
    public static function getInstance() 
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }

	/**
	 * processConfig saves configuration setup in index.php and bootstrap
	 * then reads configuration.xml file for the main setup variables
	 * It is normally called only once by the controller bootstrap mamur.php
	 * Each data group is handled by a mamurConfigData class which makes it
	 * easy to use settings when required since the class instance can be 
	 * obtained by a call to this class eg
	 * $set =mamurConfig::getInstance->settings ; //gets settings data
	 * print $set->server; 
	 * @param $mamurPageConfig - settings made during start up and added to the settings data class
	 * @return void
	 */
  	public function  processConfig($mamurPageConfig){
	  	self::$persist=false;
	    self::$data['settings']=$mamurPageConfig;
	  	//set start time used to get an idea of processing speed
	  	self::$data['settings']->time_start= ((float)self::$data['settings']->start_usec+ (float)self::$data['settings']->start_sec);
	  	//read configuration
	  	self::$xmlConfigfile=self::$data['settings']->user."/configuration.xml";
	    $rewriteXml=false;
	  	if(file_exists(self::$xmlConfigfile)){
	  		
	  		self::$configXML = new DOMDocument();
	        if(self::$configXML->load(self::$xmlConfigfile)){
	        	
	        	self::getGroupAndElementData('settings','set','settings',false);
	        
	            
	        	/**
	        	 * read globals (Strings which may appear in content) into a
	        	 * in mamurConfigData class object stored in self::$data['globals']
	        	 */
	          
	        	self::$data['globals']=new mamurConfigData();
	            $globalsLists=self::$configXML->getElementsByTagName('globals');
	            foreach( $globalsLists as $globalsListing){
	            	$globalList=$globalsListing->getElementsByTagName('global');
	 				foreach($globalList as $eTag){
	                	if($eTag->hasAttribute('name') ){
	                		$attrName=$eTag->getAttribute('name');
	                    	self::$data['globals']->$attrName= $eTag->nodeValue;
	                    }
	            	}
	        	}
	        	
	        	
	        	
	        	/**
	        	 * read controllers XML settings and save in an 
	        	 * mamurConfigData class saved in self::$data['globals']
	        	 */
	        	self::$data['controllers']=new mamurConfigData();
	        	self::getGroupAndElementData('controllers','controller','controllers');
	     
	             /**
	        	 * read controllers XML settings and save in $data
	        	 * in mamurConfigData class saved in self::$data['globals']
	        	 */
	        	self::$data['plugins']=new mamurConfigData();
	        	self::getGroupAndElementData('plugins','plugin','plugins');
	            
	            self::$data['classes']=new mamurConfigData();
	            $classesLists=self::$configXML->getElementsByTagName('classes');
	            foreach($classesLists as $typeslist){
	            	$types=$typeslist->getElementsByTagName('type');
	            	foreach($types as $type){
						$typeName=$type->getAttribute('name');
						$modules=$type->getElementsByTagName('module');
						foreach($modules as $module){
			            		$status="inactive";
			            		$moduleName=$module->getAttribute('name');
			            		if ($module->hasChildNodes()) {
	                            	$mvcList = $module->childNodes;
							        foreach($mvcList as $mvcType){	
							        	if($mvcType->nodeType==1){
							            	$mvcName=$mvcType->nodeName;
							            	$classList=$mvcType->getElementsByTagName('class');
							            	foreach($classList as $class){	
							            		$className=$class->getAttribute('name');	
							            		
							            	    $load="";
							                	if($class->hasAttribute('load')) {
							                		$load=$class->getAttribute('load');
							                	}
							                	
							            	   	$file="";
							                	if($class->hasAttribute('file')) {
							                		$file=$class->getAttribute('file');
							                	}
							                	if($mvcName=='mvc'){
							                	   $mvcArray=array('controllers','models','views');
							                	}else{
							                	   $mvcArray=array($mvcName);
							                	}
							                	foreach($mvcArray as $useMVC){
							                		$nameArray=array();
							                		if(strpos($className,'*')!==false || strpos($className,'?')!==false){	
   	   													$find=self::$data['settings']->$typeName."/modules/{$moduleName}/{$useMVC}/$className.php";
   	   													foreach (glob($find) as $filename) {
    														$nameArray[]=basename($filename,'.php');
														}
														
							                		}else{
							                			if($mvcName=='mvc'){
							                				$ext=ucfirst(substr($useMVC,0,-1));
							                				$nameArray=array($className.$ext);ucfirst(substr($useMVC,0,-1));
							                			}else{
							                				$nameArray=array($className);
							                			}
							                		}
							                		
								                	foreach($nameArray as $useClassName){
								                		//mvc type creates a controller, model, and view
								                		$classInfo=new mamurConfigData();
								                		$classInfo->type=$typeName;
								                		$classInfo->module= $moduleName;
								                		$classInfo->mvc = $useMVC;
									                	$classInfo->load = $load;   								                		 
														self::$data['classes']->$useClassName=$classInfo;
									                	
								            		}
							            		}
							               	    
							            	}
							        	}
							        }
			            		}
	            		}
	              	}
	           }
	    
	          
	       
	          
			   self::$data['placeholders']=new mamurConfigData();  
	           self::getGroupAndElementData('placeholders','placeholder','placeholders');
	         
	              
   
	            $defaultZone=ini_get('date.timezone');
	            if(empty($defaultZone)){
	                   $defaultZone='UTC';
	            }
	          
	              
	            //time zone setting
	            if(!isset(self::$data['settings']->serverTimeZone)){
	                self::setConfig('serverTimeZone',$defaultZone);
	               
	                $rewriteXml=true;
	                
	            }
	            date_default_timezone_set(self::$data['settings']->serverTimeZone);
	
	
	
	            if(!isset(self::$data['settings']->userTimeZone)){
	                 
	                 self::setConfig('userTimeZone',$defaultZone);
	               
	                 $rewriteXml=true;
	                 
	            }
	              
	           if($rewriteXml){
	           	  self::upDateConfigFile();	
	           }  
	     
	
	        }else{
	            print "configuration file {self::$xmlConfigfile} is corrupt. Fatal error!";
	            exit();
	
	        }
	    }else{
	        print "configuration file {self::$xmlConfigfile} cannot be found. Fatal error!";
	        exit();
	    }
	}
	
    /**
     * __get magic method allows
     * $config=mamurConfig::getInstance();
     * $config->variable and $config->$mayvariable contructs
     * @param $variable an item (value or array) in the data array 
     * @return void
     */
	public function __get($variable){
	    if(isset( self::$data[$variable])){
		   return self::$data[$variable];
		}else{
			return null;
		}
	
	}
	
	
	
	
	
	/**
	 * Sets up typical configuration of Group with Tags containing only attributes
	 * @param $group - outer containing tag eg settings
	 * @param $tag   - the inner tag eg set
	 * @param $setConfig - the config element
	 * @param $setByName - uses name attribute value to set array (default) otherwise assumes attribute=value is setting
	 * @return null
	 */
	
	private static function getGroupAndElementData($group,$tag,$setConfig,$setByName=true){
		$lists=self::$configXML->getElementsByTagName($group);
		foreach( $lists as $listing){
			$defaultConfigAttributes=new mamurConfigData();
			if($listing->hasAttributes()){
				foreach ($listing->attributes as $attrName => $attrNode){
					$defaultConfigAttributes->$attrName=$attrNode->value;
				}				
			}
	    	$elementList=$listing->getElementsByTagName($tag);
	        foreach($elementList as $eTag){     	   
	        	$configAttributes=clone $defaultConfigAttributes;
	        	if($eTag->hasAttributes()) {
	        		if($setByName){
	        			if($eTag->hasAttribute('name')){
	        				$name=$eTag->getAttribute('name');
	        				
	        		    	foreach ($eTag->attributes as $attrName => $attrNode) {
	        		    		if($attrName!='name'){
	        		    			$configAttributes->$attrName= $attrNode->value;   
	        		    		}
	        		    	}
	        		    	self::$data[$setConfig]->$name=$configAttributes;
	        			}
	        		}else{
	            		foreach ($eTag->attributes as $attrName => $attrNode) {
	                		self::$data[$setConfig]->$attrName= $attrNode->value;    	
	                	}
	        		}
	             }
	        }
		}	

	}
	           
	/**
	 * Gets a list of plugins to load and associated data
	 * @return string array - plugin information
	 */

	public static function getPlugInsToLoad(){
	     $ret=array();
	
	     foreach( self::$data['plugins']->getAll() as $mamurPlugInName=>$plugInItem){
	     	if($plugInItem['status']=='active'){
	        	$mamurPlugDirFile=self::getPluginDir($plugInItem['file']);
	        	if($mamurPlugDirFile!='' && file_exists($mamurPlugDirFile)){
	            	$ret[]=$mamurPlugDirFile;
	        	}
	     	}
	     }
	    return $ret;
	}
	
	/**
	 * Instantiates each plugin and saves the object in a config 
	 * @return void
	 */

	public static function createPlugIns(){
		foreach( self::$data['plugins']->getAll() as $mamurPlugInName=>$plugInItem){
			if($plugInItem['status']=='active'){
        		$mamurPlugInClass="mamurPlugIn_".$mamurPlugInName;
        		$pluginClass=null;
        		if(class_exists($mamurPlugInClass)){
            		//$pluginClass=new $mamurPlugInClass();
            		//self::setPlugInClass($mamurPlugInName,$pluginClass);
            		$plugInItem['class']=new $mamurPlugInClass();
            		self::$data['plugins']->set($mamurPlugInName,$plugInItem);
        		}
			}
      	}
	}

	public static function runPlugIns(&$model,&$view){
	    foreach( self::$data['plugins']->getAll() as $plugInName=>$plugInItem){
	      if($plugInItem['status']=='active' && method_exists($plugInItem['class'],'afterLoaded')){
	            //$this->model->getPlugInClass($plugInName)->afterLoaded();
	            $plugInItem['class']->afterLoaded($model,$view);
	      }
	    }
	}

	public static function getPluginDir($subdir=''){
	       return self::relativeDir(self::$data['settings']->plugins,$subdir);
	}

	public static function relativeDir($dir,$subdir){
	      if($subdir==''){
	         $ret=$dir;
	      }else{
	        if(substr($dir,-1)=='/'){
	                          $dir=substr($dir,0,-1);
	        }
	        if(substr($subdir,0,1)!='/'){
	                          $subdir='/'.$subdir;
	        }
	        $ret=$dir.$subdir;
	
	      }
	        return $ret;
	    }
	    
	/**
	 * Defines a plugin and places the plugin details in configuration.xml
	 * @param $name - plugin name
	 * @param $status - status eg active
	 * @param $file - file location
	 * @param $version - plugin version
	 * @return void 
	 */

	public function setPlugIn($name='',$status,$file,$version=''){
	        $pluginArray=self::$data['plugins']->get($name);
	        if(empty($pluginArray))$pluginArray=array();
	        $pluginArray['file']=$file;
	        $pluginArray['status']=$status;
	        $pluginArray['version']=$version;
	        
	        self::$data['plugins']->set($name,$pluginArray);
	
	      if( $name!=''){
	          $xpath = new DOMXPath(self::$configXML);
	          $setList=$xpath->query("/configuration/plugins/plugin[@name='$name']");
	          if($setList->length>0){
	            $setList->item(0)->setAttribute('status',$status);
	            $setList->item(0)->setAttribute('file',  $file);
	            if($version!=''){
	               $setList->item(0)->setAttribute('version',$version);
	            }
	          }else{
	            $element = self::$configXML->createElement('plugin');
	            $section=  self::$configXML->getElementsByTagName('plugins')->item(0);
	            $newnode = $section->appendChild($element );
	            $newnode->setAttribute('name',$name);
	            $newnode->setAttribute('status',$status);
	            $newnode->setAttribute('file',  $file);
	            $newnode->setAttribute('version',  $version);
	          }
	      }
	    }

	/**
	 * 
	 * Persists to the XML config data a setting value
	 * The data is not saved unless upDateConfig is called
	 * Name must be defined and if apiId or salt is set then
	 * for security the XML file must have a value set to new
	 * ie must not have been set before 
	 * @param $name  - name of setting
	 * @param $value - value to persist
	 */    
	public function persistSetting($name='',$value=''){
	    if($name!=''){ 	           
	           $xpath = new DOMXPath(self::$configXML);
	           $setList=$xpath->query("/configuration/settings/set[@$name]");
	           if($setList->length>0){
	           	    if (($name!='apiId' && $name!='salt') ||
	           	        $setList->item(0)->getAttribute($name)=='new'){
	           	     	self::$data['settings']->$name=$value;
	                	$setList->item(0)->setAttribute($name, $value);
	                	self::$persist=true;
	           	     }else{
	           	     	//for security silent failure visible only if fireBug is running
	           	     	@trigger_error("Once set you cannot change the setting $name");
	           	     }
	           }else{
	               $element = self::$configXML->createElement('set');
	               $section=  self::$configXML->getElementsByTagName('settings')->item(0);
	               $newnode = $section->appendChild($element );
	               $newnode->setAttribute($name,$value);
	               self::$data['settings']->$name=$value;
	               self::$persist=true;
	           }
	    }
	}

	
	
    /**
     * 
     * This is normally called by controller at end of page
     * it automatically saves and configurations which have been
     * set using persitSettings() method
     */
	public function upDateConfig(){
		if(self::$persist){
			self::$configXML->save(self::$xmlConfigfile);
		}
	}

	

	public static function setGlobal($name,$value){
         self::$data['globals']->$name=$value;
	
        $doc=self::$configXML;
        $xpath = new DOMXPath($doc);
        $setList=$xpath->query("/configuration/globals/global[@name='$name']");
       	if($setList->length>0){
            $setList->item(0)->nodeValue=$value;

       	}else{
           $element = $doc->createElement('global');
           $section=  $doc->getElementsByTagName('globals')->item(0);
           $newnode = $section->appendChild($element );
           $newnode->setAttribute('name',$name);
           $newnode->nodeValue=$value;
       }
	}
	
//end of class
}




