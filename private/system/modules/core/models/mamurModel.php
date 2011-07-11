<?php 
/**
 * This file contains the main model Class - mamurModel
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
 * @name mamurModel
 * @package mamur
 * @subpackage core
 * @version 110
 * @mvc model
 * @release Mamur 1.10
 * @releasetag 110
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2011,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3
 *                   
 *  					          
 */ 


/**
 * mamurModel is a basic class to access all module related data and files
 * @package mamur
 * @subpackage core
 */

class mamurModel {
   // protected $control;
    //protected $view;
    protected $webBaseDir,$pageURL,$mamurURL;
    protected $xmlPageType,$phpParameters;
    protected $defaultPageName,$defaultPageExt,$pageQuery,$pageName,$pageDir,$pageDirList,$pageExt;
    protected $themesDir;
    protected $templateTags;
    protected $error404PageName;
    protected $lastErrorPage;
    protected $countSerial;
    protected $hostScheme,$subdomain,$hostdomain,$topdomain;
    protected $locid,$locidAccepted;
    protected $global;
    protected $mamurPluginDir,$mamurBaseDir,$mamurlogDir,$mamurSystemDir;
    protected $urlCallBack,$pageProcessCallBack,$pagePagePrintCallBack,$sessionClearCallBack,
              $serverBaseRequestCallBack;
    protected $dataObjects,$session,$inSession,$oldSession,$logOutFlag,$tags,$options;
	


	   
/**
 * Constructor sets up properties according to configuration
 * On first install Sets apiID and Salt
 * Manages cookies (if enabled), decrypt stored session cookies, 
 * logouts and set up user session data
 * @return void
 */ 
    

public function   __construct(){
	   $config=mamurConfig::getInstance();
	   $set=$config->settings;

       $this->defaultPageName=$set->homePage;
       $this->defaultPageExt=$set->pageExt;
       $this->webBaseDir=$set->root;      //Base directory of web site

       //note plugin dir  set by remote call
       $this->mamurBaseDir=$set->mamur;
       $this->mamurURL=str_replace($this->webBaseDir,'', $this->mamurBaseDir);
       if(DIRECTORY_SEPARATOR=='\\'){
        $this->mamurURL=str_replace(DIRECTORY_SEPARATOR ,'/', $this->mamurURL );
       }
       $this->mamurlogDir=$set->logDir;
       $this->mamurSystemDir=$set->system;
       $this->logOutFlag=false;
       $this->mamurUserDir=$set->user;
       $this->xmlPageType=false;
       $this->error404PageName="errornotfound";
       $this->countSerial=0;
       $this->setHostData($set->host);
       $this->global=array();
       $this->urlCallBack=array();
       $this->pageProcessCallBack=array();
       $this->pagePagePrintCallBack=array();
       $this->sessionClearCallBack=array();
       $this->sessionLogoutCallBack=array();
       $this->serverBaseRequestCallBack=array();
       $this->dataObjects=null;
       $this->tags=array();
       $this->options=array();
       $this->phpParameters=array();
     

       $update=false;
       if($set->salt=='new'){
          $set->salt=$this->getRandomString(117);
          $config->persistSetting('salt',$set->salt);
          $update=true;
       }
       if($set->apiId=='new'){
       	   $set->apiId=$this->unique_serial();
       	   $config->persistSetting('apiId',$set->apiId);
           $update=true;
       }

       if($update){
           $config->upDateConfig();
       }
       $this->session=array();
       $this->inSession=false;
       if(isset($_COOKIE['session'])){
            $this->inSession=true;
            $this->session=$this->decrypt($_COOKIE['session'],63,19);
            $this->oldSession=$this->session;
       }
       if(!isset($this->session['verify']) ){
           $this->session['verify']=$this->getRandomString(12);
       } 
       
       if(!isset($this->session['id'])){
           $this->session['id']=$this->unique_serial().$this->getRandomString(8);
       }
       
    	
    	if(!isset($this->session['user'])){
           $this->session['user']['name']='unknown';
           $this->session['user']['id']='';
           $this->session['user']['loggedin']=false;
           $this->session['user']['status']=0;
           $this->session['user']['statusName']='unknown';
           $this->session['user']['group']='unknown';
           $this->session['user']['time']=time();
       //log out if time out exceeded - reset timer and cookie every 1/3 rd of time out
       //period when (logged in) Note logOutFlag is set so that log out
       //can be canccelled by a plugin (note plugins have not loaded yet)

       }elseif($this->session['user']['loggedin'] &&
           time()-$this->session['user']['time'] > $set->loginTimeOut ){
           $this->logOutFlag=true;
           $this->session['user']['time']=time();
       }elseif($this->session['user']['loggedin'] &&
           time()-$this->session['user']['time'] >  $set->loginTimeOut/3 ){
           $this->session['user']['time']=time();
       }
       if(!isset($this->session['page'])){
           $this->session['page']['edit']=false;
       }
      // print_r($this->session);
    }

	/**
	 * Checks to see if logout required.
	 * A hook function can modify this method and extend login period
	 * @return void
	 */
    public function checkLogOut(){
       if($this->logOutFlag){
          $continue=true;
          foreach($this->sessionLogoutCallBack   as $callBack){
             $continue=$continue && $callBack['ref']->$callBack['func']($this->session['user']);
         }
         if($continue){
            $this->session['user']['loggedin']=false;
         }else{
            $this->session['user']['time']=time();
         }
       }
       $this->logOutFlag=false;
    }

    /**
     * Saves php parameters defined by a php tag
     * @param $var
     * @return void
     */
    public function passParameters($var){
       $this->phpParameters=$var;
    }
    
    /**
     * Gets php pass parameters defined by a php tag
     * @return phpParamters Array
     */
    public function getParameters(){
    	return $this->phpParameters;
    }
  
	/**
	 * Saves an option
	 * @param $value - of option
	 * @param $name - of option
	 * @return void
	 */

     public function setOption($value,$name=0){
          $this->options[$name]=$value;
     }

     /**
      * Gets a named option
      * @param $name
      * @return option value
      */
     public function getOption($name=0){
        $ret='';
        if(isset($this->options[$name])) $ret=$this->options[$name];
        return $ret;
     }

     /**
      * Sets a Tag
      * @param $value
      * @param $name
      * @param $index
      * @return void
      */
     public function setTag($value,$name='tag',$index=0){
          $this->tags[$name][$index]=$value;
     }

     /**
      * Sets an array (list) of tags
      * @param $listName
      * @param $name
      * @return unknown_type
      */
     public function setTagList($listName,$name='tag'){
         foreach($listName as $var=>$val){
            $this->tags[$name][$var]=$val;
         }
     }
     
     /**
      * Gets a tag value
      * @param $name
      * @param $index
      * @return unknown_type
      */
     public function getTag($name='tag',$index=0){
        $ret='';
        if(isset($this->tags[$name][$index])) $ret=$this->tags[$name][$index];
        return $ret;
     }
     
 //xxxx    
     /**
      * Places a directory list to a dataObject
      * @param $dSet
      * @param $listArray
      * @param $datasetName
      * @param $table
      * @return unknown_type
      */

     public function dirListToDataSet(&$dSet,$listArray,$dataObjectName,$table='default'){
       //  $dSet['table'][$table]=array();
         $row=&$dSet['table'][$table];
         $field=array();
         foreach($listArray as $file){
                $field['name']=basename($file,'.html');
                $row[]=$field;
         }
         $this->setDataSet($datasetName,$dSet);
     }


     /**
      * recurcive remove directory - protected as it is dangerous.
      * @param $dir
      * @return unknown_type
      */
     protected function rrmdir($dir) {
         if (is_dir($dir)) {
           $objects = scandir($dir);
           foreach ($objects as $object) {
             if ($object != "." && $object != "..") {
               if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
             }
           }
           reset($objects);
           rmdir($dir);
        }
     }

     /**
      * Removes a page
      * @param $page
      * @return unknown_type
      */
     public function removePage($page){
          $pageDir=$this->getMamurUserDir()."/pages{$page}_html";
          $this->rrmdir($pageDir);
     }

     /**
      * Gets page processing lapsed time
      * @param $check
      * @return page time in ms
      */
     public function pageTime($check=false){
        $ret=false;
        $set=mamurConfig::getInstance()->settings;
        if(!$check || $set->pageTime=='on'){
            $timer=array();
            list($timer['lusec'], $timer['lsec']) = explode(" ", microtime());
            $set->time_end = ((float)$timer['lusec'] + (float)$timer['lsec']);
            $ret=(intval(( $set->time_end - $set->time_start)*10000)/10);
        }
        return $ret;
    }
    
	/**
	 * Saves configustaion settings by Updating  the xml file
	 * @return void
	 */
    public function upDateConfigFile(){
      mamurConfig::upDateConfigFile();
    }

    /**
     * Sets page uri and associated internal varaibles using the value of the url parameter passed.
     * the URI would normally be from the web server ie the current page.
     * @param $uri
     * @return unknown_type
     */
    public function setPageUri($uri){
        $this->pageURL=$uri;
        $checkREQUEST_URI= strip_tags(urldecode($uri));
        $parmpos=strpos($checkREQUEST_URI,"?");
        if($parmpos!==false){
            $this->pageQuery=substr($checkREQUEST_URI,$parmpos);
            $checkREQUEST_URI=substr($checkREQUEST_URI,0,$parmpos);
        }

        if( substr($checkREQUEST_URI,-1)=='/'){
             $this->pageName=$this->defaultPageName;
             $this->pageExt='.'.$this->defaultPageExt;
             $this->pageDir=substr($checkREQUEST_URI,0,-1);

        }else{
            $this->pageName=basename($checkREQUEST_URI);
            $this->pageExt= strrchr ( $this->pageName, '.' );
            $this->pageDir=dirname($checkREQUEST_URI);
        }


        if($this->pageName=='' ){
            $this->pageName=$this->defaultPageName;
        }
        if($this->pageExt==''){
                $this->pageExt='.'.$this->defaultPageExt;
        }
        $this->pageName=basename($this->pageName,$this->pageExt);
        $this->pageExt=substr($this->pageExt,1);

        $this->pageDir=str_replace('\\','/',$this->pageDir);


        $this->pageDirlist=array();
        foreach( explode('/',$this->pageDir) as $sys_dirx){
            if($sys_dirx!=''){
                    $this->pageDirlist[]=$sys_dirx;
            }
        }
        if($this->pageDir=='/'){
            $this->pageDir='';
        }
        //now process page url plugins which can re-map urls
        foreach($this->urlCallBack as $callBack){
                $callBack['ref']->$callBack['func']($this->pageDir,$this->pageName,$this->pageExt,$this->pageDirlist);
        }
    }
    
    /**
     * Sets Current User deatils
     * @param $name
     * @param $id
     * @param $loggedin
     * @param $status
     * @param $group
     * @param $statusName
     * @return unknown_type
     */
    public function setUser($name='unknown',$id='',$loggedin=false,$status=0,$group='unknown',$statusName='unknown' ){
           $this->session['user']['name']=$name;
           $this->session['user']['id']=$id;
           $this->session['user']['loggedin']=$loggedin;
           $this->session['user']['status']=$status;
           $this->session['user']['statusName']=$statusName;
           $this->session['user']['group']=$group;
           $this->session['user']['time']=time();
    }

    /**
     * Gets current user session data
     * @return unknown_type
     */
    public function getUser(){
        return $this->session['user'];
    }

    /**
     * Logs in current user
     * @param $status
     * @param $statusName
     * @return unknown_type
     */
    public function userLogIn($status=0,$statusName='unknown'){
         $this->session['user']['loggedin']=true;
         if($status!=''){
              $this->session['user']['status']=$status;
              $this->session['user']['statusName']=$statusName;
              $this->session['user']['time']=time();
         }
         $this->sessionReVerify();
    }

   

    /*
     * sessionReVerify
     * As with all systems stealing a cookie gives you access so we cannot
     * protect against systems which can steal and write cookies on demand.
     * Hosting a rogue javascript could provide such a security breach.
     *
     * We use an encryted cookie to stored login data so stealing a cookie prior
     * to login is no use. There is inherent protection from rogue web sites which pass
     * a pre-obtained cookies to a user.
     * The verification code gives another level of protection should the cookie
     * encrytion be hacked so that cookies can be made up and passed to a user.
     * So that the user was not alerted to the problem by being automatically logged
     * in the attacker could pass a made up session id and logged out status.
     * Optionally, a secure system can check stored dataset sessions to see if
     * the verification code matches that in the cookie. This code is also changed
     * at log in so this attack would fail. Most systems probably do not need this
     * extra protection ie other attack routes would probably be easier than breaking
     * the encyption.
     * 
     */
    public function sessionReVerify(){
           $this->session['verify']=$this->getRandomString(12);
    }

    /**
     * Checks if user logged in
     * @param $id
     * @param $name
     * @param $status
     * @param $group
     * @param $statusName
     * @return unknown_type
     */
    public function isLoggedIn($id='',$name='',$status='',$group='',$statusName=''){
        $in= $this->session['user']['loggedin'];
        if($id!=''){
          $in=$in && $id==$this->session['user']['id'];
        }
        if($name!=''){
          $in=$in && $name==$this->session['user']['name'];
        }
        if($statusName!=''){
          $in=$in && $statusName==$this->session['user']['statusName'];
        }
        if($status!=''){
          $in=$in && $status==$this->session['user']['status'];
        }
         if($group!=''){
          $in=$in && $group==$this->session['user']['group'];
        }
        return $in;
    }

    /**
     * Logs out a user
     * @param $status
     * @return unknown_type
     */
    public function userLogOut($status=''){
         $this->session['user']['loggedin']=false;
         if($status!=''){
              $this->session['user']['status']=$status;
         }
    }

    /**
     * get page edit status
     * @param $status
     * @return void
     */
    public function getEditStatus($status=false){
        return $this->session['page']['edit'];
    }
    
    /**
     * Sets page edit status
     * @param $status
     * @return void
     */
    public function setEditStatus($status=false){
         $this->session['page']['edit']=$status;
    }
    
    
    /**
     * 
     * Looks at the a domain name (normally the host for current server)
     * and sets model attributes realating to various parts of the domain
     * name
     * @param $host - domain name
     * @return void
     */

    public function setHostData($host){
            $hostparts=explode('.',str_replace('www.','',strtolower($host)));

            $this->hostScheme='http';

            if(isset($_SERVER['HTTPS'])
				&& ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] === true)){
	                $this->hostScheme='https';
            }
            switch (count($hostparts)){
                case 2:
                        $this->topdomain=$hostparts[1];
                case 1: $this->hostdomain=$hostparts[0];
                    break;
                case 3:
                        if($hostparts[2]=='uk'){
                                $this->topdomain=$hostparts[1].".".$hostparts[2];
                                $this->hostdomain=$hostparts[0];
                        }else{

                                $this->subdomain=$hostparts[0];
                                $this->hostdomain=$hostparts[1];
                                $this->topdomain=$hostparts[2];
                        }
                        break;
                case 4:
                        $this->subdomain=$hostparts[0];
                        $this->hostdomain=$hostparts[1];
                        $this->topdomain=$hostparts[2].".".$hostparts[3];
            }
    }

   /**
    * 
    * Gets a 32 byte salt by concatinating two 16 byte strings from 
    * the configuration salt string. Each installation will have a unique
    * salt and unless re-insatlled the salt will always remain the same
    * Allowing it to be used for encryption of long lived resources.
    * To allow different salts to be defined there is an option to give
    * two integers are supplied to identify the salt required.
    * This method also makes it les obvoius to see the salts by looking at the
    * configuration file
    * @param integer $a  - value from 0 to 99 (optional defaults to 84)
    * @param integer $b  - value from 0 to 99 (optional defaults to 11)
    * @return 32byte salt string
    */
    protected function getSalt($a=84,$b=11){
          return  substr(mamurConfig::$config['salt'],$a,16).substr(mamurConfig::$config['salt'],$b,16);
    }

    /**
     * 
     * Gets an Api salt in a similar way to getSalt
     * @return 16byte salt for use with Api
     */
    public function getApiSalt(){
       return  substr(mamurConfig::$config['salt'],7,10).substr(mamurConfig::$config['salt'],93,6);
    }

	/**
	 * 
	 * Decytpt a String using rijndael-256 and optionally
	 * 2 security integers to identify the salt to use
	 * @param string $value - base64 encoded 256bit encypted data
	 * @param integer $a
	 * @param integer $b
	 * @return Decypted string
	 */
    public function decrypt($value,$a=54,$b=16){
        $dataArray=array();
        if($value!=''){
          $encryptdata=unserialize(base64_decode($value));
          if(is_array($encryptdata)&& isset($encryptdata[1]) ){
              $salt=$this->getSalt($a,$b);
              $data=mcrypt_decrypt ('rijndael-256', $salt,
                                      $encryptdata[0], 'cbc',$encryptdata[1]);
              if(substr($data,0,1)=='a'){
                 $dataArray=unserialize($data);
              }
          }
        }
        return $dataArray;
    }

    /**
	 * 
	 * encrypt a String using rijndael-256 and optionally
	 * 2 security integers to identify the salt to use
	 * @param string $value 
	 * @param integer $a
	 * @param integer $b
	 * @return encrypted string base64_encoded
	 */  
    public function encrypt($dataArray,$a=54,$b=16){
        $td = mcrypt_module_open('rijndael-256', '', 'cbc', '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        $salt=$this->getSalt($a,$b);
        mcrypt_generic_init($td,$salt, $iv);
        $data[0] = mcrypt_generic($td, serialize($dataArray));
        $data[1] = $iv;
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return  base64_encode(serialize($data));
    }
    
    
    /**
     * 
     * Gets a random string of Letters and numbers of
     * specified length with option to return uppercase
     * only
     * @param integer $length
     * @param bool $upperonly
     */

    public function getRandomString($length,$upperonly=false){
        $string='';
        $min=1;
        $max=7;
        if($upperonly){
            $min=4;
            $max=10;
        }
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


    /**
     * 
     * Generates a cookie unique serial number
     */
    public function getCookieSerialNo(){
            $userip=$this->GetUserIP();
            $ipparts=explode(".",$userip);
            $iplast=array_pop($ipparts);
            $serialno=$this->unique_serial();
            foreach( $ipparts as $ippart){
               $serialno.=chr($ippart%26+65);
            }
            return $serialno.sprintf('%02X',$iplast);
    }

    /**
     * Gets best value for user IP address
     */
    public function GetUserIP(){
        $userip=$_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                 $userip= $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif(isset($_SERVER['HTTP_CLIENT_IP'])){
                 $userip=$_SERVER['HTTP_CLIENT_IP'];
        }
        return  $userip;
     }

    
     /**
      * 
      * generates a unique serial number based on date,microtime, call count and
      * a random number so there is very low risk of duplication in moderately
      * busy systems. 
      * The number may be used externally and vowels are mamped out to prevent
      * spelling of words which would look unprofessional
      */
    public function unique_serial(){
         $time=time();
         $this->countSerial++;
         $yearcode=chr((gmdate("Y",$time)-2006)%26+65);
         $randcode=chr(rand(65,90)).chr(rand(65,90)).chr(rand(65,90));
         list($usec, $sec) = explode(" ", microtime());
         $micro=chr(floor($usec*25.99)+65);
         $micro2=chr(65+floor(($usec*100)%4)*6 + $this->countSerial%6);
         $ret=str_pad(gmdate("zHis",$time), 9, "0", STR_PAD_LEFT).$micro.$micro2.$randcode;
         $vowels = array("A", "E", "I", "O", "U");
         $sub    = array("1", "2", "3", "4", "5");
         $ret = $yearcode.str_replace($vowels,$sub,$ret);
         return $ret;
    }

    /**
     * 
     * Sets a cookie to help identify a users at a location
     * Cookies will be set according to machine and account
     * This is approxiamte as acounts may be shared and users may use more
     * than 1 machine at a location. Each mobile device is assumed
     * to be a different "location"
     * 
     */
    public function setLocidCookie(){
        $alldomains='.';
        if( $this->hostdomain!=''){
            $alldomains.=$this->hostdomain;

        }
        if( $this->topdomain!=''){
            $alldomains.='.'.$this->topdomain;
        }
        $this->locid="L".$this->getCookieSerialNo();
        $this->locidAccepted=false;
        setcookie("locid", $this->locid, time()+315360000,"/", $alldomains,FALSE,TRUE);
    }

    /**
     * 
     * Sets an encrypted session based cookie which contains the
     * session data stored in $this->session array
     * Any pointers to session files are therefore encypted
     * The session cookie can be changed without affecting the
     * data
     */
     public function setSessionCookie(){
        $alldomains='.';
        if( $this->hostdomain!=''){
            $alldomains.=$this->hostdomain;

        }
        if( $this->topdomain!=''){
            $alldomains.='.'.$this->topdomain;
        }
        if($this->session!=$this->oldSession){
            $data=$this->encrypt($this->session,63,19);
            setcookie("session", $data, 0,"/", $alldomains,FALSE,TRUE);
        }
    }

    // called by controller to set locid when returned by a cookie
    public function confirmLocid($locid){
        $this->locid=$locid;
        $this->locidAccepted=true;

    }


    public function readPageXML(){
     //now process page Process url plugins which redirect page processing
      $xmlPagefile="{$this->getServerBase()}/pages{$this->pageDir}/{$this->pageName}_html/page.xml";
      if(file_exists($xmlPagefile)){
          $this->xmlPageType=true;
      }

      if($this->xmlPageType){
           $doc = new DOMDocument();
           $this->templateTags=array();
           if($this->processXML($xmlPagefile,$doc,$this->templateTags)){
              if(!isset($this->templateTags['template']['template']['file'])){
                $this->templateTags['template']['template']['file']="main_template.xml";
              }
              if(isset($this->templateTags['template']['template']['mapped'])){

                $mapped=$this->templateTags['template']['template']['mapped'];
                if(substr($mapped,0,1)=='/' ){
                                $mapped=substr($mapped,1);
                }
                $urlDirList=$this->getPageDirList();
                $mapList=explode('/',$mapped);
                $targetDir=array_shift($mapList);
                $targetName=array_pop($mapList);
                $i=0;
                //if multiple directories as in map=x/y/z/name  process y and z
                foreach($mapList as $dir){
                    if(isset($urlDirList[$i])){
                        $verify=$targetDir.'/'.$urlDirList[$i++];
                        if(file_exits($verify)){
                            $targetDir=$verify;
                        }else{
                            //assume default directory
                            $targetDir.='/'.$dir;
                        }
                    }elseif($dir!=''){
                        $targetDir.='/'.$dir;
                    }
                }
                //now the remaining url directory level if any maps to name or default
                if(isset($urlDirList[$i])){
                    $verify=$targetDir.'/'.$urlDirList[$i++].'.html';
                    if(file_exits($verify)){
                        $this->templateTags['template']['template']['file']=$verify;
                    }else{
                        $this->templateTags['template']['template']['file']=$targetDir.='/unknown.html';
                    }
                }else{
                    $this->templateTags['template']['template']['file']=$targetDir.='/'.$targetName.'.html';
                }


              }


           }else{
              print "XML page file error in {$xmlPagefile}";

           }

      }

    }

    //the server base is usually the user directory but it could be mapped
    //dynamically by a plugin for example admin pages - note database and
    //logs are not affected and cannot be remapped
    public function getServerBase(){
        $base=$this->mamurUserDir;

        foreach($this->serverBaseRequestCallBack as $callBack){
              $base=$callBack['ref']->$callBack['func']($base);
        }
        return $base;
    }

    public function isError404Page(){
         $error404Page=false;
         if($this->pageName==$this->error404PageName){
                     $error404Page=true;
         }
         return  $error404Page;
    }

    public function getUrl(){
          return  $this->pageURL;
    }

    public function getMamurUrl(){
        return  $this->mamurURL;
    }
    public function getWebRoot(){
          return  $this->webBaseDir;
    }


    public function getNonMamurPage(){
         return  "{$this->webBaseDir}{$this->pageDir}/{$this->pageName}.{$this->pageExt}";
    }

  
     public function getPageName(){
        return $this->pageName;
    }
    
    public function getPageExt(){
        return $this->pageExt;
    }
    public function getPageDir(){
        return $this->pageDir;
    }

    public function geterror404PageName(){
        return $this->error404PageName;
    }

    public function getTemplateFile($subdir=''){
        $subdir.=$this->templateTags['template']['template']['file'].'.html';
        return $this->relativeDir($this->getServerBase().'/templates',$subdir);
    }

    public function getContentBase($subdir=''){
        return $this->relativeDir("{$this->getServerBase()}/pages{$this->pageDir}/{$this->pageName}_html/",$subdir);
    }

    public function getPhpBase($subdir=''){
        return $this->relativeDir("{$this->getServerBase()}/php",$subdir);

    }

    public function getSharedContentBase($subdir=''){
       return $this->relativeDir("{$this->getServerBase()}/shared",$subdir);
    }

    public function getDataBaseDir($subdir=''){
       return $this->relativeDir("{$this->mamurUserDir}/databases",$subdir);
    }
    //note this is an unmapped call should use getServerDir for most calls
    //which allows maaping by a plugin
    public function getMamurUserDir($subdir=''){
         return $this->relativeDir($this->mamurUserDir,$subdir);
    }



    public function getMamurBaseDir($subdir=''){
          return $this->relativeDir($this->mamurBaseDir,$subdir);
    }

     public function getMamurlogDir($subdir=''){
         return $this->relativeDir($this->mamurlogDir,$subdir);
     }

     public function getMamurSystemDir($subdir=''){
         return $this->relativeDir($this->mamurSystemDir,$subdir);
     }

    public function getPluginDir($subdir=''){
       return $this->relativeDir($this->mamurPluginDir,$subdir);
    }
    
    /**
     * Gets Page Specific Content by name
     * @param string $name - of content eg main
     * @return string  - content
     */
    public function getPageContent($name){
        $base=$this->getContentBase();
        if(substr($name,-5)!='.html'){
        	$name.='.html';
        }
        $file=$this->relativeDir($base,$name);
        $content=null;
        if(file_exists($file)){
             $content=file_get_contents($file);
        }
        return $content;
    }


   /**
     * Gets Shared Content by name, type and by optional group and mapping
     * group use directory notation to add aditional classification to a type
     * of content eg articles could be divided by year and month eg the
     * group would be year/month
     * mapping allows the name to be expanded according to a uri or post value.
     * the name is appended by an additional map value separated by an underscore
     * mapping can be byFirstSection, byLastSection, byPage, byGet or byPost
     * section refers to the portion between the / in the uri eg
     * /firstsection/middlesection/lastsection/anypagename
     * if pagename is omitted index is assumed. The first and lastsections can be
     * the same or null if empty in which case just the name of the content without @author sygenius
	 * underscore separator is used.
	 * Post or Get variables use the same name as the content type eg ?article=1 
     * 
     * @param string - name of content eg main
     * @type string  - type of content eg article, blog, menu etc
     * @group string - optional; group
     * @mapped string - optional; mapping to different content files
     * @return string  - content
     */
    public function getSharedContent($name,$type,$group="",$mapped=""){
    	if(strlen($name>5)){
	    	if(substr($name,-5)!='.html'){
	        	$name=substr($name,0,-5);
	        }
    	}
        $base=$this->getSharedContentBase($type);
        if(!empty($mapped)){
        	//bysection assumed
            if($mapped=="byFirstSection"){
        		$section=array_shift($this->getPageDirList());
            }elseif($mapped=="byLastSection"){
            	$section=array_pop($this->getPageDirList());
            }elseif($mapped=="byPage"){
            	$section=$this->getPageName();
            	if($section==index){
            		$section="";
            	}
            }elseif($mapped=="byGet" && isset($_GET[$type]) ){
            	$section=htmlentities($_GET[$type]);
            }elseif($mapped=="byPost" && isset($_POST[$type]) ){
            	$section=htmlentities($_POST[$type]);
            }else{
            	//firePhp error report
            	@trigger_error("Unknown mapped request '$mapped' in content tag");
            	$section="_unknownmapping";
            }
        	if(!empty($section)){
        		$name.="_".$section;
        		
        	} 
        	  	
        }
        $fileBase=$this->relativeDir($base,$group);
     	
        $file=$this->relativeDir($fileBase,$name.'.html');
        $content=null;
        if(file_exists($file)){
             $content=file_get_contents($file);
        }else{
        	//firePhp error report
        	@trigger_error("$type Content tag refers to unknown content $name");
        }
        return $content;
    }
    
    /**
     * Gets a list of content names give a search pattern for a name
     * use '*' to match a sub string in the name part eg
     * * returns all files and n*x returns files starting with n
     * and ending in x
     * file extension .html is optional although generally
     * one should not be given
     * @param unknown_type $contType
     * @param unknown_type $match
     */
 	public function getContentList($contType,$match){
 		if(strlen($match>5)){
	    	if(substr($match,-5)!='.html'){
	        	$match=substr($match,0,-5);
	        }
    	}
	 	if($contType=='page'){
        	$base=$this->contentPageBase;
       	}else{
            $base=$this->model->getSharedContentBase("/{$contType}");
       	} 
	 	$filelist=glob($this->relativeDir($base,$match.".html"));
	 	
	 }
    
    /**
     * 
     * relativeDir extends a directory reference by adding and additional string
     * the additional string can be a subdirectory or page name.
     * The directory may end with an optional / character
     * The additional string may start with an optional / character
     * relative directory ensures that a / is added between and that // cannot occur
     * @param string directory reference
     * @param stringe additional directory or page name to append to directory reference
     */
     
    public function relativeDir($dir,$subdir){
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

    public function timeStampToUserDate($stamp,$format=DATE_ATOM){
      $date=gmdate(DATE_ATOM,$stamp);
      $UTCZ = new DateTimeZone('UTC');
      $userZ = new DateTimeZone(mamurConfig::$config['userTimeZone']);
      $dt = new DateTime($date,$UTCZ);
      $dt->setTimezone($userZ);
      $date=$dt->format($format);
      return $date;
    }

    public function getUserZoneOffset($date){
         $diff=0;
         if(mamurConfig::$config['serverTimeZone'] != mamurConfig::$config['userTimeZone']){
             $serverZ = new DateTimeZone(mamurConfig::$config['serverTimeZone']);
             $userZ = new DateTimeZone(mamurConfig::$config['userTimeZone']);
             $serverD=new DateTime($date, $serverZ);
             $userD=new DateTime($date, $userZ);
             $diff=($serverD->getOffset())-($userD->getOffset());
         }
         return $diff;
    }

    //gets user date converting from server zone to display in user zone
    //if date in is in DATE_RFC822, DATE_ATOM etc no conversion of time occurs just the Zone displayed
    public function convertToUserZone($date,$format=DATE_ATOM){
            $serverZ = new DateTimeZone(mamurConfig::$config['serverTimeZone']);
            $userZ = new DateTimeZone(mamurConfig::$config['userTimeZone']);
            $dt = new DateTime($date,  $serverZ);
            $dt->setTimezone($userZ);
            $date= $dt->format($format);
       return $date;
    }

    public function getTagData(){
        return $this->templateTags;
    }

    public function getGlobal($name){
    	$global=mamurConfig::getInstance()->globals;
        if(isset($global->$name)){
            return  $global->$name;
        }else{
            trigger_error("trying to get an unknown Global $name");
        }
    }

    public function setGlobal($name,$value){
          mamurConfig::setGlobal($name,$value);

    }

   public function pageProcessHookContinue(){
        $continue=true;
        foreach($this->pageProcessCallBack  as $callBack){
               $continue=$continue && $callBack['ref']->$callBack['func']($this->xmlPageType,$this->templateTags,$this->pageDir,$this->pageName,$this->pageExt,$this->pageDirlist);
        }
        return $continue;
    }

    public function  getXmlPageType(){
          return  $this->xmlPageType;
    }


    public function doPagePagePrintCallBacks(){
        $continue=true;
        foreach($this->pagePagePrintCallBack  as $callBack){
               $continue=$continue && $callBack['ref']->$callBack['func']($this->xmlPageType,$this->templateTags,$this->pageDir,$this->pageName,$this->pageExt,$this->pageDirlist);
        }
        return $continue;

    }

    public function setErrorPageTemplate(){
         $this->setPageUri("/{$this->error404PageName}.html");
         $this->readPageXML();

    }

    public function getErrorPage(){
        $ret='';
        if(!empty($this->lastErrorPage)){
            $ret= $this->lastErrorPage;
        }
        return  $ret;

    }
    
    public function openPageMeta($pageRelUrl){
    	$ret=null;
    	
      	if(mamurClassLoader('system','core','models','pageMeta')){
      		$ret=new pageMeta();
      	}

        return $ret;
    } 

     
    
      public function processXML($xmlPagefile,&$doc,&$pageTags){
      $doc->preserveWhiteSpace = false;
      $ok=$doc->load($xmlPagefile);
      if($ok){
        $thePage=$doc->firstChild;
        foreach($thePage->childNodes as $child){

            if(isset( $child->tagName)){
                $tagname=$child->tagName;
                             // print $pageTags;
                $name=$tagname;
                if($child->hasAttribute('name')){
                     $name=$child->getAttribute('name');
                }else{
                    if($child->hasAttribute('http-equiv')){
                         $name=$child->getAttribute('http-equiv');
                    }elseif($child->hasAttribute('id')){
                         $name=$child->getAttribute('id');

                    }
                }
               
                $pageTags[$tagname][$name]['value']=$child->nodeValue;
                foreach($child->attributes as $setvar=>$setval){
                  if( $setvar!='name'){
                    $pageTags[$tagname][$name][$setvar]=$setval->value;
                  }
                }

            }

        }

      }

      return $ok;
    }


    public function registerSessionClearFunction(&$ref,$function){
         $cback['ref']=$ref;
         $cback['func']=$function;
         $this->sessionClearCallBack[]=$cback;
    }

    public function registerUrlFunction(&$ref,$function){
         $cback['ref']=$ref;
         $cback['func']=$function;
         $this->urlCallBack[]=$cback;
    }

    public function registerPageProcessFunction(&$ref,$function){
         $cback['ref']=$ref;
         $cback['func']=$function;
         $this->pageProcessCallBack[]=$cback;
    }

    public function registerSessionLogoutFunction(&$ref,$function){
         $cback['ref']=$ref;
         $cback['func']=$function;
         $this->sessionLogoutCallBack[]=$cback;
    }

    public function registerPagePrintFunction(&$ref,$function){
         $cback['ref']=$ref;
         $cback['func']=$function;
         $this->pagePagePrintCallBack[]=$cback;
    }

    public function registerServerBaseFunction(&$ref,$function){
         $cback['ref']=$ref;
         $cback['func']=$function;
         $this->serverBaseRequestCallBack[]=$cback;
    }

    public function getTopPageDir(){
        $ret='';
        if(isset($this->pageDirlist[0])){
            $ret= $this->pageDirlist[0];
        }
        return   $ret;
    }

    public function getPageDirList(){
        $ret=array();
        if(isset($this->pageDirlist[0])){
            $ret= $this->pageDirlist;
        }
        return   $ret;

    }

    public function getSubDomain(){
          return   $this->subdomain;
    }

    public function getHostScheme(){
        return $this->hostScheme;
    }

    public function getHostDomain(){
          return  $this->hostdomain;
    }

    public function getTopDomain(){
          return  $this->topdomain;
    }

    public function getHomeUri(){
        return $this->hostScheme.'://'.$_SERVER["HTTP_HOST"];
    }

    public function setNonce($name='mamurData',$length=16){
    	$data=$this->getDataObject($name);
    	$nonce=$this->getRandomString($length);
        $data->mamurNonce=$nonce;
        return $nonce;
    }

    public function getNonce($name='mamurData'){
        return $this->getDataObject($name)->mamurNonce;
    }

    public function getDataObject($name){
        if(!is_array($this->dataObjects)){
            $this->readDataObjects();
        }
    	if(!isset($this->dataObjects[$name])){
    		$this->dataObjects[$name]=new mamurDataObject();
    	}
    	return $this->dataObjects[$name];
    }

    public function verifyDataSets(){
        if(!is_array($this->datasets)){
            $this->readDataSets();
        }
        if(isset($this->datasets['verify'])){
            return $this->datasets['verify']===$this->session['verify'];
        }else{
            return null;
        }
    }

    public function readDataObjects(){
        $file="{$this->mamurUserDir}/databases/mamur_datasets/{$this->session['id']}.txt";
        if(file_exists($file)){
           $this->datasets=unserialize(file_get_contents($file));
           if(is_array($this->datasets['data']) &&
                            count($this->datasets['data'])>0){
                $this->datasets['read']=true;
           }else{
                $this->datasets['read']=false;
           }
        }
    }

    public function setDataSet($name,$data){
        if(!is_array($this->datasets)){
            $this->readDataSets();
        }
        $this->datasets['data'][$name]=$data;
    }

    public function deleteDataSet($name){
        if(!is_array($this->datasets)){
            $this->readDataSets();
        }
        unset($this->datasets['data'][$name]);
    } 
    
    public function setDataSetField($fieldName,$value,$dataSetName='',$tableName='',$record=0){
    	if($dataSetName==''){
    		$dataSetName=$this->lastDataSetName;
    	}
    	$this->lastDataSetName=$dataSetName;
    	if($tableName==''){
    	    $tableName=$this->lastTableName;
    	}
    	$this->lastTableName=$tableName;
    	
    	$dataSet=$this->getDataSet($dataSetName);            
        $dataSet['table'][$tableName][$record][$fieldName]=$value;
        $this->setDataSet($dataSetName,$dataSet);
        
    }

    public function getDataSetField($fieldName,$dataSetName='',$tableName='',$record=0){
    	if($dataSetName==''){
    		$dataSetName=$this->lastDataSetName;
    	}
    	$this->lastDataSetName=$dataSetName;
    	if($tableName==''){
    	    $tableName=$this->lastTableName;
    	}
    	$this->lastTableName=$tableName;
    	$dataSet=$this->getDataSet($dataSetName);
    	
    	if(isset($dataSet['table'][$tableName][$record][$fieldName])){            
           $ret=$dataSet['table'][$tableName][$record][$fieldName];
    	}else{
    		$ret='';
    	}
        return $ret;
    }
    
    
    public function saveDataSets(){
        //only save if there is data to save or if a read found
        //data which may have been deleted
        if((is_array($this->datasets) &&
           is_array($this->datasets['data']) &&
           count($this->datasets['data'])>0
           )
           ||
           (isset($this->datasets['read']) &&
             $this->datasets['read']
           ) ){
            $this->datasets['verify']=$this->session['verify'];
            $this->datasets['time']=time();
            $dir="{$this->mamurUserDir}/databases/mamur_datasets";
            file_put_contents("{$dir}/{$this->session['id']}.txt",serialize($this->datasets));
            if(isset(mamurConfig::$config['sessionCleaned']) && time()-(mamurConfig::$config['sessionCleaned'])>mamurConfig::$config['sessionTimeOut']){
                 $donelist=array();
                 $d = dir($dir);
                 $count=0;  //number to processed
                 $scanCount=0; //number scanned
                 $processLimit=100; //number to process per page hit
                 while (false !== ($entry = $d->read()) && $count<$processLimit && $scanCount<10000) {
                    if(strlen($entry)>5 ){
                        $fullFile= $dir."/".$entry;
                        $status=stat($fullFile);
                        $timeOut=time()-$status['mtime'];
                        //print "<br>$entry expired by $timeOut s<BR>";
                        if( $timeOut>mamurConfig::$config['sessionTimeOut']){
                          //call back before unlink - allows log plugin to process logs or other
                          //plugins to save data eg shopping carts etc
                          //The plugin must return true unless it is desired to stop the delete to
                          //extend the session ie with a shopping cart
                          $canDelete=true;
                          foreach($this->sessionClearCallBack as $callBack){
                                $canDelete=$canDelete && $callBack['ref']->$callBack['func']($fullFile);
                          }
                          if($canDelete) unlink($dir."/".$entry);
                          ++$count;
                        }
                        ++$scanCount;
                    }
                }
                $d->close();
                if( $count<$processLimit){
                    $this->setConfig('sessionCleaned',time());
                    $this->upDateConfigFile();
                }
            }

        }
    }


 }
