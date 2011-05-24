<?php
/**
 * This file contains the configuration class
 * @name static configuration class mamurConfig
 * @package mamur
 * @subpackage config
 * @version 105
 * @mvc setup
 * @release Mamur 1.10
 * @svntag 105
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2011,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3
 *                   
 *  					          
 */ 
/*
  Licence:
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, version 3 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

  	General enquires email:  martinmarsh@mamur.org
  	Licence enquires email:  martinmarsh@sygenius.com
  	
  	Copyright (c) 2011 Sygenius Ltd 


 File Version: 105  compatible with versions V1.04+
 History:     Detailed history - Major Events
 100   09/12/2010 - First alpha version
 102   01/01/2011 - First beta candidate for trial in building live web sites
 103   02/02/2011 - Plugin loading
 106   10/04/2011 - Controllers,classes and Placeholders - version 1.10          

*******************************************************************************/


/**
 * The class provides a single point of reference to get all configuration details
 * Configuration is set up in index.php, bootstrap and the main configuration file configuration.xml
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
	
	private static $xmlConfigfile,$configXML;
	
	
	public function __construct(){
		print here;
		
	}
	
	/**
	 * processConfig saves configuration setup in index.php and bootstrap
	 * then reads configuration.xml file for the main setup variables
	 * @param $mamurPageConfig - settings made during start up not used after this call
	 * @return void
	 */
  	public static function  processConfig($mamurPageConfig){
	  	
	  	//set start time
	  	$mamurPageConfig['time_start'] = ((float)$mamurPageConfig['start_usec']+ (float)$mamurPageConfig['start_sec']);
	  	//read configuration
	  	self::$xmlConfigfile="{$mamurPageConfig['user']}/configuration.xml";
	    $rewriteXml=false;
	  	if(file_exists(self::$xmlConfigfile)){
	  		
	  		self::$configXML = new DOMDocument();
	        if(self::$configXML->load(self::$xmlConfigfile)){
	        	$configArray=array(); 
	        	
	        	self::getGroupAndElementData('settings','set',$mamurPageConfig,false);
	        
	            
	        	/**
	        	 * read globals
	        	 */
	          
	        	$configArray=array();  
	            $globalsLists=self::$configXML->getElementsByTagName('globals');
	            foreach( $globalsLists as $globalsListing){
	            	$globalList=$globalsListing->getElementsByTagName('global');
	 				foreach($globalList as $eTag){
	                	if($eTag->hasAttribute('name') ){
	                    	$configArray[$eTag->getAttribute('name')]= $eTag->nodeValue;
	                    }
	            	}
	        	}
	        	
	         
	        	self::$data['globals']=new mamurConfigData($configArray);
	        
	        	$configArray=array();  
	        	self::getGroupAndElementData('controllers','controller',$configArray);
	        	self::$data['controllers']=new mamurConfigData($configArray);
	        
	        
	        
	        	$configArray=array();
	        	self::getGroupAndElementData('plugins','plugin',$configArray);
	            self::$data['plugins']=new mamurConfigData($configArray);
	          
	              
	            $configArray=array();  
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
							            		
							            		$classType="";
							                	if($class->hasAttribute('type')) {
							                		$classType=$class->getAttribute('type');
							                	}
							                	
							            	    $load="";
							                	if($class->hasAttribute('load')) {
							                		$load=$class->getAttribute('load');
							                	}
							                	
							            	   	$file="";
							                	if($class->hasAttribute('file')) {
							                		$file=$class->getAttribute('file');
							                	}
							                	
							                	$configArray[$className]=array(
	                        							'type' => $typeName,
	                           							'module' => $moduleName,
	                            						'mvc' => $mvcName,
							                			'load' => $load,
							                	        'file' => $file,
	                            						'classType' => $classType
	                        					);
							                	
							                	
							            	}
							        	}
							        }
			            		}
	            		}
	              	}
	           }
	      

	           self::$data['classes']=new mamurConfigData($configArray);
	          
			   $configArray=array();  
	           self::getGroupAndElementData('placeholders','placeholder',$configArray);
	           self::$data['placeholders']=new mamurConfigData($configArray);
	              
 
	                      
	            $defaultZone=ini_get('date.timezone');
	            if(empty($defaultZone)){
	                   $defaultZone='UTC';
	            }
	             //add all config settings to file
	             self::$data['settings']=new mamurConfigData($mamurPageConfig);
	              
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
	 * This method gets the instance of a named data storage class eg
	 * $global =mamurConfig::get('globals'); //gets globals data
	 * print $global->server; prints the server value
	 * some data classes return arrays so cna use
	 * $plugin->forms['status'] or $plugin->getAll();
	 * 
	 * @param $className - name of data storage eg settings, global, controllers, placeholder,
	 * @return unknown_type
	 */
	public static function get($className){
		if(isset( self::$data[$className])){
		   return self::$data[$className];
		}else{
			return false;
		}
	}
	
	/**
	 * Sets up typical configuration of Group with Tags containing only attributes
	 * @param $group - outer containing tag eg settings
	 * @param $tag   - the inner tag eg set
	 * @param $setArray - the array to set eg self::$config
	 * @param $setByName - uses name attribute value to set array (default) otherwise assumes attribute=value is setting
	 * @return null
	 */
	
	private static function getGroupAndElementData($group,$tag,&$setArray,$setByName=true){
		$lists=self::$configXML->getElementsByTagName($group);
		foreach( $lists as $listing){
			$defaultList=array();
			if($listing->hasAttributes()){
				foreach ($listing->attributes as $attrName => $attrNode){
					$defaultList[$attrName]=$attrNode->value;
				}
				
			}
	    	$elementList=$listing->getElementsByTagName($tag);
	        foreach($elementList as $eTag){
	        	if($eTag->hasAttributes()) {
	        		if($setByName){
	        			if($eTag->hasAttribute('name')){
	        				$name=$eTag->getAttribute('name');
	        				$setArray[$name]=$defaultList;
	        		    	foreach ($eTag->attributes as $attrName => $attrNode) {
	        		    		if($attrName!='name'){
	        		    			$setArray[$name][$attrName]= $attrNode->value;   
	        		    		}
	        		    	}
	        			}
	        		}else{
	            		foreach ($eTag->attributes as $attrName => $attrNode) {
	                		$setArray[$attrName]= $attrNode->value;    	
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

	public static function setPlugIn($name='',$status,$file,$version=''){
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

	public static function setConfig($name='',$value=''){
	    if($name=='salt' && $value=='new'){
	            $value=self::getRandomString(117);
	       }elseif($name=='salt'){
	          //for security do not allow salt to be set to a known value
	           $name='';
	    }
	    if($name!=''){
	          
	           self::$data['settings']->set($name,$value);
	           $xpath = new DOMXPath(self::$configXML);
	           $setList=$xpath->query("/configuration/settings/set[@$name]");
	           if($setList->length>0){
	                $setList->item(0)->setAttribute($name, $value);
	           }else{
	               $element = self::$configXML->createElement('set');
	               $section=  self::$configXML->getElementsByTagName('settings')->item(0);
	               $newnode = $section->appendChild($element );
	               $newnode->setAttribute($name,$value);
	           }
	    }
	}

	public static function getRandomString($length){
    	$string='';
        $min=1;
        $max=7;
        for ($i=1; $i<=$length; $i++){
            $random=rand($min,$max);
            if($random==4){
               $string.=chr(rand(50,57));
            }elseif($random<4){
                $newchar=chr(rand(97,122));
                $string.=$newchar;
            }else{
                 $newchar =chr(rand(65,90));
                 $string.=$newchar;
            }
        }
       return $string;
	}

	public static function upDateConfigFile(){
		self::$configXML->save(self::$xmlConfigfile);
	}

	

	public static function setGlobal($name,$value){
         self::$data['globals']->set($name,$value);
	
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




