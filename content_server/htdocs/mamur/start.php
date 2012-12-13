<?php
/** 
 * @name start
 * @package mamur
 * @subpackage mamur
 * @version 200
 * @mvc config
 * @release Mamur 2.0
 * @releasetag 200
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2012,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3
 *                   
 *  					          
 */ 
namespace mamur; 
ob_start();  //use out put buffering  at very start to avoid header issues
ob_implicit_flush(false);
/*
 * *****************************************************************************
                         	Start
                            Mamur Framework
  Version: 2.0  Copyright (c) 2011 Sygenius Ltd  Date: 31/10/2012

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

   This file breaks conventions by having all the statup, config, error
   handling and autoloading in one place. A bit messy but easy to find
   and efficienct as just one file to load.  Also apart from config area
   indicated you are free to edit and adapt this file whereas updating
   core mamur files will not survive the next release.
 

*******************************************************************************/

/**
 * Provided .htaccess is enabled the default setup is moderately secure.
 * If not using .htaccess or an equivalent apache config you should move
 * at least the private folder outside the webspace and alter the config below.
 * This is recommended in all cases where security is a concern.  Only the public
 * folder must be accessible from the web. This file can be included from
 * outside the webspace but relative directories in the config will be from
 * the location of the file which included this file.
 * 
 * Namespaces relate to the folder structure asdefined in the default single
 * mamur folder configuration ie assuming that the entire mamur system is in
 * a folder named mamur and placed in the public web home folder (root 
 * directory). Thus config is in mamur namespace and mamur contollers are
 * in mamur\private\system\core\controllers.
 * IMPORTANT if the private area is moved or the mamur directory renamed
 * the namespaces do not change
 */


error_reporting(-1);
date_default_timezone_set('Europe/London');

$roData=array(
//
    //Directory references must be absolute using the OS
    //standard for directory separator ie '\' on windows
    //Relative dirs must be without '.' refernces and must
    //must start with the directory separator either '/' or '\'
    //which is automatically corrected for OS in use.
    //The following may be autoconfigured only manually change
    //the assignments not variables or layout or comments
    //Do not chnage next quote line in anyway
    // ***** Start Auto Config Area *****  
    'allowDebug'       => TRUE,   //False is safest for production,
    'allowErrorPrint'  => TRUE,   //True allows manual override/
    'allowErrorLog'    => TRUE,   //settings to be changed
    'allowDebugTrace'  => TRUE,
    'defaultErrorPrint'=> 'none', //Errors to print; none for production else all or major
    'defaultErrorLog'  => 'major',  //Errors to log; major or none for production else all
    'absoluteRefForPrivate' => __DIR__,
    'movePrivateRefBy'      => 0, //see note 1 below
    'relativePrivateDir'    => '/private', //relative to above
    'absoluteRefForPublic'  => __DIR__,
    'distanceToWebRoot' => -1, //see note 2 below
    'relativePublicDir' => '/public',  //realtive to above
    'apiServiceName'    => "A00001.".str_replace('www.','', $_SERVER['SERVER_NAME']),
    'apiEmailAddress'   => 'martinmarsh@sygenius.com',
    'apiSecret'         => 'cn4&in2w1$FaP£vS4',
    'databases'         => array(
        'contentDb' => array(
            'dsn'       => 'sqlite:%%database%%/%%mode%%/content.sqlite',
            'user'      => null,
            'password'  => null,
            'options'   => array(
                                \PDO::ATTR_PERSISTENT => true
                            )),
        'usersDb'       => array(
            'dsn'       => 'sqlite:/%%database%%/%%mode%%/users.sqlite' 
                          )
        )
    
    // ***** End Auto Config Area ****
    );
 
    //some defualt read write settings
 $rwData= array(
            'databaseMode'=>'live',  //sets database to live
            'debug'       => TRUE,   //False for production, TRUE for debug/firePHP
            'errorPrint'  => 'none', //Errors to print; none for production else all or major
            'errorLog'    => 'all',  //Errors to log; major or none for production else all
            'debugTrace'  => TRUE   //false for production        
    );
    /*note 1: The private directory refernce is found by moving the given reference
     * according to this value, negative to move up directoies from given ref,
     * positive to move ref to a sub directory level form root.
     * 
     * note 2: The public absolute ref must be within the public area
     * and cannot be moved. The relativePublicDir specifies the sub directory
     * below the reference which contains the Mamaur public files. The distanceToWebRoot
     * is always negative and defines the location of the web root relative to the
     * refernce. Normally -1 if public is in the Mamur folder.  The allows the
     * mamur folder to be renamed and the new value will be used without a config
     * change.
     * 
     */
 


// Ensure library/ is on include_path
//set_include_path(implode(PATH_SEPARATOR, array(
//    $config->vendorDir,
//    get_include_path()
//)));

$config=config::create($roData,$rwData);

register_shutdown_function('\mamur\shutDown');
set_error_handler('\mamur\errorHandler');
set_exception_handler('\mamur\exceptionHandler');
if ($config->allowDebug && strtolower($config->debug)==true){
  require($config->vendorDir.DIRECTORY_SEPARATOR.'firephp'.DIRECTORY_SEPARATOR.'FirePHP.class.php');
} 


//Define the top most autoload function
//You can also use this function to add aditional autoloaders
spl_autoload_register('\mamur\autoClassLoad',false);

if (!empty($_SERVER['HTTP_X_MAMUR_SERVICE'])){

    $controller='\\mamur\\services\\controllers\\'.trim($_SERVER['HTTP_X_MAMUR_SERVICE']);
    $dispatch=new $controller();
} elseif($config->getRequestItem(0) =='__service' ){
    $controller='\\mamur\\services\\controllers\\'.$config->getRequestItem(1);
    $dispatch=new $controller();
} else{
    $dispatch=new \mamur\services\controllers\www();
}
$dispatch->preDispatch();

/**
 * The class and the access commands above are placed in mamur.php file normally
 * located in the home page directory. If there is a .htaccess or other uri rewrite
 * system then all .html files and .htm files should be rooted to this file.
 * Alternatively every url using the mamur framework must (after any domain name definition)
 * start with /mamur/start.php followed by the rest of the page request. 
 * 
 * @package mamur
 * @subpackage config
 * @namespace: mamur
 *
 */

class config
{
	
    /**
     * See class description for details
     * @param $privateDir - directory relative to this file mamur where private files are stored with php r/w access
     * @param $publicDir  - web home directory for mamur to access resources etc
     * @return void
     */

    protected $ro;      //read only variables defined on start
    protected $rw;      //other system set global configuration
    
    protected static $me;
    
    private function __construct(){ }
    
    private function __clone(){ }
    
    public function __get($var){
        /* @var $var as passed to magic get*/
        $ret=null;
        if(isset($this->ro[$var])){
            $ret=$this->ro[$var];
        } elseif(isset($this->rw[$var])){
            $ret=$this->rw[$var];
        }

        return $ret;
    }
   
    public function __set($var,$val){
        $this->rw[$var]=$val;
    }
    
    public function __isset($var){
        /* @var $var as passed to magic get*/
        $ret=false;
        if(isset($this->ro[$var])){
            $ret=true;
        } elseif(isset($this->rw[$var])){
            $ret=true;
        }
        return $ret;
    }
   
            
   public static function get()
   {
       //unlike standard singleton pattern
       //this class can only be instatiated by create
       //which sets some main RO (read only) data
        return self::$me;
    }
   
    public function getRequestItem($item=0)
    {
        return $this->ro['uriList'][$item];
    }
 
    public static function create($roData,$rwData)
    {
        //only first create works
        //subsequent get singleton but
        //use getSingleton reads better
        if (!self::$me){
            self::$me = new self();
            self::$me->createSettings($roData,$rwData);
        }
        return self::$me;
    }

    
    private function createSettings($roData,$rwData)
    {     
        $this->ro=$roData;
        $this->rw=$rwData;
       
        list($this->ro['startUSec'], $this->ro['startSec'])= explode(" ", microtime());    
        
        $absPrivate=$this->ro['absoluteRefForPrivate'];
        $this->ro['relativePrivateRef']='';
        if($this->ro['movePrivateRefBy']!=0){
            $absList=explode(DIRECTORY_SEPARATOR,$this->ro['absoluteRefForPrivate']);
            $absListRemoved=array_splice($absList,$this->ro['movePrivateRefBy']);
            $this->ro['relativePrivateRef']=DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$absListRemoved);
            $absPrivate=implode(DIRECTORY_SEPARATOR,$absList);
        }
        $this->ro['webRoot'] = $this->ro['absoluteRefForPublic'];
        if($this->ro['distanceToWebRoot']!=0){
            $absList=explode(DIRECTORY_SEPARATOR,$this->ro['absoluteRefForPublic']);
            $absListRemoved=array_splice($absList,$this->ro['distanceToWebRoot']);
            $this->ro['relativePublicRef']=DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$absListRemoved);
            $this->ro['webRoot'] = implode(DIRECTORY_SEPARATOR,$absList);          
        }

        if(DIRECTORY_SEPARATOR!='/'){
            $this->ro['relativePrivateDir']= str_replace('/', DIRECTORY_SEPARATOR, $this->ro['relativePrivateDir']);
            $this->ro['relativePublicDir'] = str_replace('/', DIRECTORY_SEPARATOR, $this->ro['relativePublicDir']);
        }
        $this->ro['privateRoot']=$absPrivate.$this->ro['relativePrivateDir'];
        $this->ro['publicRoot']=$this->ro['absoluteRefForPublic'].$this->ro['relativePublicDir'];
        $this->ro['publicUri']=$this->ro['relativePublicRef'].$this->ro['relativePublicDir'];
        $this->ro['publicUri'] = str_replace(DIRECTORY_SEPARATOR,'/', $this->ro['publicUri']);
 
        $thisFile=str_replace($this->ro['webRoot'],'',__FILE__);
        if(DIRECTORY_SEPARATOR!='/'){
            $thisFile= str_replace(DIRECTORY_SEPARATOR,'/', $thisFile);
        }
        $this->ro['requestUri']=str_replace($thisFile,'',$_SERVER['REQUEST_URI']);
        $this->ro['uriList']=explode('/',$this->ro['requestUri']);
        array_shift($this->ro['uriList']);
        $this->ro['domain']=$_SERVER["HTTP_HOST"];   //defines host domain
 	$this->ro['logDir']=$this->ro['privateRoot'].DIRECTORY_SEPARATOR.'logs';
        $this->ro['vendorDir']=$this->ro['privateRoot'].DIRECTORY_SEPARATOR.'Vendor';
        $this->ro['mamurDir']=$this->ro['vendorDir'].DIRECTORY_SEPARATOR.'mamur';
        $this->ro['applicationDir']=$this->ro['privateRoot'].DIRECTORY_SEPARATOR.'application';
        $this->ro['databaseDir']=$this->ro['privateRoot'].DIRECTORY_SEPARATOR.'database';
        
  
        return self::$me;
    }
   
} //end of config class

/**
 * Mamur Autoload Class function registerred at start up
 */
function autoClassLoad($name){
    $nameSpaceList=explode('\\',$name);
    $config=\mamur\config::get();
 
    if(count($nameSpaceList)<=1){
        $parts=explode('_',$name);
   	$file=DIRECTORY_SEPARATOR.array_pop($parts).'.php';
        $dir='';
        if(count($parts)>0){
            $dir=DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$parts);
        }
        $load=$dir.$file;
   	if(!empty($parts[0])){
            require($config->vendorDir.$load);
            //require($config->vendorDir.$load); 
        }else{
            require($config->mamurDir.$load);
            //require($config->mamurDir.$load); 
        }                      
    } else {
        $file=array_pop($nameSpaceList);
   	$dir=implode(DIRECTORY_SEPARATOR,$nameSpaceList);
   	$load=$dir.DIRECTORY_SEPARATOR."$file.php";
        require($config->vendorDir.DIRECTORY_SEPARATOR.$load);
            //require($config->vendorDir.$load); 
    }
    
    return;
}

/**
 * Fatal Errors must also be trapped and logged
 * @return void
 */
function shutDown(){
    $config=\mamur\config::get();
    if (($error = error_get_last())) {       
        $geterror= date("Y-m-d H:i:s (T)").
                " FATAL ERROR CAUSED SHUTDOWN of Page URI: '{$_SERVER['REQUEST_URI']}' due to error: ".
                $error['message']." in ".$error['file']." line ".$error['line'];
	
        if(class_exists('FirePHP') && $config->debug){
            $firephp = \FirePHP::getInstance(true);			     	
            $firephp->log($geterror);
	}
        	
	$logDir=$config->logDir;
   	$logfile="fatal_shutdowns_log.txt";
   	error_log("$geterror\n",3,"$logDir/{$logfile}");	
   					
     }	
}

/**
 * PHP Exception handler for uncaught exceptions
 * @param $exception
 * @return unknown_type
 */
function exceptionHandler($exception) {
   $config=\mamur\config::get();
   $logDir=$config->logDir;
   $logfile="critical_exceptions_log.txt";
  // print  'uncaught_exception'.$logDir;
   $geterror= date("Y-m-d H:i:s (T)")." Uncaught Exception Page URL: '{$_SERVER['REQUEST_URI']}' was aborted due to: ".$exception->getMessage();
   error_log("$geterror\n",3,"$logDir/{$logfile}");
   
   if(class_exists('FirePHP')){
		$firephp = \FirePHP::getInstance(true);
		$firephp->trace($geterror);
   }
     
}

/**

 *  * PHP Default Error handler
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
function errorHandler($errno, $errmsg, $filename, $linenum, $vars)
{  
    $config=\mamur\config::get();  //get the settings object	
    $dt = date("Y-m-d H:i:s (T)");
    //use htmlentities for security 
   	$errstr=htmlentities("$errmsg,$filename,$linenum,{$_SERVER['REQUEST_URI']}");
   	$printstr='';
   	$myerrorlevel=0;
        if ($config->allowErrorPrint && isset($config->errorPrint) ){
            $errorPrint=strtolower($config->errorPrint);  //print level
        } else {
            $errorPrint=strtolower($config->defaultErrorPrint);  //print level           
        }
        if ($config->allowErrorLog && isset($config->errorLog)){
            $errorLog=strtolower($config->errorLog);      //log error level
        } else {
            $errorLog=strtolower($config->defaultErrorLog);      //log error level
            
        }
        $logDir=$config->logDir;
        if($config->allowDebugTrace){
            $debugTrace=$config->debugTrace;   //set yes for debug trace
        }else{
            $debugTrace=FALSE;
        }
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
		$firephp = \FirePHP::getInstance(true);
		if (error_reporting() == 0) {
			$printstr="Supressed @$printstr";
		}
		if(strpos($errmsg,"TRACE")!==false){
			//do firebug trace if TRACE in user error message
       		$firephp->trace($printstr);
        }else{			
			$firephp->log($printstr);
			if ($debugTrace==TRUE && $myerrorlevel>3){
				if(isset($vars) && is_array($vars) ){
                 	$firephp->log($vars, 'Trapped Variable State');           	
            	}
                $firephp->log(debug_backtrace(), 'Back Trace');        
        	}
        	
        }
        
    }
    
        if(strpos($errmsg,"EXIT")!==false){
        	$terminate=true;
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
