 <?php
 /**
 * This file contains the core view Class - mamurForm
 * 
 * Forms are produced using mamur tags which mimic HTML tags ie are identical except
 * for open and close of tags uses the mamur tag format. The exception being textarea which
 * is contained in one tag with the contents being in the vlaue attribute. In addtion
 * Attributes have also been added, principly those begining with a * character are
 * used but never shown in the form html.
 * 
 * All Forms have a nonce number used once which is added as a hidden field and verified
 * on receipt. Forms can only be submitted once and the number must match that stored in the
 * session. This improves security since a failer casues a re-issue of the cookie under @author sygenius
 * new encrytion.
 * 
 * Data used by the form is stored in a data object which is stored in the model and can be
 * obtained thus:
 * $dataObject=$this->model->getDataObject($this->dataObjectName);
 * A form object contains the status and field information of the form and is contained in another
 * data object specific to the form instance and can be obtained from the model:
 * $formObject=$this->model->getDataObject($this->formObjectName);
 * Mamur perists all models dataobjects if they are flagged for persistance and have been changed
 * 
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
       		  $dataSetRow,$protection,
      		  $actionOverRide,$action,$cancel,$actionFunc,$cancelFunc,$request,$resubmit,
      		  $errorFormStr,$selectDataValue,$selectDataName,$inSelect;

      function __construct($setModel,$setView){
      	
         $this->mamur=$setModel;
         $this->model=$setModel;
         $this->view=$setView;

         $this->dataObject=null;
         $this->dataObjectName='defaultForm';
         $this->formObjectName=null;
         
               
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
         $this->errorFormStr['*error']='<h3 style="color:red;" >Form has errors: Please correct and try again</h3>';
         $this->errorFormStr['*errormin']='<span style="color:red;">&lt;- too short</span>';
         $this->errorFormStr['*errormax']='<span style="color:red;">&lt;- too long</span>';
         $this->errorFormStr['*errorrequired']='<span style="color:red;">&lt;- required</span>';
         $this->errorFormStr['*errormessage']='<span style="color:red;">&lt;- invalid</span>';
         $this->errorFormStr['*errorsent']='<h3 style="color:red;">NOTE: This form cannot be re-sent</h3>';

      }
    

     /**
      * 
      * Form placeholder - produces the open form html tag
      * and set up the data and form objects
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
               		case '*errormin':
             		case '*errormax':
               		case '*error':
           			case '*errormessage':
     				case '*errorrequired':
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
         if(!empty($this->formId)){
         		$this->formObjectName='form_'.$this->formId.'_'.$this->dataObjectName; 
         }elseif(!empty($this->formName)){
         	 	$this->formObjectName='form_'.$this->formName.'_'.$this->dataObjectName; 
         }else{
         		$this->formObjectName='form_'.$this->dataObjectName; 
         }
              
         $formObject=$this->model->getDataObject($this->formObjectName);
         
		if(!isset($formObject->formValid)){
			
         	$formObject->formValid=false;
            $formObject->action=$this->action;
            $formObject->override=$this->actionOverRide;
            $formObject->cancel=$this->cancel;
   			$formObject->new=true; 
            $formObject->errorMessages=array();
            $formObject->fieldCheck=array();
            $formObject->checkBox=array();
            $formObject->hasExternalErrors=false;
                          
			$str.=' action="'.$this->model->getPageDir().'/'.$this->model->getPageName().'.'.$this->model->getPageExt().'"';
			
			  trigger_error("NEW FORM");
		}else{
           
//var_dump ($formObject);
            trigger_error("Form ret1 saved nonce=".$formObject->getNonce());
			
            if(isset($this->request['mamurNonce'])
                && $this->request['mamurNonce']==$formObject->getNonce()
        	){
            	//now validate form against input and set action or redirect
            	
            trigger_error("Form valid");
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
                    		$errorMessages[$fieldName]='';                   
                    	}else{
                        	$errorMessages[$fieldName]=$valid;
                        	$formObject->formValid=false;
                  		}
                    	
   					}elseif(isset($dataObject->$fieldName)){
   						//this nulls anything which is not returned!
                         $dataObject->$fieldName='';                              				
                	}
                             
   				}//end for each field name
        	    if($formObject->formValid && $formObject->hasExternalErrors){
                       		$formObject->formValid=false;
                        	$errorMessages=$formObject->externalErrorMessages;
                        	$formObject->hasExternalErrors=false;
                        	$formObject->externalErrorMessages=array();
                }
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
                                    if( (isset($formObject->override) &&
                                        $formObject->override=='noredirect')||
                                        $this->actionOverRide=='noredirect'){
                                        $formObject->overRidenAction='noredirect';

                                    }else{
                                    	//$this->mamur->setDataSet($this->dataSetName,$this->dataSet);
                                        $this->model->deleteDataObject($this->formObjectName);
                                        
                                        $str.=" action=\"{$this->action}\" ";
                                        $this->view->redirect($this->action);
                                    }
                	}
           		}else{
            		//$str.=" action=\"{$_SERVER['REQUEST_URI']}\" ";
            		$str.=' action="'.$this->model->getPageDir().'/'.$this->model->getPageName().'.'.$this->model->getPageExt().'"';
	
            	}
            
        	}else{
        		//$this->formObjectName=$dataObject->getAttribute('formObjectName');
            	//$formObject=$this->model->getDataObject($this->formObjectName);
        	
            	if($formObject->formValid && !$formObject->new ){
            		print $this->resubmit;
            	}
           		//$str.=" action=\"{$_SERVER['REQUEST_URI']}\" ";
           		$str.=' action="'.$this->model->getPageDir().'/'.$this->model->getPageName().'.'.$this->model->getPageExt().'"';
		
        	}
        
        }

        $str.=" >";
        print $str;

        //set the dataset Nonce a security one time use id
   
         
        print "\n<input type=\"hidden\" name=\"mamurNonce\" value=\"{$formObject->setNonce()}\" />\n";
        if(!$formObject->formValid && !$formObject->new ){
			print $this->errorFormStr['*error'];
        }
    print_r($errorMessages);    
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
     	$check=$formObject->fieldCheck;
        $check[$lAttr['name']]['maxlength']=500;
        $formObject->fieldCheck=$check;
        
        if(!isset($lAttr['type']) ){
        	$lAttr['type']='input';
        }
                    
        if($lAttr['type']=='checkbox'){
            $checkBox= $formObject->checkBox;               
        	if(!is_null($checkBox) && isset($checkBox[$lAttr['name']])){
        	     $lAttr['value']=$checkBox[$lAttr['name']];    
            }elseif(!isset($lAttr['value']) && isset($lAttr['checked']) ){
                 $lAttr['value']='1';	
            }                
        }
        if(!isset($lAttr['value']) ){
        	$lAttr['value']='';
        }
       
        $str.=$this->fldAttrib($lAttr,$lTag); 
        
         
        if($lAttr['type']=='checkbox' 
        	&& isset($dataObject->$lAttr['name'])
            && $dataObject->$lAttr['name']!==''
        ){
            $str.=' checked="checked"';
        }elseif($lAttr['type']=='radio' 
             && isset($dataObject->$lAttr['name'])
             && $dataObject->$lAttr['name']===$lAttr['value']
        ){
        	$str.=' checked="checked"';
        }
                    
       	$str.=" />";
       	/*
        if(!empty($dataObject->errormessage'][$this->dataSetTable][$attr['name']])){
                       $str.=$this->dataSet['errormessage'][$this->dataSetTable][$attr['name']];
                    }
        */
        if(!empty($formObject->errorMessages[$lAttr['name']])){
        	$str.=$formObject->errorMessages[$lAttr['name']];
        }
        print $str;
      }
      
      
      
      public function doTextarea($serparams){
      	$attr=unserialize($serparams);
      	$lAttr=array();
      	foreach($attr as $attrName=>$attrValue){
      		$lAttr[strtolower($attrName)]=$attrValue;
      	}
      	$lTag="textarea";
     	$str="<$lTag";
     	$formObject=$this->model->getDataObject($this->formObjectName);
     	$dataObject=$this->model->getDataObject($this->dataObjectName);
     	
     	$check=$formObject->fieldCheck;
        $check[$lAttr['name']]['maxlength']=2000;
        $formObject->fieldCheck=$check;
        
        $value='';
        if(isset($lAttr['value'])){
        	$value=$lAttr['value'];
        	//do not want to show value attribute ie should not have been set
        	unset($lAttr['value']);
        }
        
      	$str.=$this->fldAttrib($lAttr,$lTag);
        $str.= " >";
        //now add the value part of textarea between tags
        if(isset($dataObject->$lAttr['name'])){
             $value=$dataObject->$lAttr['name'];
        }else{
        	$dataObject->$lAttr['name']=$value;
        }
      	$str.= $value."</textarea>";
      	
        if(!empty($formObject->errorMessages[$lAttr['name']])){
        	$str.=$formObject->errorMessages[$lAttr['name']];
        }
        print $str;
                 
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
      //	$this->mamur->setDataSet($this->dataSetName,$this->dataSet);
        print '</form>';
        $formObject=$this->model->getDataObject($this->formObjectName);
        if($formObject->formValid && !$formObject->new ){
        	print $this->errorFormStr['*error'];
        }
    //     var_dump ($formObject->fieldCheck);
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
         $checkBox=$formObject->checkBox;   
         $dataObject=$this->model->getDataObject($this->dataObjectName);
         $check=$formObject->fieldCheck;
         $str=' ';
         foreach($lAttr as $lAttrName=>$value){
         	$lValue=strtolower($value);
            if(substr($lAttrName,0,1)=='*'){
            	$rAttrName=substr($lAttrName,1); //name with '*' removed
                switch($lAttrName){
                	case '*maxlength':
                	case '*minlength':
                     		$check[$rAttr['name']][$lAttrName]=$value;
                  			break;
                    case '*errormin':
              		case '*errormax':
                    case '*error':
                   	case '*errormessage':
                    case '*errorsent':
                   	case '*errorrequired':
                    		$this->errorFormStr[$lAttr['name']][$lAttrName]=htmlspecialchars($value,ENT_NOQUOTES,'UTF-8',false);
                       		 break;
                    case '*verify':
                        	$check[$lAttr['name']]['verify']=$lValue;
                        	break;
                   	case '*required':
                      		if($lValue!='n' && $lValue!='no' && $lValue!='false'){
                        		$check[$lAttr['name']]['required']=1;
                       		}
                       		break;
                   	case '*pattern':
                            $check[$lAttr['name']]['pattern']=$value;
                       		break;
					case '*value':
						    $dataObject->$lAttr['name']=$value;
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
                                if(!isset($checkBox[$lAttr['name']])){	
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
                                        $value=$checkBox[$lAttr['name']];
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
                       	//otherwise value tag is shown
                       	
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
                                $check[$lAttr['name']]['verify']=$lAttrName;
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
     	
		$formObject->fieldCheck=$check;
        $formObject->checkBox=$checkBox;          
      	return $str;
   	}
      	

      
	protected function validate($invalue,$name){
         $valid=true;
      
         $formObject=$this->model->getDataObject($this->formObjectName);
     	 $dataObject=$this->model->getDataObject($this->dataObjectName);
     	 
     	$check=$formObject->fieldCheck[$name];
    //    print_r($check);
        trigger_error("Validating  $name  =  '$invalue' check='".serialize($check)."'");
         

         if(!empty($formObject->fieldCheck[$name]['minlength'])){
           $minLength=$formObject->fieldCheck[$name]['minlength'];
         }else{
           $minLength=0;
         }

         if(!empty($formObject->fieldCheck[$name]['maxlength'])){
           $maxLength=$formObject->fieldCheck[$name]['maxlength'];
         }else{
           $maxLength=2000;
         }
         $errorMessage='';
         //error strings may be set per field or per form
         if(isset($formObject->errorMessages[$name]['*errormessage'])){
                $errorMessage=$formObject->errorMessages[$name]['*errormessage'];
         }else{
                $errorMessage=$this->errorFormStr['*errormessage'];
         }

         $len=strlen($invalue);
         if(!empty($formObject->fieldCheck[$name]['required']) && $len==0  ){   	
              if(isset($formObject->errorMessages[$name]['*errorrequired'])){
                  $valid=$formObject->errorMessages[$name]['*errorrequired'];
              }else{
                  $valid=$this->errorFormStr['*errorrequired'];
              }
         }elseif($len>$maxLength){
              if(isset($this->errorFormStr[$name]['*errormax'])){
                  $valid=$this->errorFormStr[$name]['*errormax'];
              }else{
                 $valid=$this->errorFormStr['*errormax'];
              }
         }elseif($len<$minLength){
               if(isset($this->errorFormStr[$name]['*errormin'])){
                  $valid=$this->errorFormStr[$name]['*errormin'];
              }else{
                 $valid=$this->errorFormStr['*errormin'];
              }
         }elseif(!empty($formObject->fieldCheck[$name]['verify'])){
             $errorMess='';
             $verify=$formObject->fieldCheck[$name]['verify'];
             if ($verify!='' && $invalue!="") {
                switch ($verify){
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
                        if(isset($verifyreg[$verify])){
                             preg_match($verifyreg[$verify],$invalue,$matches);
                             if (isset($matches[1])&& $matches[1]!=''){
                                    if(ord($matches[1])<=126){
                                       $errChar= htmlspecialchars($matches[1],ENT_NOQUOTES,'UTF-8',false);
                                       $errorMess=$errorMessage.' "'.$errChar.'"?';
                                    }else {
                                       $errorMess=true;
                                    }
                            }
                       }else{
                                $errorMess=$verify.' ?';
                       }
                       if($errorMess==''){
                            if($verify=='date' && !strtotime($invalue)){
                                $errorMess=true;
                            }elseif($verify=='number' && is_numeric($invalue)){
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
       }elseif(!empty($formObject->fieldCheck[$name]['pattern'])){
             $errorMess='';
             $pattern='/^'.$formObject->fieldCheck[$name]['pattern'].'$/is';
             if (!preg_match($pattern,$invalue)){
                    $valid=$errorMessage."pattern='$pattern'";;
             }
       }
         trigger_error("**result = $valid  ".serialize($valid));
         return $valid;
      }
      
}


