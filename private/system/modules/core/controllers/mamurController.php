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
 * @subpackage core
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
					
		//find appropriate controller to dispatch to
		$controllerToUse="mamurMainController"; 
		foreach($config->controllers->getAll() as $name=>$controller){
			if(preg_match($controller->match,$set->uri)>0){
				$controllerToUse=$name;
				break;
			}
		}
	
		@trigger_error("TRACE matched $controllerToUse");
		
		if(!isset($_COOKIE["locid"]) && $set->allowPermCookie=='yes' ){
        	self::$model->setLocidCookie();
    	}else{
        	self::$model->confirmLocid($_COOKIE["locid"]);
   		}
   		//see if another controller is required
   		if($controllerToUse!='mamurController'){
   			self::$controller=new $controllerToUse;
   			self::$controller->response($set->uri);
   			
   		}else{
   			self::$controller=null;
   			self::$view=new mamurView();
   			self::$view->setModel(self::$model);
   			self::response($set->uri);	
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
	 * response method has the controll steps to resond to a url
	 * and print a page.
	 * @param $uri - address of a page
	 * @return unknown_type
	 */
	
	protected static function response($uri){
    	//this should have been set up in mmaur.php but we do another just in case
    	ob_clean();
    	ob_start();
    	ob_implicit_flush(true);
        $config=mamurConfig::getInstance();
        
    	//check to see if logout must be forced automatically can be cancelled
    	//by a plugin
    	self::$model->checkLogOut();
    	//sets up the page url
    	self::$model->setPageUrl($uri);

    	//A plugin via this hook can pre-process urls or can trap them and do all processing
    	//itself.
    	if(self::$model->pageProcessHookContinue()){

        	$fileExtension=self::$model->getPageExt();

        	if($fileExtension=='php'){
           		//php files bypass the templating engine
           		//include the php file within a view just like a [php /] tag
           		self::$view->directPhpView();

        	}else{
          	  //This is a STANDARD URL request for page templating
              //control now requests that model reads the page data
              //and any xml page and template definitions
              self::$model->readPageXML();
               //We now instruct view to print via templating
               //the view will ask the model for status and processing of errors
              self::$view->templatedView();
        	}
    	}
    	//now just before print output (ob_end_flush) we will set session cookie and store
    	//any new session data
    	if($config->allowSessionCookie=='yes' ){
        	self::$model->setSessionCookie();
        	self::$model->saveDataSets();
    	}
    	ob_end_flush();

	}

}


