<?php
/**
 * This file contains the static main view Class - mamurView
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
 * @name mamurView
 * @package mamur
 * @subpackage core
 * @version 110
 * @mvc view
 * @release Mamur 1.10
 * @releasetag 110
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2011,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3                  
 *  					          
 */ 

/**
 * mamurView is a basic class to print out pages
 * @package mamur
 * @subpackage core
 */

 class mamurView {
 /* This prime purpose of this class is to hide data and functions from plugins
    and inlcuded user PHP code.
    i.e. helps prevent name conflicts etc.
 */

    protected $mamur;
    protected $model;
     public $templateTags;
    protected $urlDir;
    protected $pageOutput;
    public  $contentPageBase;
    protected $oddeven;
    protected $placeHolderClasses;
    protected $pageBuild;
    protected $pagePhp;
    protected $pageBuildId;



    //class constructor
    public function  __construct() {
       $this->oddeven=array();
       $this->placeHolderClasses=array();
       $this->pageBuild=array();
       $this->pageBuildId=0;
       $this->pagePhp='';
    }

    //function setController(&$control){
    //   $this->control=$control;
   // }

    public function setModel(&$model){
          $this->model=$model;
          $this->mamur=$model;    //alias for model (allows php inserts to access
                                    //model using $this->mamur (same as plugins)
                                    
    }
    
   //A modified singleton method for 5.2 since static class cannot be called by variable
    public function getPlaceholder($class) {
        if (!isset($this->placeHolderClasses[$class])) { 
           $this->placeHolderClasses[$class]= new $class($this->model,$this);
        }
        return $this->placeHolderClasses[$class];
    }


    public function insertTag($tag,$pairs){
       $tag=strtolower($tag);
       $config=mamurConfig::getInstance();

       //insert Tag executes for each tag and occurs for main page when
       //doPhpAndPrint is called.
       //Pairs are a string of name=data type settings
       //escape the data inserted into quotes in php insertTag call
       $pairs=stripslashes($pairs);  //remove escaped quotes etc
       //extract variable list pairs separated by a space or quoted with " or '
       $var=array();
       $loop=false;
       $eq=0;
       $start=0;
       do{
          $eq=strpos($pairs,"=");
          if($eq===false){
            $loop=false;
          }else{
            $loop=true;
            $varname=substr($pairs,0,$eq);
            $eq++;
            $pairs=ltrim(substr($pairs,$eq));
            $sep=substr($pairs,0,1);
            if($sep=='"' || $sep=="'"){
              $pairs=substr($pairs,1);
            }else{
               $sep=' ';
            }
            $endVar=strpos($pairs,$sep);
            if($endVar===false){
               $var[$varname]=$pairs;

            }else{
               $var[$varname]=substr($pairs,0,$endVar);
               $pairs=ltrim(substr($pairs,$endVar+1));
            }
            $eq+=strlen($var[$varname]);
          }

       }while($loop);


       if(isset($var['name']) && isset($var['file'])  ){
         //if there is tag with a name and file set and then overwrite template file or
         //create one
          $this->templateTags[$tag][$var['name']]['file']=$var['file'];
       }
       if(isset($var['name']) && isset($var['value'])  ){
         //if there is tag with a name and file set and then overwrite template file or
         //create one
          $this->templateTags[$tag][$var['name']]['value']=$var['value'];
       }
       $isTagNamed=false;
       if(isset($var['name']) && isset($this->templateTags[$tag][$var['name']])){
            $isTagNamed=true;
       }
       $hasTagFile=false;
       if($isTagNamed && isset($this->templateTags[$tag][$var['name']]['file'])){
            $hasTagFile=true;
       }
     
       $tagObj=$config->placeholders->$tag;
       
       if(is_object($tagObj)){
       		$tagInstance=$this->getPlaceholder($tagObj->class); 
       		$tagMethod=$tagObj->method;
       		$tagDefaultMethod=$tagObj->default;
       
       	   if($tagMethod!==false && method_exists ( $tagInstance , $tagMethod )){
	       		$data=$tagInstance->$tagMethod($var,$hasTagFile,$tag);
	       }elseif(method_exists ( $tagInstance , $tag)){
	       	    $data=$tagInstance->$tag($var,$hasTagFile,$tag);
	       }elseif($tagDefaultMethod!==false && method_exists ( $tagInstance , $tagDefaultMethod )){
	       	    $data=$tagInstance->$tagDefaultMethod($var,$hasTagFile,$tag);
	       }
         
         
       	   $this->pageBuild[$this->pageBuildId]['tag']=$tag;
       	   $this->pageBuild[$this->pageBuildId]['var']=$var;
       	   $id=$this->pageBuildId++;
       }else{
       		//Undefined Tag 
       		@trigger_error("TRACE unknow tag '$tag' found");
         
       }
       return "$data"; 
    }

 function metaContentStr($metaName,$metaFields){
        $ret='';
        $content='';
        $others='';
        $name='';
        $name='name="'.$metaName.'" ';
        if(isset($metaFields['content'])){
           $content=$metaFields['content'];

        }
        foreach($metaFields as $field=>$val){
             switch($field){
                case 'content':
                case 'name':
                    break;
                case 'value':
                    $content.=$val;
                    break;
                case 'http-equiv':
                    $name="$field=\"$val\" ";
                    break;
                default:
                    $others.="$field=\"$val\" ";
                    break;
             }

        }

        $ret= "<meta $name content=\"{$content}\" $others />\n";
        return $ret;
   }

   public function templatedView(){
        if($this->model->doPagePagePrintCallBacks()){
            if($this->model->getXmlPageType()){
                //is an xml templated file so print it
                $this->printTemplatePage();
            }else{
                //not an xml templated file so must be an not found file
                $this->model->setErrorPageTemplate();
                if($this->model->getXmlPageType()){
                     //an error page template is found so print it
                     //printing an error page sets 404 header
                     $this->printTemplatePage();
                }else{
                     //an error page template not found so print a default
                     //error page
                     $this->defaultErrorPage();
                }
            }
       }

   }

   public function directPhpView(){
            $base=$this->model->getPhpBase();
            $file=$this->model->getPageDir().'/'.$this->model->getPageFile().'.php';
            $file=$this->model->relativeDir($base,$file);
            if(file_exists($file)){
               $file=file_get_contents($file);
               $this->doPhpAndPrint($file);

            }else{
               header("HTTP/1.1 404 Not Found");
               print "Error: No PHP at $file";
            }

   }


   public function printTemplatePage(){
           //this prints a templated page - must only call if processed ok
           if($this->model->isError404Page()){
              header("HTTP/1.1 404 Not Found");
           }
 		   $config=mamurConfig::getInstance();
           $this->templateTags=$this->model->getTagData();
           $this->contentPageBase=$this->model->getContentBase();
           $templateFile=$this->model->getTemplateFile();
           $this->pageOutput=file_get_contents($templateFile);
            $this->processTags($this->pageOutput);
          // $this->pagePhp=" \nprint  $this->pageBuild[$this->pageBuildId]['data'];\n";  
          // $this->pageBuild[$this->pageBuildId]['data']=$this->pageOutput;
       	   $this->pageBuild[$this->pageBuildId]['tag']="pagetemplate";
       	   $this->pageBuild[$this->pageBuildId]['var']=array('file'=>$templateFile);
       	  
       	   //print_r($this->pageBuild);
       	   $dataArray=serialize($this->pageBuild);
       	   $page=" \$mamurRawData=<<<herdoc123xyz\n$dataArray\n\nherdoc123xyz;\n\n";
       	   $page.="\$data=unserialize(\$mamurRawData); ?>";
       	   $page.=$this->pageOutput;
       //print $page;	  
     //eval($page);
       //	print_r($this->pageBuild);
          if($config->settings->pageBuild=='yes'){
	       	  $dir=$config->settings->build.$this->model->getPageDir();
	       	  if(!is_dir($dir)){
	       	  	 mkdir($dir, 0777,true);
	       	  	
	       	  }
	       
	       	  file_put_contents($dir.'/'.$this->model->getPageName().".php",
	       	    	$this->pageOutput);
          }
       	    	
       	  $this->doPhpAndPrint($this->pageOutput);
       	   
       	  
          $this->pageBuildId++; 
   }

   public function showBuiltPage($builtFile){
   		include_once($builtFile);
   }
    public function includePageFile(){
         //this includes a file (php or html content ect)
         //which is know to exist - This would return a non-templated file
         //and might occur if there is no _page.xml but there is a standard html
         //page. Templated and non-templated can co-exist in same directory
         if($this->model->isError404Page()){
              header("HTTP/1.1 404 Not Found");
         }
         $file=$this->model->getNonMamurPage();
         include_once($file);
    }

    public function defaultErrorPage(){
          //this prints a fall back error page when there is no
          //errornotfound_page.xml or errornotfound.html file
          header("HTTP/1.1 404 Not Found");
          print "
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
 \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<!-- note this file must not be too small or Google tools (if installed) will show
their error page instead -->
<title>Page not found - 404 Error</title>
</head>
<body>
<h1>Sorry, Page not found</h1>

<p>The Page you requested has either moved or is currently unavailable.
</p>
<p>Please try again later, use your browser back button or <a href='/'>click here for home page</a></p>
</body>
</html>";

          exit();
    }

    public function redirect($redirect=''){

        if($this->model->getConfigValue('allowSessionCookie')=='yes' ){
           $this->model->setSessionCookie();
           $this->model->saveDataSets();
        }

        $protcol="http";
        if(!empty($_SERVER['HTTPS'])){
            $protcol="https";
        }
        $host = $_SERVER['HTTP_HOST'];

        if(empty($redirect)){
             header("Location: $protcol://$host/");
             exit();
        }

        //if redirect contains a host and scheme then redirect
        $url=parse_url ($redirect);
        if(!empty($url['host'])){
            if(!empty($url['scheme'])){
                header("Location: ".$redirect);
                exit();
            }else{
              //assume http if another site otherwise same
                if($host==$url['host']){
                  header("Location:  $protcol://$redirect");
                  exit();
                }else{
                  header("Location: http://$redirect");
                  exit();
                }
            }
        }
        // host and scheme are empty
        if(substr($redirect,0,1)!=='/')$redirect="/".$redirect;
        header("Location: $protcol://$host$redirect");
        exit();
    }


    public function doPhpAndPrint(&$input){
        eval('?>'.$input);

    }



    //this is a bit messy since our call back has to call out of class.
    public function  processTags(&$textData){

      /*   Tags are between <?mamur: :?> are invisible if the content is viewed
           in an html editor and will passes W3D validation if used in templates
           there is a slight risk of issues with some servers setups which might miss
           process <?mamaur as illegal php after a short tag
           A short printable version [?mamur: :?] or shorter format [?: :?] is ideal in editable content as it is
           visible to the editor
      */

      /* These lines would allow php inside a tag -not really a good idea.
        ob_start();
        $this->doPhpAndPrint($textData);
        $textData= ob_get_contents();
        ob_end_clean();  */
    	
        $textData= preg_replace_callback('@(<\?mamur:([a-zA-Z0-9_\-]*)(.*?)(?=(:\?>)):\?>)|(\[\?(mamur)?:([a-zA-Z0-9_\-]*)(.*?)(?=(:\?\])):\?\])@smx',
                      'mamur_view_replaceTag',$textData);

     }

}
/* end of class */


/**
 * Warning this is a call back function and NOT a class method.
 * This is not neat but is a v5.2+ work around for not having Anonymous functions
 * which would be used in the above class
 * @param $matches
 * @return unknown_type
 */
function mamur_view_replaceTag($matches){

         $pairs='';
         $tagname='';
         $out='';
         if(isset($matches[9]) && $matches[9]==":?]"){
             $tagname=$matches[7];
             $pairs=trim($matches[8]);
         }elseif($matches[4]==":?>"){
             $tagname=$matches[2];
             $pairs=trim($matches[3]);
         }
         if($tagname!=''){
            //ob_start();
            $out= mamurController::getView()->insertTag($tagname,$pairs);
            //$out= ob_get_contents();
            //ob_end_clean();
        }
        return $out;

}

