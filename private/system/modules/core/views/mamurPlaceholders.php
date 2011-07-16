<?php
class mamurPlaceholders {

	private $model;
	private $view;
	
	public function __construct($model,$view){	
		$this->model=$model;
		$this->view=$view;
	}
	
	/**
	 * page_content tag inserts content associated with a page and stored in the page specific
	 * folder or table
	 * This content can contain tags so view->processPlaceholders is called
	 * on the content
	 * @param array  $param  - array of tag parameters
	 * @param string $tag    - tag name
	 * @return string $content -  content with all static tags replaced
	 */
	public function page_content($param){
		$content=$this->model->getPageContent($param['name']);
		if(!empty($content)){	 
            $content=$this->view->processPlaceholders($content);    
        }
        return $content;
	}
	
	/**
	 * shared tags inserts shared content of a particlar type
	 * as defined by the tag name ie
	 * template, structure', 'section' 'blog' 'menu' 'news' 'article'
	 * This content can contain tags so view->processPlaceholders is called
	 * on the content
	 * @param array  $param  - array of tag parameters
	 * @param string $tag    - tag name
	 * @return string $content -  content with all static tags replaced
	 */
     public function shared($param,$tag){
     	$type=$tag; //defaults to tag name
     	$group="";
     	if(isset($param['type'])){
     		$type=$param['type'];
     	}
        if(isset($param['group'])){
     		$group=$param['group'];
     	}
     	$mapped="";
     	if(isset($param['mapped'])){
     		$mapped=$param['mapped'];
     	}
		$content=$this->model->getSharedContent($param['name'],$type,$group,$mapped);
		if(!empty($content)){	 
            $content=$this->view->processPlaceholders($content);    
        }
        return $content;
	}
	
	
	
	 
	 public function all($param){
     	$contType='page';
        if(isset($param['type'])){
        	$contType=$param['type'];
        }
        $match='*';
        $separator='';
        if(isset($param['separator'])){
        	$separator=$param['separator'];
        }
      
        $contentList=$this->model->getContentList($contType,$match);
        
                      
        $sep='';
        $content="";
        if(!empty($contentList))foreach($contentList as $contentListItem){
                          // $file=$sep.file_get_contents($file);
                          // $file=$this->view->processPlaceholders($file);
                          // $file.=$file;
                $param['name']=$contentListItem;
        		if($contType=='page'){
        			$content=$sep.$this->page_content($param);
        		}else{
        			$content=$sep.$this->shared($param,$contType);
        		}
                         
                $sep=$separator;
         }
         return($content);
	 }
	
	
	
	public function mamur(){
		 return  $this->model->getMamurUrl();
	}
	
	public function  title(){
        $title='';
        if(isset($this->view->templateTags['title']['title']['value'])){
        	$title=$this->view->templateTags['title']['title']['value'];
        }
        return $title;
	}
	
	public function http_header($param,$tag){
    	if(isset($param['value'])){
            $headerAction="header('{$param['value']}');";
        }elseif(isset($param['name']) && isset($this->view->templateTags[$tag][$param['name']])){
            $headerAction="header('{$this->view->templateTags[$tag][$param['name']]}');";
        }
        return "<?php $headerAction ?>";       
	}
	

    public function globalTag($param){
    	$parms=serialize($param);
    	$ret='';
        if(isset($param['name'])){
        	$ret="<?php \$this->doGlobalPlaceholder('$parms'); ?>";
        }
    	return $ret;
                    
    }
    
	public function  random($param){
		$parms=serialize($param);
		return "<?php \$this->doRandomPlaceholder('$parms'); ?>";
	}
	
    public function  unique_serial(){
		return "<?php \$this->doUniqueSerialPlaceholder(); ?>";
	}
	
                    
	public function  build_random($param){
    	$length=6;
        $upperonly=false;
        if(isset($param['length'])){
           $length=$param['length'];
        }
        if(isset($param['upper_only'])){
        	$length=true;
        }
        return $this->model->getRandomString($length,$upperonly);                 
	}



    public function page_timer(){
    	return "<?php \$this->dopage_pageTimerPlaceholder(); ?>";
    }
    
 	public function page_timerms(){
    	return "<?php \$this->dopage_timermsPlaceholder(); ?>";
    }

    public function  date($param){
		$parms=serialize($param);
    	return "<?php \$this->doDatePlaceholder($parms); ?>";
    }
	
   
    public function php($param){
    	$ret="";
    	$parms=serialize($param);
        if(isset($param['name'])){
        	$ret="<?php \$this->doPhpPlaceholder('$parms'); ?>";
        }
    	return $ret;
    	
    }
    
    public function nonce($param){
    	$parms=serialize($param);
    	print "<?php \$this->doNoncePlaceholder('$parms'); ?>";
    }
  
    
    public function tag($param){
    	$parms=serialize($param);
        if(isset($param['name'])){
        	$ret="<?php \$this->doTagPlaceholder('$parms'); ?>";
        }
    	return $ret;               
    }
    
     
   	public function page_selected($param){
   		$selected="";
        $match=true;
        if(isset($param['name'])){
			if( $param['name'] != $this->model->getPageName() ){
 				$match=false;
			}
     	}
        if(isset($param['dir'])){
        	if(substr($param['dir'],0,1)=='/' ){
                                 $param['dir']=substr($param['dir'],1);
            }
            $urlDirList=$this->model->getPageDirList();
            $dirList=explode('/',$param['dir']);
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
        if(isset($param['url_part'])){
        	$fileUrl=$this->model->getPageDir().'/'.$this->model->getPageName().'.'.$this->model->getPageExt();
            if(substr($param['url_part'],0,1)=='/'){
            	if(substr($fileUrl,0,1)!='/' ){
                	$fileUrl='/'.$fileUrl;
               }
            }else{
            	if(substr($fileUrl,0,1)=='/'){
                	$fileUrl=substr($fileUrl,1);
                }
            }
            if(strlen($param['url_part'])<=strlen($fileUrl)){
            	$fileUrl=substr($fileUrl,0,strlen($param['url_part']));
                if( $param['url_part'] != $fileUrl){
                	$match=false;
                }
            }else{
            	$match=false;
            }
        }

        if($match){
        	$selected="_selected";
     	}         
      	return $selected;
    }
    
    
    
	function generalPlaceholder($param,$tag){
		  
      switch($tag){
         
     
       

        case 'data':
                        $name="default";
                        $table="default";
                        $row=0;
                        $name="default";
                        if(isset($param['name'])){
                           $name=$param['name'];
                        }
                        if(isset($param['table'])){
                           $table=$param['table'];
                        }
                        if(isset($param['row'])){
                           $row=$param['row'];
                        }
                        $dataSet=$this->model->getDataSet($name);
                        if(isset($param['index'])){
                            $index=$param['index'];
                        }
                        if(isset($dataSet['table'][$table][$row][$name])){
                           $toprint=$dataSet['table'][$table][$row][$name];
                           $toprint=$this->view->processPlaceholders($toprint);
                          return $toprint;
                        }
                        break;

     

         case 'other_css_files':
                         if(!isset($param['name'])){
                            if(isset($this->view->templateTags['css'] )){
                              foreach( $this->view->templateTags['css'] as $tName=>$tFields){
                                 if(isset($tFields['file'])){
                                    return "<link href=\"{$tFields['file']}\" rel=\"stylesheet\" type=\"text/css\" />\n";
                                 }
                              }
                            }
                        }else{
                           if(isset($this->view->templateTags['css'][$param['name']]['file'])){
                                $file=$this->view->templateTags['css'][$param['name']]['file'];
                                if(isset($file)){
                                  return "<link href=\"{$file}\" rel=\"stylesheet\" type=\"text/css\" />\n";
                                }
                           }
                        }
                        break;


         case 'other_js_files':
                        if(!isset($param['name'])){
                            if(isset($this->view->templateTags['javascript'] )){
                              foreach( $this->view->templateTags['javascript'] as $tName=>$tFields){
                                 if(isset($tFields['file'])){
                                   return "<script type=\"text/javascript\" src=\"{$tFields['file']}\"></script>\n";
                                 }
                              }
                            }
                        }else{
                           if(isset($this->view->templateTags['javascript'][$param['name']]['file'])){
                                $file=$this->view->templateTags['javascript'][$param['name']]['file'];
                                if(isset($file)){
                                   return "<script type=\"text/javascript\" src=\"{$file}\"></script>\n";
                                }
                           }
                        }

                        break;

       

         case 'other_meta':
                      if(!isset($param['name'])){
                            if(isset($this->view->templateTags['meta'] )){
                              foreach( $this->view->templateTags['meta'] as $metaName=>$metaFields){
                                 return $this->view->metaContentStr($metaName,$metaFields);
                              }
                            }
                      }else{
                           if(isset($this->view->templateTags['meta'][$param['name']]['value'])){
                              return $this->view->metaContentStr($param['name'],$this->templateTags['meta'][$param['name']]);
                           }
                      }
                      break;

         case 'get':
                      if(isset($param['name'])){
                         if(isset($_GET[$param['name']])){
                          return htmlspecialchars($_GET[$param['name']]);
                         }
                      }else{
                         foreach($_GET as $param=>$val){
                             return $param=htmlspecialchars($val).'<br />';
                         }

                      }
                      break;

         case 'post':
                      if(isset($param['name'])){
                        if(isset($_POST[$param['name']])){
                          return htmlspecialchars($_POST[$param['name']]);
                        }
                      }else{
                         foreach($_POST as $param=>$val){
                             return $param=htmlspecialchars($val).'<br />';
                         }

                      }
                      break;

         case 'request':
                      if(isset($param['name'])){
                         if(isset($_REQUEST[$param['name']])){
                          return htmlspecialchars($_REQUEST[$param['name']]);
                         }
                      }else{
                         foreach($_REQUEST as $param=>$val){
                             return $param=htmlspecialchars($val).'<br />';
                         }
                      }

                      break;

           case 'cookie':
                      if(isset($param['name'])){
                        if(isset($_GET[$param['cookie']])){
                          return htmlspecialchars($_COOKIE[$param['name']]);
                        }
                      }else{
                         foreach($_COOKIE as $param=>$val){
                             return $param=htmlspecialchars($val).'<br />';
                         }
                      }
                      break;

         

          case 'odd_even':
                     $rowVar="default";
                     if(isset($param['name'])){
                        $rowVar=$param['name'];
                     }
                     if(!isset($this->oddeven[$rowVar])){
                        $this->oddeven[$rowVar]='even';
                     }
                     if($this->oddeven[$rowVar]=='even'){
                        $this->oddeven[$rowVar]='odd';
                     }else{
                        $this->oddeven[$rowVar]='even';
                     }
                     if(isset($param['set'])){
                        $this->oddeven[$rowVar]=$param['set'];
                     }
                     return $this->oddeven[$rowVar];
                     break;

       

          case 'option':
                     $name=0;
                     if(isset($param['name'])){
                        $name=$param['name'];
                     }
                     return $this->model->getOption($name);
                     break;

          case 'protected':
                    $pass=false;
                    $user=$this->model->getUser();

                    if (isset($user['loggedin']) && $user['loggedin']==true  ){
                        $pass=true;

                        if(isset($param['allow_group'])){
                            if( $param['allow_group'] != $user['group'] ){
                                $pass=false;
                            }
                        }
                        if(isset($param['disallow_group'])){
                            if( $param['disallow_group'] == $user['group'] ){
                                $pass=false;
                            }
                        }
                         if(isset($param['allow_status'])){
                            if( $param['allow_status'] != $user['status'] ){
                                $pass=false;
                            }
                         }
                         if(isset($param['disallow_status'])){
                            if( $param['disallow_status'] == $user['status'] ){
                                $pass=false;
                            }
                        }
                        if(isset($param['allow_status_and_above'])){
                            if( $user['status'] < $param['allow_status_and_above']  ){
                                $pass=false;
                            }
                        }
                        if(isset($param['allow_status_name'])){
                            if( $user['statusName'] != $param['allow_status_name']  ){
                                $pass=false;
                            }
                        }
                        if(isset($param['disallow_status_name'])){
                            if( $user['statusName'] == $param['disallow_status_name']  ){
                                $pass=false;
                            }
                        }

                    }
                    if(!$pass){
                       if(isset($param['login_page'])){
                           $redirect= $param['login_page'];
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
                     if(isset($param['ref'])){
                       $ref=$param['ref'];
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
                    $page=$this->model->getPageName();
                    $ext=$this->model->getPageExt();
                    if($ext!=''){
                      $page.='.'.$ext;
                    }
                   return $page;
                    break;

           case 'page_name':
                    return $page=$this->model->getPageName();
                    break;

           case 'page_ext':
                   return $this->model->getPageExt();
                    break;

           case 'page_dir':
                    return $this->model->getPageDir();
                    break;

          

         

         default:
         		//try form tags
         	   
              

       }
		
	}
	
}