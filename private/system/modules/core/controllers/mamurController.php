<?php
/**
 * This file contains the static main Controller Class - mamurController
 * Control may be dispatched to other contollers and mvc patterns according to 
 * configuration xml.
 *  Licence:
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, version 3 of the License.
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *  
 * @name mainController
 * @package mamur
 * @subpackage core
 * @version 110
 * @mvc controller
 * @release Mamur 1.10
 * @releasetag 110
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2011,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3
 *                   
 *  					          
 */ 


/**
 * mamurController is a static class which is always the top level
 * controller. For simple pages it just processes the url according to
 * configuration xml which defines the tags which can be used to build
 * a page using templates. For more complex dynamic pages configuration xml
 * may define another controller which optionally may run a different
 * mvc pattern to respond to certain page requests
 * @package mamur
 * @subpackage coreController
 */
abstract class mamurController{
	
	protected static $view,$model,$controller;

	/**
	 * This method processes all page requests either passing
	 * control to another controller or responds directly
	 * to the request
	 * @return void
	 */
	
	public static function processUri(){
		$config=mamurConfig::getInstance();
		$set=$config->settings;	
		self::$model=new mamurModel();
		//if permited by config set or retrieve locid permanent cookie
		self::$model->locidCookie();
					
		//find appropriate controller to dispatch to
		$controllerToUse="mamurMainController"; 
		foreach($config->controllers->getAll() as $name=>$controller){
			if(preg_match($controller->match,$set->uri)>0){
				$controllerToUse=$name;
				break;
			}
		}
	
		//@trigger_error("TRACE matched $controllerToUse");
         $contents=ob_get_contents ();
         if(!empty($contents)){
         	@trigger_error("Unexpected Output just before clearing buffer and running $controllerToUse controller");
         }
		 ob_end_clean(); //turn off the buffer and clean output so far
		 		
   		//see if another controller is required
   		if($controllerToUse!='mamurController'){
   			//this an alternative controller
   			//In alternative controller's response action copy the mamur 
   			//controller code below in order to to use mamur managed cookies,
   			//nonce, sessions and dataObjects must start with ob_start plus
   			//setupSession and end with ob_end_flush

   			self::$controller=new $controllerToUse;
   			self::$controller->response($set->uri);
   			
   		}else{
   			//use mamur controller
   			ob_start();
   			ob_implicit_flush(false);
   			self::$controller=null;
   			self::$model->setUpSession();
   			self::$view=new mamurView();
   			self::$view->setModel(self::$model);
   			self::response($set->uri);	
   			//now complete the session set up
   			//now just before print output (ob_end_flush)
   			//we will set session cookie and store
    		//any new session data
    	
    		if($set->allowSessionCookie=='yes' ){
        		self::$model->setSessionCookie();
        		self::$model->saveDataObjects();
    		}
    		//update the config file if required
    		$config->upDateConfig();
    		//flush buffer to return output
    		ob_end_flush();
   		}
   		
   			
	}

	/**
	 * Get controller gets the current controller dispatched by processUri
	 * @return void
	 */
	public static function getController(){
		return  self::$controller;
	}
	
	/**
	 * Get view gets the current view instance
	 * @return void
	 */
	public static function getView(){
		return  self::$view;
	}
	
	/**
	 * response method has the control steps to respond to a url
	 * and update print buffer.
	 * @param $uri - address of a page
	 * @return unknown_type
	 */
	
	protected static function response($uri){
        $config=mamurConfig::getInstance(); 
    	//check to see if logout must be forced automatically can be cancelled
    	//by a plugin
    	self::$model->checkLogOut();
    	//sets up the page url
    	self::$model->setPageUri($uri);

    	//A plugin via this hook can pre-process urls or can trap them and do all processing
    	//itself.
    	if(self::$model->pageProcessHookContinue()){

        	$fileExtension=self::$model->getPageExt();

        	if($fileExtension=='php'){
           		//php files bypass the templating engine
           		//include the php file within a view just like a [php /] tag
           		self::$view->directPhpView();

        	}else{
          	  //This is a STANDARD URL request       	  
          	  $builtFile=$config->settings->build.self::$model->getPageDir().'/'.self::$model->getPageName().".php";
          	  if($config->settings->pageBuild=='yes' && file_exists($builtFile)){
          	  	self::$view->showBuiltPage($builtFile);
          	  }else{
	        	   //obtain details about a pages meta data including which template to use
	             self::$model->processPageMetaData();
	               //We now instruct view to print via templating
	               //the view will ask the model for status and processing of errors
	             self::$view->templatedView();
          	  }
        	}
    	}
    	

	}
   
}


