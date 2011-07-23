 <?php
 /**
 * This file contains the core view Class - mamurForm
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
 * @name mamurForm
 * @package mamur
 * @subpackage coreView
 * @version 110
 * @mvc view
 * @release Mamur 1.10
 * @releasetag 110
 * @author Martin Marsh <martinmarsh@sygenius.com>
 * @copyright Copyright (c) 2011,Sygenius Ltd  
 * @license http://www.gnu.org/licenses GNU Public License, version 3                  
 *  					          
 */ 
 

class mamurForm{

      private $view,$dataOject,$dataObjectName,$formObjectName,$formName,$formId,
       			$dataSetTable,$dataSetRow,$protection,
      			$actionOverRide,$action,$cancel,$actionFunc,$cancelFunc,$request,$resubmit,
      			$errorFormStr,$selectDataValue,$selectDataName,$inSelect;

      function __construct($setModel,$setView){
      	
         $this->mamur=$setModel;
         $this->model=$setModel;
         $this->view=$setView;

         $this->dataObject=null;
         $this->dataObjectName='defaultForm';
         $this->dataSetTable='default';
         $this->dataSetRow=0;
         
         $this->formName='';
         $this->formId='';
         
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
    

     /**
      * 
      * Form placeholder inserts a php to invoke method
      * @param string $serparams - serialized parameter list
      */
     public function doForm($serparams){
     	$attr=unserialize($serparams);
     	$lTag="form";
     	$str="<$lTag";
        foreach($attr as $attrName=>$value){
        	$lAttrName=strtolower($attrName);
            $lValue=strtolower($value);
            if(substr($attrName,0,1)=='*'){
            	switch($lAttrName){
            		case '*data':
						$this->dataObjectName=$value;
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
             		case 'id':
             			$this->formId=$value;
             			$str.=" $attrName=\"$value\"";
             			break;
             		case 'name':
             			$this->formName=$value;
             			$str.=" $attrName=\"$value\"";
             			break;
             			  	
         			default:
                   		$str.=" $attrName=\"$value\"";
				}
             }
         }//end for each
         $errorMessages=array();
         $checkBox=array();           
         $dataObject=$this->model->getDataObject($this->dataObjectName);
              
         //get dataObject if new (null formValid) then ignore any post as first time page
         //has been prepared. If security mamurNonce is returned which matches the
         //form then it is valid post (This is not a nonce check)
         
		if(is_null($dataObject->getAttribute('formObjectName'))){
			$this->formObjectName=$this->model->getRandomString(8); 
         	if(!empty($this->formId)){
         		$this->formObjectName='form_'.$this->formId.'_'.$this->model->getRandomString(6); 
         	}elseif(!empty($this->formName)){
         	 	$this->formObjectName='form_'.$this->formName.'_'.$this->model->getRandomString(6); 
         	}else{
         		$this->formObjectName='form_'.$this->model->getRandomString(16); 
         	}
        	$formObject=$this->model->getDataObject($this->formObjectName);
         	$dataObject->setAttribute('formObjectName',$this->formObjectName);

         	$formObject->formValid=false;
            $formObject->action=$this->action;
            $formObject->override=$this->actionOverRide;
            $formObject->cancel=$this->cancel;
   			$formObject->new=true; 
            $formObject->errorMessages=array();
            $formObject->check=array();
            $formObject->checkBox=array();
            $formObject->hasExternalErrors=false;
                          
			$str.=" action=\"{$_SERVER['REQUEST_URI']}\" ";
			
		}elseif(isset($this->request['mamurNonce'])
                && $this->request['mamurNonce']==$formObject->getNonce()
        ){
            //now validate form against input and set action or redirect
            $this->formObjectName=$dataObject->getAttribute('formObjectName');
            $formObject=$this->model->getDataObject($this->formObjectName);
            
  			$formObject->formValid=true;
            $formObject->new=false;
            
            $errorMessages=$formObject->errorMessages;
            $checkBox= $formObject->checkBox;           
            foreach($dataObject->getRecord() as $fieldName=>$fieldValue){
            	if(isset($this->request[$fieldName])){
                	//Validate input   
                	$newValue=$this->request[$fieldName];
                    if(isset($checkBox[$fieldName])){
                        if($checkBox[$fieldName]!=''){
                           	$newValue=$checkBox[$fieldName];
                        }else{
                        	$newValue='1';	
                        }		
                    }
                                     
                	$valid=$this->validate($newValue,$fieldName);
          			$dataObject->$fieldName=trim(htmlspecialchars($newValue,ENT_NOQUOTES,'UTF-8',false));
                    if($valid===true){           
                    	$errorMessages=array();                   
                    }else{
                        $errorMessages=$valid;
                        $formObject->formValid=false;
                  	}
                    if($formObject->formValid && $formObject->hasExternalErrors){
                       	$formObject->formValid=false;
                        $errorMessages=$formObject->getAttribute('errorExternalMessages');
                        $formObject->hasExternalErrors=false;
                        $formObject->setAttribute('errorExternalMessages',array());
                    }
   				}elseif(isset($dataObject->$fieldName)){
   					//this nulls anything which is not returned!
                         $dataObject->$fieldName='';                              				
                }
                             
   			}//end for each field name
   			
           	//now process completed and valid form
            if($formObject->formValid){
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
                 if($formObject->formValid){
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
        	$this->formObjectName=$dataObject->getAttribute('formObjectName');
            $formObject=$this->model->getDataObject($this->formObjectName);
        	
            if($formObject->formValid && !$formObject->new ){
            	print $this->resubmit;
            }
           	$str.=" action=\"{$_SERVER['REQUEST_URI']}\" ";
        }

        $str.=" >";
        print $str;

        //set the dataset Nonce a security one time use id
        $formObject->setNonce();
         
        print "\n<input type=\"hidden\" name=\"mamurNonce\" value=\"{$formObject->getNonce}\" />\n";
        if(!$formObject->formValid && !$formObject->new ){
			print $this->errorFormStr['*error'];
        }
        
        $formObject->errorMessages=$errorMessages;
        $formObject->checkBox=$checkBox;
      	$formObject->persist();
      	$dataObject->persist();
      }
      
      
      
      
      
      public function doInput($serparams){
      	$attr=unserialize($serparams);
      	$lAttr=array();
      	foreach($attr as $attrName=>$attrValue){
      		$lAttr[strtolower($attrName)]=$attrValue;
      	}
      	$lTag="input";
     	$str="<$lTag";
     	$formObject=$this->model->getDataObject($this->formObjectName);
     	$dataObject=$this->model->getDataObject($this->dataObjectName);
     	$check=array();
        $check[$attr['name']]['maxlength']=500;
        $formObject->check=$check; 
        
        if(isset($lAttr['value']) ){
        	$value=$lAttr['value'];
        }else{
             $value='';
        }
        
        if(isset($lAttr['type']) ){
        	$type=$lAttr['type'];
        }else{
             $type='input';
        }
                    
        if($type=='checkbox'){
            $checkBox= $formObject->checkBox;               
        	if(!is_null($checkBox) && isset($checkBox[$attr['name']])){
        	     $value=$checkBox[$lAttr['name']];    
            }elseif(!isset($lAttr['value']) && isset($lAttr['checked']) ){
                        	$value='1';	
            }                
        }
       
       
        $str.=$this->fldAttrib($lAttr,$lTag); 
        
         
        if($lAttr['type']=='checkbox' 
        	&& isset($dataObject->$attr['name'])
            && $dataObject->$attr['name']!==''
        ){
            $str.=' checked="checked"';
        }
        if($lAttr['type']=='radio' 
             && isset($dataObject->$attr['name'])
             && $dataObject->$attr['name']===$lAttr['value']
        ){
        	$str.=' checked="checked"';
        }
                    
       	$str.=" />";
        if(!empty($this->dataSet['errormessage'][$this->dataSetTable][$attr['name']])){
                       $str.=$this->dataSet['errormessage'][$this->dataSetTable][$attr['name']];
                    }
                    print $str;
      }
      
      public function doTextarea($serparams){
      	$param=unserialize($serparams);
      		print "******* Input ********";
      }
      
	  public function doSelect($serparams){
	  	$param=unserialize($serparams);
      		print "******* Input ********";
      }
      
  	  public function endSelect($serparams){
  	  	$param=unserialize($serparams);
      		print "******* Input ********";
      }
      
 	  public function optionList($serparams){
 	  	$param=unserialize($serparams);
      		print "******* Input ********";
      }
      
      public function doOption($serparams){
      	$param=unserialize($serparams);
      		print "******* Input ********";
      }
      
      public function doEndform($serparams){
      	$param=unserialize($serparams);
      		print "******* End Form ********";
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


	protected function fldAttrib($lAttr,$lTag){
      	 $formObject=$this->model->getDataObject($this->formObjectName);
         $check=$formObject->check;
         $checkBox=$formObject->checkBox;   
         $error=$formObject->error;
         $dataObject=$this->model->getDataObject($this->dataObjectName);
         
         $str=' ';
         foreach($lAttr as $lAttrName=>$value){
         	$lValue=strtolower($value);
            if(substr($lAttrName,0,1)=='*'){
            	$rAttrName=substr($lAttrName,1); //name with '*' removed
                switch($lAttrName){
                	case '*maxlength':
                	case '*minlength':
                     	$check[$rAttr['name']]=$value;
                  		break;
                    case '*errMin':
              		case '*errMax':
                    case '*error':
                   	case '*errMessage':
                    case '*errorSent':
                   	case '*errRequired':
                    	$error[$lAttr['name']][$lAttrName]=htmlspecialchars($value,ENT_NOQUOTES,'UTF-8',false);
                        break;
                    case '*verify':
                        $error[$lAttr['name']]['type']=$lValue;
                        break;
                   	case '*required':
                      	if($lValue!='n' && $lValue!='no' && $lValue!='false'){
                        	$check[$lAttr['name']]['required']=1;
                       }
                       break;
                   	case '*pattern':
                            $check[$lAttr['name']]['pattern']=$lValue;
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
                                //data  value must always be set so set it to zero
                                if(!isset($check[$lAttr['name']])){	
                                       $dataObject->$lAttr['name']=0;
                                }
                                if(isset($dataObject->$lAttr['name'])
                                               && $dataObject->$lAttr['name']=== 0
                                               && isset($lAttr['checked'])
                                ){	
                                	$dataObject->$lAttr['name']=$lAttr['value'];
                               	}
                       		}elseif(isset($dataObject->$lAttr['name'])){
                                $value=$dataObject->$lAttr['name'];
                                //if value is already in dataObject but checkbox not defined then set it to dataObject value
                                if(isset($lAttr['type']) && $lAttr['type']=='checkbox'){
                                	if( !isset($checkBox[$lAttr['name']])){         	
                                    	$checkBox[$lAttr['name']]=$value;        	
                                    }else{
                                        $value=$checkBox[$this->dataSetRow][$lAttr['name']];
                                    }
                                               
                               }
                                         
                           	}else{
                    			$dataObject->$lAttr['name']=$value;
                              	//set checkbox value to any value given in form if dataSet empty
                                if(isset($lAttr['type']) && $lAttr['type']=='checkbox'){
                                	if(!isset($checkBox[$lAttr['name']])){
                                    	if($value==''){
                                        	$value="1";
                                     	}
                                       	$checkBox[$lAttr['name']]=$value;
                                        if(!isset($lAttr['checked'])){
                                       		$dataObject->$lAttr['name']='';
                                        }
                                 	}else{
                                  		$value=$checkBox[$lAttr['name']];
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
                    	$check[$lAttr['name']][$lAttrName]=$value;
                    	break;
                   	case 'required':
                                     //required in html5 is required='required' or just present
                    	$check[$lAttr['name']]['required']=1;
                        break;
                  	case 'type':
                        switch($lAttrName){
                        	case 'email':
                            case 'tel':
                            case 'number':
                                $check[$lAttr['name']]['type']=$lAttrName;
                            	break;
                            default:
                       	}
                        break;
                 	case 'pattern':
                        $check[$lAttr['name']]['pattern']=$value;
                        break;
                   	default:

                    }
             	if($show)$str.=" $lAttrName=\"$value\"";
            }
     	}

        $formObject->check=$check;
        $formObject->checkBox=$checkBox;
        $formObject->error=$error;          
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


