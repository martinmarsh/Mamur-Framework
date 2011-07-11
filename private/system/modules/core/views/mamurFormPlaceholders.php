 <?php

class mamurFormPlaceholders{

      private $view,$dataSet,$dataSetName,$dataSetTable,$dataSetRow,$protection,$actionOverRide,
              $action,$cancel,$actionFunc,$cancelFunc,$request,$resubmit,$errorFormStr,
              $selectDataValue,$selectDataName,$inSelect;

      function __construct($setModel,$setView){
      	
         $this->mamur=$setModel;
         $this->model=$setModel;
         $this->view=$setView;

         $this->dataSet=array();
         $this->dataSetName='default';
         $this->dataSetTable='default';
         $this->dataSetRow=0;
         
         $this->protection='html';  //html is default using htmlspecialchars
         $this->request=&$_GET;
         $this->action='';
         $this->cancel='';
         $this->actionOverRide='';
         $this->actionFunction='';
         $this->cancelFunction='';
         $this->selectDataValue='';
         $this->selectDataName='';
         $this->inSelect=false;
         $this->resubmit='<h3 style="color:orange;" >Warning: This form has been submitted</h3>';
         $this->errorFormStr['*error']='<h3 style="color:red;" >ERROR: Please correct and try again:</h3>';
         $this->errorFormStr['*errMin']='<span style="color:red;">&lt;- too short</span>';
         $this->errorFormStr['*errMax']='<span style="color:red;">&lt;- too long</span>';
         $this->errorFormStr['*errRequired']='<span style="color:red;">&lt;- required</span>';
         $this->errorFormStr['*errMessage']='<span style="color:red;">&lt;- invalid</span>';
         $this->errorFormStr['*errorSent']='<h3 style="color:red;">NOTE: This form cannot be re-sent</h3>';

      }
    


      public function generalPlaceholder($attr,$tag){
  /*
  		  Form tags process the form and update a dataset
          When used on a page the page returns to itself and form being reloaded causes
          the tags to update and update the associated dataset.
          If using AJAX you simply need to parse (ie use $this->processTags($form);) this will
          evaluate all tags including loading this class to evaluated a form with form tags.
          The form and dataset are updated there is no need for a complex API!
          Using AJAX you simply need to first request the form from the server
          and display it and then on update pass the form variables back to the server. The
          AJAX server side runs as part of view if it has a .php extension. When a request
          is received a fresh tagged copy of the form it retrieved and parsed with  $this->processTags($form)
          Provided the forms POST or GET method matches the AJAX method the $this->processTags($form)
          will update the data set.

      */
      	print $this->model->getPageName();
     
      	$found=true;
         $lTag=strtolower($tag);
         $lAttr=array();
         foreach($attr as $attrName=>$value){
         	$lAttr[strtolower($attrName)]=$value;
         }
         switch($lTag){
             case 'form':
                   /* print "Example tag test passed:
                     {$attribute['name']} {$attribute['file']} {$attribute['attr1']}
                     {$attribute['attr2']}";*/
                    $str="<$lTag";
                    foreach($attr as $attrName=>$value){
                       $lAttrName=strtolower($attrName);
                       $lValue=strtolower($value);
                       if(substr($attrName,0,1)=='*'){
                            switch($lAttrName){
                                case '*data':
                                	 if(!class_exists($value){
                                	 	$this->dataObject=new mamurDataObject();
                                	 }
                                     break;
                                case '*table':
                                     $this->dataSetTable=$value;
                                     break;
                                case '*rowid':
                                     $this->dataSetRow=$value;
                                     break;
                                case '*rownumber':
                                     $this->dataSetRow=intval($value);
                                     break;
                                case '*protection':
                                     $this->protection=$value;
                                     break;
                                 case '*resubmit':
                                    $this->resubmit=$value;
                                    break;
                                 case '*errmin':
                                 case '*errmax':
                                 case '*error':
                                 case '*errmessage':
                                 case '*errrequired':
                                      $this->errorFormStr[$lAttrName]=htmlspecialchars($value,ENT_NOQUOTES,'UTF-8',false);
                                    break;
                                 case '*onokfunction':
                                 case '*actionfunction':
                                    $this->actionFunction=$value;
                                    break;
                                 case '*oncancelfunction':
                                    $this->cancelFunction=$value;
                                    break;
                                 case '*oncancel':
                                 case '*cancel':
                                    $this->cancel=$value;
                                    break;
                                 case '*onok':
                                 case '*action':
                                    $this->action=$value;
                                    break;
                                 case '*actionoverride':
                                    $this->actionOverRide=$value;
                                    break;

                            }
                       }else{
                            //action is filtered out as it depends on validation


                            switch($lAttrName){
                                case 'method':
                                     if($lValue=='post'){
                                        $this->request=&$_POST;
                                     }else{
                                        $this->request=&$_GET;
                                     }
                                     $str.=" $attrName=\"$value\"";
                                     break;
                                case 'action':
                                     $this->action=$value;
                                     break;
                                default:
                                     $str.=" $attrName=\"$value\"";
                            }


                       }
                    }
                    //get dataset if new then ignore any post as first time page
                    //is seen
                    $this->dataSet=$this->mamur->getDataSet($this->dataSetName);
                   
                    if(!isset($this->dataSet['formValid'])){
                          $this->dataSet['action']=$this->action;
                          $this->dataSet['override']=$this->actionOverRide;
                          $this->dataSet['cancel']=$this->cancel;
                          $this->dataSet['new']=true;
                          $this->dataSet['formValid']=false;
                          $str.=" action=\"{$_SERVER['REQUEST_URI']}\" ";
                    }elseif(isset($this->request['mamurSecId'])
                             && $this->request['mamurSecId']==$this->dataSet['mamurSecId']
                             && isset($this->dataSet['table'])){
                         //now validate form against input and set action or redirect
                         $this->dataSet['formValid']=true;
                         $this->dataSet['new']=false;
                         foreach($this->dataSet['table'][$this->dataSetTable][$this->dataSetRow] as $fieldName=>$fieldValue){
                             if(isset($this->request[$fieldName])){
                                //Validate input   
                                $newValue=$this->request[$fieldName];
                                if(isset($this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$fieldName])
                              	 ){
                              	    if($this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$fieldName]!=''){
                              		      $newValue=$this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$fieldName];
                                 		}else{
                              			 $newValue='1';	
                              		}		
                                }
                                $valid=$this->validate($newValue,$fieldName);
                                $this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$fieldName]=
                                        trim(htmlspecialchars($newValue,ENT_NOQUOTES,'UTF-8',false));
                                if($valid===true){
                                   $this->dataSet['errormessage'][$this->dataSetTable][$fieldName]='';
                                }else{
                                   $this->dataSet['errormessage'][$this->dataSetTable][$fieldName]=$valid;
                                   $this->dataSet['formValid']=false;
                                }
                                if($this->dataSet['formValid'] &&
                                    isset($this->dataSet['external_errormessage'][$this->dataSetTable][$fieldName])){
                                    $this->dataSet['formValid']=false;
                                    $this->dataSet['errormessage'][$this->dataSetTable][$fieldName]=$this->dataSet['external_errormessage'][$this->dataSetTable][$fieldName];
                                    unset($this->dataSet['external_errormessage'][$this->dataSetTable][$fieldName]);
                                }
                             }elseif(isset($this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$fieldName])){
                               	     $this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$fieldName]='';                              				
                            }
                             
                         }
                         //now process completed and valid form
                         if($this->dataSet['formValid']){
                                //process special functions on form completion
                                 if($this->actionFunction!='' ){
                                      $dir=dirname($this->actionFunction);
                                      if(empty($dir)|| $dir=='.' || $dir=='/' ){
                                         $dir=dirname(__FILE__);
                                      }else{
                                         $dir=$this->mamur->getPluginDir($dir);

                                      }
                                      $file=basename($this->actionFunction);

                                      include_once($dir.'/'.$file);
                                 }
                                 //need to check status after processing
                                 if($this->dataSet['formValid']){
                                    //in case of aceptance required
                                    if( (isset($this->dataSet['override']) &&
                                        $this->dataSet['override']=='noredirect')||
                                        $this->actionOverRide=='noredirect'){
                                        $this->dataSet['overRidenAction']='noredirect';

                                    }else{
                                    	$this->mamur->setDataSet($this->dataSetName,$this->dataSet);
 
                                        $str.=" action=\"{$this->action}\" ";
                                        $this->view->redirect($this->action);
                                    }
                                 }
                         }else{
                                 $str.=" action=\"{$_SERVER['REQUEST_URI']}\" ";
                         }
                    }else{
                        if($this->dataSet['formValid'] && !$this->dataSet['new']  ){
                            print $this->resubmit;
                        }
                        $str.=" action=\"{$_SERVER['REQUEST_URI']}\" ";
                    }

                    $str.=" >";
                    print $str;

                    //set the dataset secure one time use id
                    $this->dataSet['mamurSecId']=$this->mamur->getRandomString(16);
                    print "\n<input type=\"hidden\" name=\"mamurSecId\" value=\"{$this->dataSet['mamurSecId']}\" />\n";
                    if(!$this->dataSet['formValid'] && !$this->dataSet['new']  ){

                        print $this->errorFormStr['*error'];
                    }
                    break;


            case 'input':
                    $str="<$lTag";
                    $this->dataSet['check'][$this->dataSetTable][$attr['name']]['maxlength']=500;
                    if($lAttr['type']=='checkbox'){
                        if(isset($this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$attr['name']])){
                       		$attr['value']=$this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$attr['name']];
                    		$lAttr['value']=$attr['value'];
                        }elseif(!isset($lAttr['value']) && isset($lAttr['checked']) ){
                        	$attr['value']='1';
                    	    $lAttr['value']='1';	
                        }
                    }
                    if(!isset($lAttr['value']) ){
                    	$attr['value']='';
                    	$lAttr['value']='';
                    }
                    $str.=$this->fldAttrib($attr,$lTag);
                    if($lAttr['type']=='checkbox' && 
                        isset($this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']]) &&
                        $this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']]!==''
                    ){
                        $str.=' checked="checked"';
                    }
                    if($lAttr['type']=='radio' && 
                        isset($this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']]) &&
                        $this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']]===$lAttr['value']
                    ){
                    	  $str.=' checked="checked"';
                    }
                    
                    $str.=" />";
                    if(!empty($this->dataSet['errormessage'][$this->dataSetTable][$attr['name']])){
                       $str.=$this->dataSet['errormessage'][$this->dataSetTable][$attr['name']];
                    }
                    print $str;
                    break;

            case 'textarea':
                  $this->dataSet['check'][$this->dataSetTable][$attr['name']]['maxlength']=2000;
                  $str="<$lTag";
          		  if(!isset($lAttr['value']) ){
                    	$attr['value']='';
                    	$lAttr['value']='';
                  }
                  $str.=$this->fldAttrib($attr,$lTag);
                  $str.= " >";
                  $value='';
                  if(isset($this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']])){
                        $value=$this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']];
                   }else{
                        $this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']]=$value;
                  }
                  $str.=$value."</textarea>";
                  if(!empty($this->dataSet['errormessage'][$this->dataSetTable][$attr['name']])){
                       $str.=$this->dataSet['errormessage'][$this->dataSetTable][$attr['name']];
                  }
                  print $str;
                  break;

            case 'select':
                   $this->selectDataValue='';
                   if(isset($lAttr['*select'] )){
                       $this->selectDataValue=$lAttr['*select'];
                   }
                   if(isset($this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']])){
                        $this->selectDataValue=$this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']];
                   }else{
                        $this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$attr['name']]=$this->selectDataValue;
                   }
                  $this->selectDataName=$attr['name'];
                  $this->inSelect=true;
                  $str="<$lTag";
                  $str.=$this->fldAttrib($attr,$lTag);
                  $str.=" >";
                  print $str;
                  break;

            case 'endselect':
                  $this->selectDataValue='';
                  $this->inSelect=false;
                  $str='</select>';
                  if(!empty($this->dataSet['errormessage'][$this->dataSetTable][$this->selectDataName])){
                       $str.=$this->dataSet['errormessage'][$this->dataSetTable][$this->selectDataName];
                  }
                  print $str;
                  break;

            case 'optionlist':

                  if(isset($lAttr['*dataset'])){
                     $table='default';
                     if(isset($lAttr['*table'])){
                       $table=$lAttr['*table'];
                     }
                     $col=0;
                     if(isset($lAttr['*col'])){
                       $col=$lAttr['*col'];
                     }
                     $attribs="";
                     foreach($attr as $attrName=>$attrValue){
                         if(substr($attrName,0,1)!='*'){
                             $attribs.=" $attrName=\"$attrValue\"";
                         }
                     }
                     $options=$this->mamur->getDataSet($lAttr['*dataset']);

                     if(isset($options['table'][$table]))foreach($options['table'][$table] as $row=>$field){

                        if(isset($lAttr['*index'])){
                            $value=$field[$lAttr['*index']];
                            $sel='';
                            if($this->inSelect && $this->selectDataValue==$value){
                                    $sel=' selected="selected"';
                            }
                            print "<option $attribs$sel value=\"{$value}\">{$field[$col]}</option>\n";
                        }else{

                            $value=$field[$col];
                            $sel='';
                            if($this->inSelect && $this->selectDataValue==$value){
                                    $sel=' selected="selected"';
                            }
                            print "<option $attribs$sel>{$field[$col]}</option>\n";
                        }

                     }

                  }
                  break;

            case 'option':
                  $str="<$lTag";
                  $value='';
                  $returnValue='';
                  $str.=$this->fldAttrib($attr,$lTag);
                  if(isset($lAttr['*value'])){
                    if(isset($lAttr['value'])){
                      $value=$lAttr['value'];
                      $returnValue=$lAttr['*value'];
                      $str.=' value="'.$lAttr['*value'].'"';
                    }else{
                       $value=$lAttr['*value'];
                       $returnValue=$value;
                    }
                  }elseif(isset($lAttr['value'])){
                      $value=$lAttr['value'];
                      $returnValue=$value;
                  }
                  if($this->inSelect && $this->selectDataValue==$returnValue){
                     $str.=' selected="selected" ';
                  }
                  $str.=" >";
                  $str.=$value;
                  $str.="</option >";
                  print $str;
                  break;

            case 'endform':
                  $this->mamur->setDataSet($this->dataSetName,$this->dataSet);
                  print '</form>';
                  if(!$this->dataSet['formValid'] && !$this->dataSet['new']  ){
                        print $this->errorFormStr['*error'];
                  }
                  break;

            default:
                  $found=false;
         }
         return $found;
      }


      protected function fldAttrib($attr,$lTag){
         $str=' ';
         $lAttr=array();
         foreach($attr as $attrName=>$value){
         	$lAttr[strtolower($attrName)]=$value;
         }
         foreach($attr as $attrName=>$value){
                       $lAttrName=strtolower($attrName);
                       $lValue=strtolower($value);
                       if(substr($attrName,0,1)=='*'){
                           $rAttrName=substr($attrName,1); //name with '*' removed
                           switch($lAttrName){
                                case '*maxlength':
                                case '*minlength':
                                      $this->dataSet['check'][$this->dataSetTable][$lAttr['name']][$rAttrName]=$value;
                                     break;
                                 case '*errMin':
                                 case '*errMax':
                                 case '*error':
                                 case '*errMessage':
                                 case '*errorSent':
                                 case '*errRequired':
                                      $this->dataSet['error'][$this->dataSetTable][$lAttr['name']][$lAttrName]=
                                            htmlspecialchars($value,ENT_NOQUOTES,'UTF-8',false);
                                      break;
                                 case '*verify':
                                      $this->dataSet['check'][$this->dataSetTable][$lAttr['name']]['type']=$lValue;
                                      break;
                                 case '*required':
                                 	  if($lValue!='n' && $lValue!='no' && $lValue!='false'){
                                 	     $this->dataSet['check'][$this->dataSetTable][$lAttr['name']]['required']=1;
                                      }
                                 	  break;
                                 case '*pattern':
                                      $this->dataSet['check'][$this->dataSetTable][$lAttr['name']]['pattern']=$lValue;
                                      break;

                                default:

                           }
                       }else{
                           $show=true;
                           switch($lAttrName){
                                case 'value':
                                    if($lTag=='input'){
                                        if(isset($lAttr['type']) && $lAttr['type']=='radio' && isset($lAttr['value'])){
                                        	$value=$lAttr['value'];  //the value for radio buttons does not change
                                        	//data set value must always be set so set it to null
                                        	if(!isset($this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']])){	
                                        		$this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']]=0;
                                        	}
                                            if(isset($this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']])
                                               && $this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']]=== 0
                                               && isset($lAttr['checked'])
                                            ){	
                                        		$this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']]=$lAttr['value'];
                                        	}
                                        }elseif(isset($this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']])){
                                            $value=$this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']];
                                            //if value is already in dataset but checkbox not defined then set it to dataset value
                                            if(isset($lAttr['type']) && $lAttr['type']=='checkbox'){
                                               if( !isset($this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']])){         	
                                            		$this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']]=$value;        	
                                               }else{
                                               	  $value=$this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']];
                                               }
                                               
                                            }
                                         
                                        }else{
                                           $this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']]=$value;
                                           //set checkbox value to any value given in form if dataSet empty
                                           if(isset($lAttr['type']) && $lAttr['type']=='checkbox'){
                                               if(!isset($this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']])){
                                           	   		if($value==''){
                                           	   	  		$value="1";
                                           	   		}
                                              		$this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']]=$value;
                                               		if(!isset($lAttr['checked'])){
                                            	 		$this->dataSet['table'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']]='';
                                               		}
                                           		}else{
                                           			$value=$this->dataSet['checkbox'][$this->dataSetTable][$this->dataSetRow][$lAttr['name']];
                                           		}
                                           }
                                        }
                                       
                                    }elseif($lTag=='option'){
                                        $show=false; //value is never shown in option atrribute use *value
                                    }
                                    break;
                               case 'checked':
                               	        $show=false; //checked is always computed && overriden by a preset dataset field
                                	    break;
                               case 'minlength':
                               case 'maxlength':
                                     $this->dataSet['check'][$this->dataSetTable][$lAttr['name']][$lAttrName]=$value;
                                     break;
                               case 'required':
                                     //required in html5 is required='required' or just present
                                     $this->dataSet['check'][$this->dataSetTable][$lAttr['name']]['required']=1;
                                     break;
                               case 'type':
                                      switch($lAttrName){
                                            case 'email':
                                            case 'tel':
                                            case 'number':
                                                $this->dataSet['check'][$this->dataSetTable][$lAttr['name']]['type']=$lAttrName;
                                            break;
                                        default:
                                      }
                                      break;
                                case 'pattern':
                                    $this->dataSet['check'][$this->dataSetTable][$lAttr['name']]['pattern']=$value;
                                    break;
                                default:

                           }
                           if($show)$str.=" $attrName=\"$value\"";
                       }
                    }

           return $str;
      }

      function validate($invalue,$name){
         $valid=true;

         if(!empty($this->dataSet['check'][$this->dataSetTable][$name]['minlength'])){
           $minLength=$this->dataSet['check'][$this->dataSetTable][$name]['minlength'];
         }else{
           $minLength=0;
         }

         if(!empty($this->dataSet['check'][$this->dataSetTable][$name]['maxlength'])){
           $maxLength=$this->dataSet['check'][$this->dataSetTable][$name]['maxlength'];
         }else{
           $maxLength=2000;
         }
         $errorMessage='';
         if(isset($this->dataSet['error'][$this->dataSetTable][$name]['*errMessage'])){
                $errorMessage=$this->dataSet['error'][$this->dataSetTable][$name]['*errMessage'];
         }else{
                $errorMessage=$this->errorFormStr['*errMessage'];
         }

         $len=strlen($invalue);
         if(!empty($this->dataSet['check'][$this->dataSetTable][$name]['required']) && $len==0  ){   	
              if(isset($this->dataSet['error'][$this->dataSetTable][$name]['*errRequired'])){
                  $valid=$this->dataSet['error'][$this->dataSetTable][$name]['*errRequired'];
              }else{
                  $valid=$this->errorFormStr['*errRequired'];
              }
         }elseif($len>$maxLength){
              if(isset($this->dataSet['error'][$this->dataSetTable][$name]['*errMax'])){
                  $valid=$this->dataSet['error'][$this->dataSetTable][$name]['*errMax'];
              }else{
                 $valid=$this->errorFormStr['*errMax'];
              }
         }elseif($len<$minLength){
               if(isset($this->dataSet['error'][$this->dataSetTable][$name]['*errMin'])){
                  $valid=$this->dataSet['error'][$this->dataSetTable][$name]['*errMin'];
              }else{
                 $valid=$this->errorFormStr['*errMin'];
              }
         }elseif(!empty($this->dataSet['check'][$this->dataSetTable][$name]['type'])){
             $errorMess='';
             $check=$this->dataSet['check'][$this->dataSetTable][$name]['type'];
             if ($check!='' && $invalue!="") {
                switch ($check){
                    case "none":
                        break;
                    case "sameasnameornull":
                        if($invalue=="") break;
                    case "sameasname":
                        if(strtolower($name)!=strtolower($invalue)){
                            $errorMess=true;
                        }
                        break;
                    case 'email':
                        $verifyregex= '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/is';
                        if (!preg_match($verifyregex,$invalue)){
                            $errorMess=true;
                        }else{
                            if(strstr($invalue,'@')===false){
                                $errorMess=true;
                            }else{
                                list($userName, $mailDomain) = explode("@", $invalue);
                                if (((strlen($userName)+strlen($mailDomain))!=(strlen($invalue)-1))||($mailDomain=="")){
                                    $errorMess=true;
                                }
                                if (preg_match('/^\.|\.\.|^\.|\.$/is',"$userName.$mailDomain"))$errorMess=true;
                            }
                        }
                        break;
                    case 'emaildns':
                        $verifyregex= '/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}$/is';
                        if (!preg_match($verifyregex,$invalue)){
                             $errorMess=true;
                        }else{
                            //See if domain has mail.
                            list($userName, $mailDomain) = explode("@", $invalue);
                            if (((strlen($userName)+strlen($mailDomain))!=(strlen($invalue)-1))||($mailDomain=="")){
                                    $errorMess=true;
                            }elseif(preg_match('/^\.|\.\.|^\.|\.$/is',"$userName.$mailDomain")){
                                    $errorMess=true;
                            }else{
                                if (checkdnsrr($mailDomain, "MX")) {
                                    //domain accepts email and is live now verify username
                                    $verifyregex= '/^[A-Z0-9._%-]+$/is';
                                    if (!preg_match($verifyregex,$userName))$errorMess=true;
                                 }else{
                                    $errorMess=true;
                                 }
                            }
                         }
                         break;
                    default:
                        $verifyreg['printkeys']= '/^[A-Z0-9._%^,:;\s=!"£$%^&\*()\'\-\+@#?\/`¬|]*(.){0,1}(.)*$/is';
                        $verifyreg['style']= '/^[A-Z0-9_\-]*(.){0,1}(.)*$/is';
                        $verifyreg['name']= '/^[A-Z.\x20\-]*(.){0,1}(.)*$/is';
                        $verifyreg['username']= '/^[A-Z0-9.\x20\-]*(.){0,1}(.)*$/is';
                        $verifyreg['alpha']= '/^[A-Z.\x20\']*(.){0,1}(.)*$/is';
                        $verifyreg['alphanum']= '/^[A-Z0-9]*(.){0,1}(.)*$/is';
                        $verifyreg['alphanumstop']= '/^[A-Z0-9.\-\x20\']*(.){0,1}(.)*$/is';
                        $verifyreg['password']= '/^[A-Z0-9\x20._%^,:;=!"£$%^&\*()\-\+@#?]*(.){0,1}(.)*$/is';
                        $verifyreg['telephone']= '/^[0-9+.\-xXeE()\s]*(.){0,1}(.)*$/is';
                        $verifyreg['tel']= '/^[0-9+.\-xXeE()\s]*(.){0,1}(.)*$/is';
                        $verifyreg['filename']= '/^[A-Za-z0-9\x20._%-]*(.){0,1}(.)*$/is';
                        $verifyreg['fullfilename']= '/^[A-Za-z0-9\x20\/._%-]*(.){0,1}(.)*$/is';
                        $verifyreg['link']= '/^[A-Za-z0-9:&?=\x20\/._%-]*(.){0,1}(.)*$/is';
                        $verifyreg['fullfilename_noext']= '/^[A-Za-z0-9\/_%-]*(.){0,1}(.)*$/is';
                        $verifyreg['filename_noext']= '/^[A-Za-z0-9_%-]*(.){0,1}(.)*$/is';
                        $verifyreg['numspace']= '/^[0-9\x20]*(.){0,1}(.)*$/is';
                        $verifyreg['text']= '/^[\x20-\x7f\s\x80-\xAE]*(.){0,1}(.)*$/is';
                        $verifyreg['integer']= '/^[+\-]*[0-9]*(.){0,1}(.)*$/is';
                        $verifyreg['real']= '/^[+\-]*[0-9]*[.]*[0-9]*(.){0,1}(.)*$/is';
                        $verifyreg['number']= '/^[+\-]*[0-9,?ex+\-]*(.){0,1}(.)*$/is';
                        $verifyreg['pounds']= '/^[£+\-]*[0-9]*[.]*[0-9]*(.){0,1}(.)*$/is';
                        $verifyreg['dollars']= '/^[$+\-]*[0-9]*[.]*[0-9]*(.){0,1}(.)*$/is';
                        $verifyreg['date']= '/^[A-Za-z0-9\/.\x20\-]*(.){0,1}(.)*$/is';
                        //if  case value is in the above regex array verify the input string
                        if(isset($verifyreg[$check])){
                             preg_match($verifyreg[$check],$invalue,$matches);
                             if (isset($matches[1])&& $matches[1]!=''){
                                    if(ord($matches[1])<=126){
                                       $errChar= htmlspecialchars($matches[1],ENT_NOQUOTES,'UTF-8',false);
                                       $errorMess=$errorMessage.' "'.$errChar.'"?';
                                    }else {
                                       $errorMess=true;
                                    }
                            }
                       }else{
                                $errorMess=$check.' ?';
                       }
                       if($errorMess==''){
                            if($check=='date' && !strtotime($invalue)){
                                $errorMess=true;
                            }elseif($check=='number' && is_numeric($invalue)){
                                $errorMess=true;
                            }
                       }
                }
            }
            if($errorMess===true){
                 $valid=$errorMessage;
            }elseif($errorMess!=''){
                 $valid=$errorMess;
            }
       }elseif(!empty($this->dataSet['check'][$this->dataSetTable][$name]['pattern'])){
             $errorMess='';
             $pattern='/^'.$this->dataSet['check'][$this->dataSetTable][$name]['pattern'].'$/is';
             if (!preg_match($pattern,$invalue)){
                    $valid=$errorMessage."pattern='$pattern'";;
             }
       }
         return $valid;
      }
      
}


