<?php
/**
 * This file contains the start up class and custom error functions.
 * This file is not notmally auto-updated with new versions but may
 * be overwritten or re-configured should an error be found.
 * If you have made custom change the next line to "NO".
 * ::UPDATE_ALLOWED:YES 
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
 * @version 110
 * @mvc setup
 * @release Mamur 1.10
 * @svntag 110
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
  Version: 1.1  Copyright (c) 2011 Sygenius Ltd  Date: 22/05/2011

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

 

*******************************************************************************/

/**
 * Moderately secure set up uses just one mamur folder
 * A better set up would split mamur folder so that private is in
 * a folder above web access
 * mamurStart::setup(.'/../mamur/private','/mamur/public');
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
class mamurStart{
	
	/**
	 * See class desciption for details
	 * @param $privateDir - directory relative to this file mamur where private files are stored with php r/w access
	 * @param $publicDir  - web home directory for mamur to access resources etc
	 * @return void
	 */
	
	public static function setup($privateDir,$publicDir){
		$set=array();
		/**
		 * The following can be changed for special purposes in which case
		 * alter or remove the update allowed setting
		 */
	    $set['root']=realpath(dirname(__FILE__));
	    $set['startFile']=__FILE__;
		$set['mamur']=realpath($set['root'].'/'.$privateDir);
        $set['system']=$set['mamur']."/system";
        $set['root']=realpath($root);
        $set['public']=$publicDir;
        $set['user']=$set['mamur']."/user";
        $set['plugins']=$set['mamur']."/plugins";
        $set['logDir']=$set['user']."/errorlogs";
        $set['uri']=$_SERVER['REQUEST_URI'];
		list($set['start_usec'], $set['start_sec'])= explode(" ", microtime());
		require($set['system'].'/modules/core/models/configdata.php');
		require($set['system'].'/modules/core/models/config.php');
		require($set['system'].'/modules/core/controllers/maincontroller.php');
        mamurConfig::processConfig($set);
        
        //use object methods with $set from now on:
        $set=mamurConfig::get('settings');
        error_reporting(-1);
		set_error_handler('mamurErrorHandler');
		set_exception_handler('mamurExceptionHandler');
		if(strtolower($set->firePhp)=='yes'){
			require($set->mamur.'/firephp/FirePHP.class.php');
		}       
 	    mamurMainController::processUri();  //process according to config set up
	}
	
}

/**
 * Execption handler for uncaught exceptions
 * @param $exception
 * @return unknown_type
 */

function mamurExceptionHandler($exception) {
   $set=mamurConfig::get('settings');
   $logfile="critical_setuperrors_log.txt";
   $logDir=$set->logDir;
   print  'uncaught_exception';
   $geterror= date("Y-m-d H:i:s (T)")." Uncaught Exception Page URL: '{$_SERVER['REQUEST_URI']}' was aborted due to: ".$exception->getMessage();
   error_log("$geterror\n",3,"$logDir/{$logfile}");
     
   if(class_exists('FirePHP')){
		$firephp = FirePHP::getInstance(true);
		$firephp->trace($geterror);
   }		
}

/**
 * This is the mamur default Error handler. Its output is controlled by
 * settings in Configuration XML
 * You may wish to add new settings there and modify the logic below
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
	
   // if error has been supressed with an @
   if (error_reporting() == 0) {
        return;
   }  	
   $set=mamurConfig::get('settings');  //get the settings object
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

    $printstr='';
    $myerrorlevel=0;
    $dt = date("Y-m-d H:i:s (T)");
     //use htmlentities for security 
    $errstr=htmlentities("$dt,$errno,$errmsg,$filename,$linenum,{$_SERVER['REQUEST_URI']}");

   /**
    * Comment out or add lines  below to report errors
    * set $logvalue to "" to stop logging of a particular error
    * could also set up mail or other error logs
    */

    $terminate=false;
    switch($errno){
       case E_ERROR:
       case E_RECOVERABLE_ERROR:
                $myerrorlevel=2;
                $printstr="ERROR: $errstr<BR>\n";
                $logfile="error_log.txt";
                break;
       case E_WARNING:
                $myerrorlevel=2;
                $printstr="warning: $errstr<BR>\n";
                $logfile="warning_log.txt";
                break;
       case E_USER_ERROR:
    			if(strpos($errstr,"FATAL")!==false){
                   	  $terminate=true;
                }
                $myerrorlevel=4;
                $printstr="warning user critical: $errstr<BR>\n";
                $logfile="critical_setuperrors_log.txt";
                break;
       case E_USER_NOTICE:
                $myerrorlevel=3;
                $printstr="warning setup: $errstr<BR>\n";
                $logfile="setup_notices_log.txt";
                break;
       default:
              $myerrorlevel=1;
              $printstr="warning minor: $errstr<BR>\n";
              $logfile="minor_notices_log.txt";

    }

         if ($printstr!=""){
             if($pstat=='all' || ($pstat=='major' && $myerrorlevel>3)){
                print $printstr;
                if($debugTrace=='YES' && $myerrorlevel>3){
                	if(isset($vars) && is_array($vars)
                    ){
                    	print "<br/>Variables:<br />"; 
                   		var_dump($vars);            	
                    }
                    print "<br/>Back Trace:<br />";
                    debug_print_backtrace(); 
                    print "<br/><hr/><br/>";                          	
                }
             }
        }
        if(class_exists('FirePHP')){
			$firephp = FirePHP::getInstance(true);			
			$firephp->log($errstr, 'PHP Error raised');
			if ($debugTrace=='YES' && $myerrorlevel>3){
				if(isset($vars) && is_array($vars) ){
                 	$firephp->log($vars, 'Trapped Variable State');           	
            	}
                $firephp->log(debug_backtrace(), 'Back Trace');        
            	
        	}
        	if(strpos($errstr,"TRACE")!==false){
        	     $firephp->trace($errstr);
        	} 
		}  
        
        if (isset($logfile) && $logfile!=""){
        	if($debugTrace=='YES' && $myerrorlevel>3){
            	if(isset($vars) && is_array($vars)){
                    	$errstr.="\nVariables:\n";
                   		$errstr.= var_export($vars, true); 
                }
                $errstr.="\nBack Trace:\n";                            	
                $errstr.= var_export(debug_backtrace(),true);
                $errstr.="\n---\n";   
            }
            if($lstat=='all' || ($lstat=='major' && $myerrorlevel>3) || $terminate){
            	if($terminate){
            		$errstr="\n\n***** TERMINATED ERROR:\n$errstr";
            	}
             	error_log("$errstr\n",3,"$logDir/{$logfile}");
            }
        }
        
       
        if($terminate){
        	exit("<br/>This page is currently unavailable due to a technical error<br/>");
        }
        
    
        
        return true;  //continues with out php error handler

}


ob_end_flush(); //now fush any output