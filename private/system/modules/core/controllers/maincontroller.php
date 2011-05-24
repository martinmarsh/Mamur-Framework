<?php
/**
 * This file contains the start up class - file name mamur.php unless renamed
 * @name mainController
 * @package mamur
 * @subpackage core
 * @version 110
 * @mvc controller
 * @release Mamur 1.10
 * @svntag 110
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2011,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3
 *                   
 *  					          
 */ 
/*
 * 
 * *****************************************************************************

                    Controller  Class
  Mamur Content Server; for Dynamic Serving of Web Pages using Templates
  File Version: 1.04  Copyright (c) 2011 Sygenius Ltd  Date: 19/02/2011

 1st Released on System tag: 104

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

 Application:
  Mamur Page Server sits on top of Apache and is easily integrated by adding
 .htaccess to the root directory. When a html\xhtml web page is requested the url
 is passed to this server to produce the page dynamically from templates.
 The website sits in a directory /website. Pages which have _page.xml definition
 will override any existing page by that name and produce the page from templates
 as defined with the xml definition

 We use a simple MVC model for this level which might be considered only the
 view of a higher level MVC model.


 Important:   Requires php 5.2 and .htaccess enabled


 Install:     Place in mamur folder/system/php

 Description:


 Author:      Martin Marsh.
 Notes:


Acknowlgedements:

Martin Marsh        - Architect, designer and coder for version 1
(Sygenius Ltd)        Based on earlier works created for Sygenius Ltd


 Author:      Martin Marsh.

 File Version: 104  compatible with versions V1.04+
 History:     Detailed history - Major Events
 104   18/02/2010 - Controller in separate file


*******************************************************************************/

/**
 * 
 * @package mamur
 * @subpackage core
 */
class mamurMainController{
	//the controller processes the url request and causes the url to print
	//There can be more than one instance of an mvc set can be created.
	//An editor might create one for example. On page load one MVC is always set
	//up to print the requested page and then it calls printPage.

	protected $view,$model;

	public function  __construct(&$mamurPageModel,&$mamurPageView){

   		$config=mamurConfig::$config;
   		$this->model=$mamurPageModel;
   		$this->view=$mamurPageView;
   		//Here we will do setting up and make a call to print the page
   		//setups view class to produce page
   		//cookie definition - a permament and session one is used

  
    	//set up locid cookies
    	if(!isset($_COOKIE["locid"]) && isset($config['allowPermCookie']) && $config['allowPermCookie']=='yes' ){
        	$this->model->setLocidCookie();
    	}else{
        	$this->model->confirmLocid($_COOKIE["locid"]);
   		 }	
    	$mamurPageView->setModel($mamurPageModel);
	}
	
	
	public static function processUri(){
		$set=mamurConfig::get('settings');
		
		//find appropriate controller to dispatch to
		$controllerToUse="mamurMainController";
		$controllers=mamurConfig::get('controllers');
		foreach($controllers->getAll() as $name=>$controller){
			if(preg_match($controller['match'],$set->uri)>0){
				$controllerToUse=$name;
				break;
			}
		}
		
		print "****matched $controllerToUse ****";
		
		if(!isset($_COOKIE["locid"]) && $set->allowPermCookie=='yes' ){
        	//$this->model->setLocidCookie();
        	print "cookies allowed";
    	}else{
    		print "cookies not allowed $set->allowPermCookie {$_COOKIE['locid']}";
        	//$this->model->confirmLocid($_COOKIE["locid"]);
   		}
   		//see if another controller is required
   			
		
	}

	public function getModel(){
    	return  $this->model;
	}

	public function getView(){
    	return  $this->view;
	}




	public function printPage($url){
    	//this function runs through the steps to print a page
    	//buffer all out put to allow headers and cookies to be processed
    	//this should have been set up in index.html but we do another just in case
    	ob_clean();
    	ob_start();
    	ob_implicit_flush(true);

    	//check to see if logout must be forced automatically can be cancelled
    	//by plugin
    	$this->model->checkLogOut();
    	//ask model to sets up the page url
    	$this->model->setPageUrl($url);

    	//A plugin via this hook can pre-process urls or can trap them and do all processing
    	//itself.
    	if($this->model->pageProcessHookContinue()){

        	$fileExtension=$this->model->getPageExt();

        	if($fileExtension=='php'){
           		//php files bypass the templating engine
           		//include the php file within a view just like a [php /] tag
           		$this->view->directPhpView();

        	}else{
          		//This is a STANDARD URL request for page templating
              //control now requests that model reads the page data
              //and any xml page and template definitions
              $this->model->readPageXML();
               //We now instruct view to print via templating
               //the view will ask the model for status and processing of errors
              $this->view->templatedView();
        	}
    	}
    	//now just before print output (ob_end_flush) we will set session cookie and store
    	//any new session data
    	if($this->model->getConfigValue('allowSessionCookie')=='yes' ){
        	$this->model->setSessionCookie();
        	$this->model->saveDataSets();
    	}
    	ob_end_flush();

	}

}


