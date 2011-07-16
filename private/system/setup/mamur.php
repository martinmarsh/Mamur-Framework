<?php
/**
 * This file contains the start up class and custom error functions.
 * You may wish to adapt these for you own use; if so please change the next line
 * to "NO".
 * ::UPDATE_ALLOWED:YES 
 * If set to yes any changes the start directories defined in the line:
 * mamurStart::setup('./mamur/private','/mamur/public');
 * will be copied to the update any other changes will be lost.
 * If set to no this file must be modified manually, however, it is
 * not expected that his file will be updated very frequently. 
 * This page should not Output anything ie ensure that there no spaces
 * before php tag and do not use an end tag.
 * Note: Mamur is >5.24 compatible and to avoid naming issues the mamur
 * uses only classes and functions starting with "mamur".
 * Also mamur uses no gloabl variables or sessions.
 * Post 5.3 mamur will run in global namespace and new user modules should
 * use namespaces.
 *
 * @name start up
 * @package mamur
 * @subpackage startup
 * @version 112
 * @mvc setup
 * @release Mamur 1.10
 * @releasetag 110
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2011,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3
 *                   
 *  					          
 */ 
ob_start();  //use out put buffering  at very start to avoid header issues
ob_implicit_flush(false);
/*
 * *****************************************************************************
                         	Start file
  						  Mamur Framework
  Version: 1.1  Copyright (c) 2011 Sygenius Ltd  Date: 16/07/2011

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


   Email:  martinmarsh@sygenius.com

 

*******************************************************************************/

/**
 * Moderately secure set up uses just one mamur folder with the
 * private folder protected by .htaccess.
 * If your host allows a better set up would split mamur folder so that
 * private is in a folder above web access eg
 * mamurStart::setup(.'/../mamur/private','/mamur/public');
 * The settings on the next line will always be preserved even if
 * even if UPDATE_ALLOWED is set to YES 
 */
mamurStart::setup('./mamur/private','/mamur/public');


/**
 * The class and the access commands above are placed in mamur.php file normally
 * located in the home page directory. If there is a .htaccess or other uri rewrite
 * system then all .html files and .htm files should be rooted to this file.
 * Alternatively every url using the mamur framework must (after any domain name definition)
 * start with mamur.php followed by the rest of the page request. 
 * 
 * Files with .php extension can bypass mamur or be integrated into mamur and
 * handled by special controllers
 * 
 * This file defines file locations set up configuration class to read the configuration.xml
 * in the mamur user directory and runs the main mamurController invoking the appropriate
 * mvc modules
 * 
 * @package mamur
 * @subpackage startup
 *
 */
abstract class mamurStart{
	
	/**
	 * See class description for details
	 * @param $privateDir - directory relative to this file mamur where private files are stored with php r/w access
	 * @param $publicDir  - web home directory for mamur to access resources etc
	 * @return void
	 */
	
	public static function setup($privateDir,$publicDir){
	
	 
		/**
		 * The following can be changed for special purposes in which case
		 * alter or remove the update allowed setting
		 */
		$root=realpath(dirname(__FILE__));
		$mamur=realpath($root.'/'.$privateDir);
        $system=$mamur."/system";
		require($system.'/modules/core/models/mamurConfigData.php');
		$set=new mamurConfigData();
	   
	    $set->startFile=__FILE__;
		$set->mamur=$mamur;
        $set->system=$system;
        $set->public=$publicDir;
        $set->webBaseDir=$root;      //Base directory of web site
        
        if(file_exists("$mamur/usersites/user")){
        	$set->user="$mamur/usersites/user";
        }else{
        	$set->user="$mamur/usersites/exampleuser";
        }
        $set->plugins="$mamur/plugins";
        $set->logDir=$set->user."/errorlogs";
        $set->build=$set->user."/build";
        $set->uri=$_SERVER['REQUEST_URI'];  //used to define page request
        $set->host=$_SERVER["HTTP_HOST"];   //defines host domain
        
		list($set->start_usec, $set->start_sec)= explode(" ", microtime());
		
		require($set->system.'/modules/core/models/mamurConfig.php');
		//get configuration class
		$config=mamurConfig::getInstance();
        $config->processConfig($set);
        
        //use object methods with $set from now on:
        $set=$config->settings;
        error_reporting(-1);
        register_shutdown_function('mamurShutDown');
		set_error_handler('mamurErrorHandler');
		set_exception_handler('mamurExceptionHandler');
		if(strtolower($set->firePhp)=='yes'){
			require($set->mamur.'/vendor/firephp/FirePHP.class.php');
		} 

		//Define the top most autoload function
		//In mamur you can use this function to add aditional autoloaders
		spl_autoload_register('mamurAutoClassLoad',false);
	
		//now load the required pre-loaded classes
		$classes=$config->classes;
		foreach($classes->getAll() as $name=>$class){ 
			$type=$class->type;
            $file=$set->$type."/modules/{$class->module}/{$class->mvc}/";
			if($class->load=='onstart'){
				if(!empty($class->file)){
					$file.=$class->file;
				}else{
					$file.="$name.php";
				
				}
				include_once($file);
			}
			
		}
		//call mamur controller to process uri according to config set up     
 	    mamurController::processUri();  
	}
	
} //end of mamurStart class

/**
 * Mamu Autoload Class function registerred at start up
 * Loads a class configured in confguration.xml
 */
function mamurAutoClassLoad($name){
   $config=mamurConfig::getInstance();
   $classes=$config->classes;	
   if (isset($classes->$name)){
   	   $type=$classes->$name->type;
   	   $file=$config->settings->$type."/modules/{$classes->$name->module}/{$classes->$name->mvc}/$name.php";
   	   require_once($file);
   }
}

/**
 * Fatal Errors must also be trapped and logged
 * @return void
 */
function mamurShutDown(){	
     if (($error = error_get_last())) {       
			$geterror= date("Y-m-d H:i:s (T)").
			     " FATAL ERROR CAUSED SHUTDOWN of Page URI: '{$_SERVER['REQUEST_URI']}' due to error: ".
			     $error['message']." in ".$error['file']." line ".$error['line'];
			if(class_exists('FirePHP')){
				$firephp = FirePHP::getInstance(true);			     	
				$firephp->log($geterror);
			}	
			$logDir=mamurConfig::getInstance()->settings->logDir;
   			$logfile="fatal_shutdowns_log.txt";
   			error_log("$geterror\n",3,"$logDir/{$logfile}");	
   					
      }	
}

/**
 * PHP Exception handler for uncaught exceptions
 * @param $exception
 * @return unknown_type
 */
function mamurExceptionHandler($exception) {
   $logDir=mamurConfig::getInstance()->settings->logDir;
   $logfile="critical_exceptions_log.txt";
   print  'uncaught_exception';
   $geterror= date("Y-m-d H:i:s (T)")." Uncaught Exception Page URL: '{$_SERVER['REQUEST_URI']}' was aborted due to: ".$exception->getMessage();
   error_log("$geterror\n",3,"$logDir/{$logfile}");
     
   if(class_exists('FirePHP')){
		$firephp = FirePHP::getInstance(true);
		$firephp->trace($geterror);
   }		
}

/**
 * PHP Default Error handler
 * This is the mamur default Error handler. Its output is controlled by
 * settings in Configuration XML
 * User error messages can use:
 *     trigger_error($message,E_USER_ERROR) - allows variable trace if a setting
 * or  trigger_error($message,E_USER_NOTICE)- for general debug messages
 * firePhp will also be logged errors if the word "TRACE" appears in error message
 * eg trigger_error('TRACE message key words in message') - will issue firePHP
 * trace and log a USER_NOTICE error
 *
 * Note: Policy to continue on USER ERROR - add call exit in your script after
 * trigger USER_ERROR needs or include the word "FATAL" in capitals in message
 * 
 * @param $errno - the level of the error raised, as an integer. 
 * @param $errmsg -  the error message, as a string. 
 * @param $filename - the filename that the error was raised in, as a string. 
 * @param $linenumthe - line number the error was raised at, as an integer. 
 * @param $vars - read only array of every variable that existed in the scope when error was triggered in.
 * @return void
 */
function mamurErrorHandler($errno, $errmsg, $filename, $linenum, $vars)
{  
	$set=mamurConfig::getInstance()->settings;  //get the settings object
	
    $dt = date("Y-m-d H:i:s (T)");
    //use htmlentities for security 
   	$errstr=htmlentities("$errmsg,$filename,$linenum,{$_SERVER['REQUEST_URI']}");
   	$printstr='';
   	$myerrorlevel=0;
   	$errorPrint=strtolower($set->errorPrint);  //print level
   	$errorLog=strtolower($set->errorLog);      //log error level
   	$logDir=$set->logDir;
  	$debugTrace=strtoupper($set->debugTrace);   //set yes for debug trace

    if(isset($errorPrint)){
        $pstat=$errorPrint;  //'all' or none or major for production
    }else{
       $pstat='all';
    }
    if(isset($errorLog)){
       $lstat=$errorLog;  //major or none for production, all for debug
    }else{
       $lstat="all";
    }

   
   /**
    * Comment out or add lines  below to report errors
    * set $logvalue to "" to stop logging of a particular error
    * could also set up mail or other error logs
    */

    $terminate=false;
   
    switch($errno){
       case E_ERROR:
       case E_RECOVERABLE_ERROR:
                $myerrorlevel=4;
                $printstr="ERROR: $errstr<BR>\n";
                $logfile="error_log.txt";
                break;
       case E_WARNING:
                $myerrorlevel=3;
                $printstr="WARNING: $errstr<BR>\n";
                $logfile="warning_log.txt";
                break;
       case E_USER_ERROR:
    			if(strpos($errstr,"FATAL")!==false){
                   	  $terminate=true;
                }
                $myerrorlevel=5;
                $printstr="USER ERROR NOTICE: $errstr<BR>\n";
                $logfile="critical_setuperrors_log.txt";
                break;
       case E_USER_NOTICE:
                $myerrorlevel=2;
                $printstr="USER DEBUG NOTICE: $errstr<BR>\n";
                $logfile="debug_notices_log.txt";
                break;
       case E_NOTICE:
       	        $myerrorlevel=4;
                $printstr="PHP ERROR NOTICE: $errstr<BR>\n";
                $logfile="php_notices_log.txt";
                break;
       	
       default:
              $myerrorlevel=1;
              $printstr="DEFAULT ERRORT: no. $errno: $errstr<BR>\n";
              $logfile="default_notices_log.txt";

	}
    //always do firbug reporting even if supressed error
    if(class_exists('FirePHP')){
		$firephp = FirePHP::getInstance(true);
		if (error_reporting() == 0) {
			$printstr="Supressed @$printstr";
		}
		if(strpos($errmsg,"TRACE")!==false){
			//do firebug trace if TRACE in user error message
       		$firephp->trace($printstr);
        }else{			
			$firephp->log($printstr);
			if ($debugTrace=='on' && $myerrorlevel>3){
				if(isset($vars) && is_array($vars) ){
                 	$firephp->log($vars, 'Trapped Variable State');           	
            	}
                $firephp->log(debug_backtrace(), 'Back Trace');        
        	}
        	
        }
        if(strpos($errmsg,"EXIT")!==false){
        	$terminate=true;
        }
        	
	}  
   	// if error has not been supressed with an @ end
   	if (error_reporting() != 0) {
   		if ($printstr!=""){
        	if($pstat=='all' || ($pstat=='major' && $myerrorlevel>3)){
            	print $printstr;   
             }
        }
        
        if (isset($logfile) && $logfile!=""){
            if($lstat=='all' || ($lstat=='major' && $myerrorlevel>3) || $terminate){
            	if($terminate){
            		$printstr="\n\n***** TERMINATED ERROR:\n$printstr";
            	}
             	error_log("$printstr\n",3,"$logDir/{$logfile}");
            }
        }
        
       
        if($terminate){
        	exit("<br/>This page is currently unavailable due to a technical error<br/>");
        }
        
    
   }     
        return true;  //continues without php error handler

}

