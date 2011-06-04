<?php
/******************************************************************************
@version: 1.04;
@name: view;
@type: main;
                          View Classes
 Mamur Content Server; for Dynamic Serving of Web Pages using Templates
  Version: 1.04  Copyright (c) 2010 Sygenius Ltd  Date: 05/02/2011

  1st Released on tag: 104

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
 This is the view class. It is responsible for output to the client (browser)
 This usually involves the model passing a print request and tag data


 Important:   Requires php 5.2 and .htaccess enabled


 Install:     This file private directory/mamur_page_server/


 Author:      Martin Marsh.


 Author:      Martin Marsh.

 File Version: 104  compatible with versions V1.02+
 History:     Detailed history - Major Events
 100   09/12/2010 - First alpha version - based on Sygenius Software used with permission
 102   31/12/2010 - First beta candidate for trial in building live web sites
 103   10/01/2011 - Revised redirect to work with external urls, home and page_ tags,
                    php content files cannot have tags inside
 103   29/01/2011 - =& removed, Quoted parameters now allowed tagnames must have
                    alphanumeric names with _ or - allowed
 104   10/04/2011 - custom parameters in php tag, dir for page mapping parameter in content tags

*******************************************************************************/


 abstract class mamurAbstractPageView {
 /* This prime purpose of this class is to hide data and functions from plugins
    and inlcuded user PHP code.
    i.e. helps prevent name conflicts etc.
 */

    protected $mamur;
    protected $model;
  // protected $control;
    protected $templateTags;
    protected $urlDir;
    protected $pageOutput;
    protected $contentPageBase;
    protected $oddeven;



    //class constructor
    public function  __construct() {
       $this->oddeven=array();;
    }

    //function setController(&$control){
    //   $this->control=$control;
   // }

    public function setModel(&$model){
          $this->model=$model;
          $this->mamur=$model;    //alias for model (allows php inserts to access
                                    //model using $this->mamur (same as plugins)
    }


    public function insertTag($tag,$pairs){
       $tag=strtolower($tag);

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
       $processTag=true;
       //trap a Named tag takes priority and can either trap or pre-process
       foreach($this->model->getNamedTagCallBack($tag) as $callBack){
                     $processTag=$callBack['ref']->$callBack['func']($tag,$var,$isTagNamed,$hasTagFile);

       }
       if($processTag) switch($tag){
         case 'page_content':
                        $base=$this->contentPageBase;
                        //a page.xml WITH FILE=X overrideS A NAMED TAG location set in a template WHEN $hasTagFile IS TRUE
                        if($hasTagFile && isset($var['name'])){
                            $file=$this->model->relativeDir($base,$this->templateTags[$tag][$var['name']]['file']);
                        }elseif(isset($var['file'])){
                           $file=$this->model->relativeDir($base,$var['file']);
                        }elseif(isset($var['name'])){
                           $file=$this->model->relativeDir($base,$var['name'].'.html');
                        }
                        if(file_exists($file)){
                            $file=file_get_contents($file);
                             $this->processTags($file);
                            $this->doPhpAndPrint($file);
                        }
                        break;

         case 'mamur':
                        print  $file=$this->model->getMamurUrl();
                        break;

         case 'title':
                        $title='';
                        if(isset($this->templateTags['title']['title']['value'])){
                            $title=$this->templateTags['title']['title']['value'];
                        }
                        print $title;
                        break;

         case 'http_header':
                        if(isset($var['value'])){
                           header($var['value']);
                        }elseif(isset($var['name']) && isset($this->templateTags[$tag][$var['name']])){
                           header($this->templateTags[$tag][$var['name']]);
                        }

                        break;
       case 'global':
                        if(isset($var['name'])){
                             print $this->model->getGlobal($var['name']);
                        }else{
                             print "[/global tag must have a name! /]";
                        }
                        break;

       case 'shared':
                         $tag='article';
                         if(isset($var['type'])){
                            $tag=$var['type'];
                         }
       case 'structure':
       case 'section':
       case 'blog':
       case 'menu':
       case 'news':
       case 'article':
                         $base=$this->model->getSharedContentBase("/{$tag}");
                       //a page.xml WITH FILE=X overrideS A NAMED TAG location set in a template WHEN $hasTagFile IS TRUE
                         if($hasTagFile && isset($var['name'])){
                            $file=$this->model->relativeDir($base,$this->templateTags[$tag][$var['name']]['file']);
                         }elseif(isset($var['name'])){
                            $file=$this->model->relativeDir($base,$var['name'].'.html');
                         }elseif(isset($var['file'])){
                            //if template tag has a file location
                            $file=$this->model->relativeDir($base,$var['file']);
                            //if mapped is set then mapped = map_directory/default1/default2...
                            //usually only one default is set the content is located in
                            //shared/type/map_directory with name given as the directory or default.html
                         }elseif(isset($var['dir'])){
                         	//page name is matched with content
                         	$file=$this->model->relativeDir($base.$var['dir'],$this->model->getPageName().'.html');
                         	if(!file_exists($file)){
                         		$file=$this->model->relativeDir($base.$var['dir'],'default.html');
                         	}
       					 }elseif(isset($var['mapped'])){
                            if(substr($var['mapped'],0,1)=='/' ){
                                    $var['mapped']=substr($var['mapped'],1);
                            }
                            $urlDirList=$this->model->getPageDirList();
                            $mapList=explode('/',$var['mapped']);
                            $targetDir=array_shift($mapList);
                            $targetName=array_pop($mapList);
                            $targetDir=$this->model->relativeDir($base,$targetDir);
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
                                       $file=$verify;
                               }else{
                                       $file=$targetDir.='/unknown.html';
                               }
                            }else{
                                $file=$targetDir.='/'.$targetName.'.html';
                            }

                         }

                         if(file_exists($file)){
                                $file=file_get_contents($file);
                                 $this->processTags($file);
                                $this->doPhpAndPrint($file);
                         }else{
                                print "[/no shared_content file: {$file} /]";
                         }
                       break;


        case 'all':
                        $contType='page';
                        if(isset($var['type'])){
                           $contType=$var['type'];
                        }
                        $match='*.html';
                        $separator='';
                        if(isset($var['separator'])){
                           $separator=$var['separator'];
                        }
                        if($contType=='page'){
                            $base=$this->contentPageBase;
                        }else{
                             $base=$this->model->getSharedContentBase("/{$contType}");
                        }
                        if(isset($var['match'])){
                            if(substr($match,0,1)=='/')$match= substr($match,1);
                        }
                        $filelist=glob($this->model->relativeDir($base,$this->templateTags[$tag][$var['name']]['file'].'/'.$match));
                        $sep='';
                        if(!empty($filelist))foreach($filelist as $file){
                           $file=$sep.file_get_contents($file);
                           $this->processTags($file);
                           $this->doPhpAndPrint($file);
                           $sep=$separator;
                        }
                        break;

        case 'data':
                        $name="default";
                        $table="default";
                        $row=0;
                        $name="default";
                        if(isset($var['name'])){
                           $name=$var['name'];
                        }
                        if(isset($var['table'])){
                           $table=$var['table'];
                        }
                        if(isset($var['row'])){
                           $row=$var['row'];
                        }
                        $dataSet=$this->mamur->getDataSet($name);
                        if(isset($var['index'])){
                            $index=$var['index'];
                        }
                        if(isset($dataSet['table'][$table][$row][$name])){
                           $toprint=$dataSet['table'][$table][$row][$name];
                           $this->processTags($toprint);
                           $this->doPhpAndPrint($toprint);
                        }
                        break;

        case 'nonce':
                        $length=16;
                        if(isset($var['length'])){
                          $length=$var['length'];
                        }
                        $useVar='default';
                        if(isset($var['name'])){
                           $useVar=$var['name'];
                        }
                        print $this->mamur->setNonce($useVar,$length);
                        break;

         case 'other_css_files':
                         if(!isset($var['name'])){
                            if(isset($this->templateTags['css'] )){
                              foreach( $this->templateTags['css'] as $tName=>$tFields){
                                 if(isset($tFields['file'])){
                                    print "<link href=\"{$tFields['file']}\" rel=\"stylesheet\" type=\"text/css\" />\n";
                                 }
                              }
                            }
                        }else{
                           if(isset($this->templateTags['css'][$var['name']]['file'])){
                                $file=$this->templateTags['css'][$var['name']]['file'];
                                if(isset($file)){
                                  print "<link href=\"{$file}\" rel=\"stylesheet\" type=\"text/css\" />\n";
                                }
                           }
                        }
                        break;


         case 'other_js_files':
                        if(!isset($var['name'])){
                            if(isset($this->templateTags['javascript'] )){
                              foreach( $this->templateTags['javascript'] as $tName=>$tFields){
                                 if(isset($tFields['file'])){
                                   print "<script type=\"text/javascript\" src=\"{$tFields['file']}\"></script>\n";
                                 }
                              }
                            }
                        }else{
                           if(isset($this->templateTags['javascript'][$var['name']]['file'])){
                                $file=$this->templateTags['javascript'][$var['name']]['file'];
                                if(isset($file)){
                                   print "<script type=\"text/javascript\" src=\"{$file}\"></script>\n";
                                }
                           }
                        }

                        break;

         case 'php':
                         $base=$this->model->getPhpBase();
                         //a page.xml overrideS A NAMED TAG WHEN $hasTagFile IS TRUE
                         if($hasTagFile && isset($var['name'])){
                            $file=$this->model->relativeDir($base,$this->templateTags[$tag][$var['name']]['file']);
                         }elseif(isset($var['file'])){
                            $file=$this->model->relativeDir($base,$var['file']);
                         }elseif(isset($var['name'])){
                            $file=$this->model->relativeDir($base,$var['name'].'.php');
                         }
                         if(file_exists($file)){
                                $file=file_get_contents($file);
                                $this->mamur->passParameters($var);
                                $this->doPhpAndPrint($file);
                         }else{
                                print "[no script file: {$file} /]";
                         }

                       break;

         case 'other_meta':
                      if(!isset($var['name'])){
                            if(isset($this->templateTags['meta'] )){
                              foreach( $this->templateTags['meta'] as $metaName=>$metaFields){
                                 print $this->metaContentStr($metaName,$metaFields);
                              }
                            }
                      }else{
                           if(isset($this->templateTags['meta'][$var['name']]['value'])){
                              print $this->metaContentStr($var['name'],$this->templateTags['meta'][$var['name']]);
                           }
                      }
                      break;

         case 'get':
                      if(isset($var['name'])){
                         if(isset($_GET[$var['name']])){
                          print htmlspecialchars($_GET[$var['name']]);
                         }
                      }else{
                         foreach($_GET as $var=>$val){
                             print $var=htmlspecialchars($val).'<br />';
                         }

                      }
                      break;

         case 'post':
                      if(isset($var['name'])){
                        if(isset($_POST[$var['name']])){
                          print htmlspecialchars($_POST[$var['name']]);
                        }
                      }else{
                         foreach($_POST as $var=>$val){
                             print $var=htmlspecialchars($val).'<br />';
                         }

                      }
                      break;

         case 'request':
                      if(isset($var['name'])){
                         if(isset($_REQUEST[$var['name']])){
                          print htmlspecialchars($_REQUEST[$var['name']]);
                         }
                      }else{
                         foreach($_REQUEST as $var=>$val){
                             print $var=htmlspecialchars($val).'<br />';
                         }
                      }

                      break;

           case 'cookie':
                      if(isset($var['name'])){
                        if(isset($_GET[$var['cookie']])){
                          print htmlspecialchars($_COOKIE[$var['name']]);
                        }
                      }else{
                         foreach($_COOKIE as $var=>$val){
                             print $var=htmlspecialchars($val).'<br />';
                         }
                      }
                      break;

           case 'page_timer':
                    $pagetime=$this->model->pageTime(true);
                    if($pagetime!==false){
                        print "<br>page Time {$pagetime} ms<br>";
                        
                      }
                    break;

           case 'page_timerms':
                    print $this->model->pageTime();
                               $timer=array();
                    print "<BR>".(intval(($GLOBALS['mamurPageConfig']['start_config']- mamurConfig::$config['time_start'])*10000)/10)."ms <br>";           
                    print "<BR>".(intval(($GLOBALS['mamurPageConfig']['end_config']- $GLOBALS['mamurPageConfig']['start_config'])*10000)/10)."ms <br>";
 
           
             
                    break;

           case 'page_selected':
                    $match=true;
                    if(isset($var['name'])){
                        if( $var['name'] != $this->model->getPageFile() ){
                            $match=false;
                        }
                    }
                    if(isset($var['dir'])){
                       if(substr($var['dir'],0,1)=='/' ){
                                 $var['dir']=substr($var['dir'],1);
                       }
                       $urlDirList=$this->model->getPageDirList();
                       $dirList=explode('/',$var['dir']);
                       $i=0;
                       foreach($dirList as $dir){
                          if(isset($urlDirList[$i])){
                            $verify=$urlDirList[$i++];
                            if($verify!=$dir){
                              $match=false;
                              break;
                            }
                          }elseif($dir!=''){
                              $match=false;
                              break;
                          }

                       }
                    }
                    if(isset($var['url_part'])){
                       $fileUrl=$this->model->getPageDir().'/'.$this->model->getPageFile().'.'.$this->model->getPageExt();
                       if(substr($var['url_part'],0,1)=='/'){
                            if(substr($fileUrl,0,1)!='/' ){
                                 $fileUrl='/'.$fileUrl;
                            }
                       }else{
                            if(substr($fileUrl,0,1)=='/'){
                                 $fileUrl=substr($fileUrl,1);
                            }
                       }
                       if(strlen($var['url_part'])<=strlen($fileUrl)){
                         $fileUrl=substr($fileUrl,0,strlen($var['url_part']));
                          if( $var['url_part'] != $fileUrl){
                              $match=false;
                          }
                       }else{
                           $match=false;
                       }
                    }

                    if($match){
                       print "_selected";
                    }
                    break;



          case 'odd_even':
                     $rowVar="default";
                     if(isset($var['name'])){
                        $rowVar=$var['name'];
                     }
                     if(!isset($this->oddeven[$rowVar])){
                        $this->oddeven[$rowVar]='even';
                     }
                     if($this->oddeven[$rowVar]=='even'){
                        $this->oddeven[$rowVar]='odd';
                     }else{
                        $this->oddeven[$rowVar]='even';
                     }
                     if(isset($var['set'])){
                        $this->oddeven[$rowVar]=$var['set'];
                     }
                     print $this->oddeven[$rowVar];
                     break;

          case 'tag':
                     $tagName='tag';
                     $index=0;
                     if(isset($var['name'])){
                        $tagName=$var['name'];
                     }
                     if(isset($var['index'])){
                        $index=$var['index'];
                     }
                     $toprint=$this->model->getTag($tagName,$index);
                     $this->processTags($toprint);
                     $this->doPhpAndPrint($toprint);
                     break;

          case 'option':
                     $name=0;
                     if(isset($var['name'])){
                        $name=$var['name'];
                     }
                     print $this->model->getOption($name);
                     break;

          case 'protected':
                    $pass=false;
                    $user=$this->model->getUser();

                    if (isset($user['loggedin']) && $user['loggedin']==true  ){
                        $pass=true;

                        if(isset($var['allow_group'])){
                            if( $var['allow_group'] != $user['group'] ){
                                $pass=false;
                            }
                        }
                        if(isset($var['disallow_group'])){
                            if( $var['disallow_group'] == $user['group'] ){
                                $pass=false;
                            }
                        }
                         if(isset($var['allow_status'])){
                            if( $var['allow_status'] != $user['status'] ){
                                $pass=false;
                            }
                         }
                         if(isset($var['disallow_status'])){
                            if( $var['disallow_status'] == $user['status'] ){
                                $pass=false;
                            }
                        }
                        if(isset($var['allow_status_and_above'])){
                            if( $user['status'] < $var['allow_status_and_above']  ){
                                $pass=false;
                            }
                        }
                        if(isset($var['allow_status_name'])){
                            if( $user['statusName'] != $var['allow_status_name']  ){
                                $pass=false;
                            }
                        }
                        if(isset($var['disallow_status_name'])){
                            if( $user['statusName'] == $var['disallow_status_name']  ){
                                $pass=false;
                            }
                        }

                    }
                    if(!$pass){
                       if(isset($var['login_page'])){
                           $redirect= $var['login_page'];
                       }else{
                           $redirect=$this->model->getConfigValue('loginPage');
                           if(is_null($redirect)){
                               $redirect="login.html";
                           }
                       }
                       $this->redirect($redirect);
                    }
                    break;

           case 'home':
                     $ref='';
                     if(isset($var['ref'])){
                       $ref=$var['ref'];
                       if($ref!='' && substr($ref,0,1)!='/'){
                          $ref='/'.$ref;
                       }
                     }
                    print  $this->model->getHomeUri().$ref;
                    break;

           case 'page_url':
                    print $this->model->getUrl();
                    break;

           case 'page_name_ext':
                    $page=$this->model->getPageFile();
                    $ext=$this->model->getPageExt();
                    if($ext!=''){
                      $page.='.'.$ext;
                    }
                    print $page;
                    break;

           case 'page_name':
                    print $page=$this->model->getPageFile();
                    break;

           case 'page_ext':
                    print $this->model->getPageExt();
                    break;

           case 'page_dir':
                    print $this->model->getPageDir();
                    break;

           case 'unique_serial':
                    print $this->model->unique_serial();
                    break;

           case 'random':
                    $length=6;
                    $upperonly=false;
                    if(isset($var['length'])){
                       $length=$var['length'];
                    }
                    if(isset($var['upper_only'])){
                       $length=true;
                    }
                    print $this->model->getRandomString($length,$upperonly);
                    break;

           case 'date':
                    $format="r";
                    $dateSrc="now";
                    if(isset($var['format'])){
                       $format=$var['format'];
                    }
                    if(isset($var['when'])){
                       $dateSrc=$var['when'];
                    }
                    $dateTime = new DateTime($dateSrc);
                    print $dateTime->format($format);
                    break;


         default:
         		//try form tags
         	    if(mamurClassLoader("system","core","views","formTags")){
         	    	$formTags=new formTags($this->model,$this);
         	    	$formTags->tags($tag,$var);
         	    	
         	    }
                foreach($this->model->getTagCallBack() as $callBack){
                     if($callBack['ref']->$callBack['func']($tag,$var)==true) break;
                }

       }

    }

   protected function metaContentStr($metaName,$metaFields){
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

           $this->templateTags=$this->model->getTagData();
           $this->contentPageBase=$this->model->getContentBase();
           $templateFile=$this->model->getTemplateFile();
           $this->pageOutput=file_get_contents($templateFile);

           $this->processTags($this->pageOutput);
           $this->doPhpAndPrint($this->pageOutput);
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
/* end of abstract class */

//This is not neat but this call back cannot work in class I think this is
//a php limitation - but it works and does not really cause a problem
//Warning this outside of the class in global function space.
//This is a much repeated constant function and needs to be more efficient thsn
//create_function
function mamur_view_replaceTag($matches){

  GLOBAL $mamurPageView;

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
            ob_start();
            $mamurPageView->insertTag($tagname,$pairs);
            $out= ob_get_contents();
            ob_end_clean();
        }
        return $out;

}

