<?php
class mamurTags {

	private $model;
	private $view;
	
	public function __construct($model,$view){	
		$this->model=$model;
		$this->view=$view;
	}
	
	/**
	 * 
	 * @param $var
	 * @param $hasTagFile
	 * @return unknown_type
	 */
	public function page_content($var,$hasTagFile){
		$base=$this->view->contentPageBase;
        //a page.xml WITH FILE=X overrideS A NAMED TAG location set in a template WHEN $hasTagFile IS TRUE
        if($hasTagFile && isset($var['name'])){
                            $file=$this->model->relativeDir($base,$this->view->templateTags[$tag][$var['name']]['file']);
        }elseif(isset($var['file'])){
                           $file=$this->model->relativeDir($base,$var['file']);
        }elseif(isset($var['name'])){
                           $file=$this->model->relativeDir($base,$var['name'].'.html');
        }
        if(file_exists($file)){
             $file=file_get_contents($file);
             $this->view->processTags($file);
             return $file;
           
        }
	}
	
	
	function generalPlaceholder($var,$hasTagFile,$tag){
		  
      switch($tag){
         
         case 'mamur':
                        return  $this->model->getMamurUrl();
                        break;

         case 'title':
                        $title='';
                        if(isset($this->view->templateTags['title']['title']['value'])){
                            $title=$this->view->templateTags['title']['title']['value'];
                        }
                        return $title;
                        break;

         case 'http_header':
                        if(isset($var['value'])){
                           header($var['value']);
                        }elseif(isset($var['name']) && isset($this->view->templateTags[$tag][$var['name']])){
                           header($this->view->templateTags[$tag][$var['name']]);
                        }

                        break;
       case 'global':
                        if(isset($var['name'])){
                             return $this->model->getGlobal($var['name']);
                        }else{
                             return "[/global tag must have a name! /]";
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
                                $this->view->processTags($file);
                                return($file);
                               // $this->view->doPhpAndPrint($file);
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
                           $this->view->processTags($file);
                           $file.=$file;
                           $sep=$separator;
                        }
                        return($file);
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
                        $dataSet=$this->model->getDataSet($name);
                        if(isset($var['index'])){
                            $index=$var['index'];
                        }
                        if(isset($dataSet['table'][$table][$row][$name])){
                           $toprint=$dataSet['table'][$table][$row][$name];
                           $this->view->processTags($toprint);
                          return $toprint;
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
                        return $this->model->setNonce($useVar,$length);
                        break;

         case 'other_css_files':
                         if(!isset($var['name'])){
                            if(isset($this->view->templateTags['css'] )){
                              foreach( $this->view->templateTags['css'] as $tName=>$tFields){
                                 if(isset($tFields['file'])){
                                    return "<link href=\"{$tFields['file']}\" rel=\"stylesheet\" type=\"text/css\" />\n";
                                 }
                              }
                            }
                        }else{
                           if(isset($this->view->templateTags['css'][$var['name']]['file'])){
                                $file=$this->view->templateTags['css'][$var['name']]['file'];
                                if(isset($file)){
                                  return "<link href=\"{$file}\" rel=\"stylesheet\" type=\"text/css\" />\n";
                                }
                           }
                        }
                        break;


         case 'other_js_files':
                        if(!isset($var['name'])){
                            if(isset($this->view->templateTags['javascript'] )){
                              foreach( $this->view->templateTags['javascript'] as $tName=>$tFields){
                                 if(isset($tFields['file'])){
                                   return "<script type=\"text/javascript\" src=\"{$tFields['file']}\"></script>\n";
                                 }
                              }
                            }
                        }else{
                           if(isset($this->view->templateTags['javascript'][$var['name']]['file'])){
                                $file=$this->view->templateTags['javascript'][$var['name']]['file'];
                                if(isset($file)){
                                   return "<script type=\"text/javascript\" src=\"{$file}\"></script>\n";
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
                                $this->model->passParameters($var);
                                $this->view->doPhpAndPrint($file);
                         }else{
                                print "[no script file: {$file} /]";
                         }

                       break;

         case 'other_meta':
                      if(!isset($var['name'])){
                            if(isset($this->view->templateTags['meta'] )){
                              foreach( $this->view->templateTags['meta'] as $metaName=>$metaFields){
                                 return $this->view->metaContentStr($metaName,$metaFields);
                              }
                            }
                      }else{
                           if(isset($this->view->templateTags['meta'][$var['name']]['value'])){
                              return $this->view->metaContentStr($var['name'],$this->templateTags['meta'][$var['name']]);
                           }
                      }
                      break;

         case 'get':
                      if(isset($var['name'])){
                         if(isset($_GET[$var['name']])){
                          return htmlspecialchars($_GET[$var['name']]);
                         }
                      }else{
                         foreach($_GET as $var=>$val){
                             return $var=htmlspecialchars($val).'<br />';
                         }

                      }
                      break;

         case 'post':
                      if(isset($var['name'])){
                        if(isset($_POST[$var['name']])){
                          return htmlspecialchars($_POST[$var['name']]);
                        }
                      }else{
                         foreach($_POST as $var=>$val){
                             return $var=htmlspecialchars($val).'<br />';
                         }

                      }
                      break;

         case 'request':
                      if(isset($var['name'])){
                         if(isset($_REQUEST[$var['name']])){
                          return htmlspecialchars($_REQUEST[$var['name']]);
                         }
                      }else{
                         foreach($_REQUEST as $var=>$val){
                             return $var=htmlspecialchars($val).'<br />';
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
                        return "<br>page Time {$pagetime} ms<br>";
                        
                      }
                    break;

           case 'page_timerms':
                    return $this->model->pageTime();
                               $timer=array();
                   // print "<BR>".(intval(($GLOBALS['mamurPageConfig']['start_config']- mamurConfig::$config['time_start'])*10000)/10)."ms <br>";           
                   // print "<BR>".(intval(($GLOBALS['mamurPageConfig']['end_config']- $GLOBALS['mamurPageConfig']['start_config'])*10000)/10)."ms <br>";
 
           
             
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
                     return $this->oddeven[$rowVar];
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
                     $this->view->processTags($toprint);
                     //$this->view->doPhpAndPrint($toprint);
                    return $toprint;
                     break;

          case 'option':
                     $name=0;
                     if(isset($var['name'])){
                        $name=$var['name'];
                     }
                     return $this->model->getOption($name);
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
                    return  $this->model->getHomeUri().$ref;
                    break;

           case 'page_url':
                    return $this->model->getUrl();
                    break;

           case 'page_name_ext':
                    $page=$this->model->getPageFile();
                    $ext=$this->model->getPageExt();
                    if($ext!=''){
                      $page.='.'.$ext;
                    }
                   return $page;
                    break;

           case 'page_name':
                    return $page=$this->model->getPageFile();
                    break;

           case 'page_ext':
                   return $this->model->getPageExt();
                    break;

           case 'page_dir':
                    return $this->model->getPageDir();
                    break;

           case 'unique_serial':
                    return $this->model->unique_serial();
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
                    return $this->model->getRandomString($length,$upperonly);
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
                    return $dateTime->format($format);
                    break;


         default:
         		//try form tags
         	   
              

       }
		
	}
	
}