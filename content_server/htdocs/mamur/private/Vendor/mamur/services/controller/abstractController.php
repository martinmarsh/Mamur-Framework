<?php
namespace mamur\services\controller;

abstract class abstractController
{
  

    protected $fileRef;
    protected $authKey;
    protected $apiId;
    protected $autheticated;
    protected $method;
    protected $service;   
    
    protected $methods;
    
    public function __construct(){
        $this->methods=array('get','post','put','delete','head');
    }
    
    /* pre-dispatch receives the all api service 
     * contoller requets which should extend from
     * this class.
     * The headers and uri Api parameters are processed
     * so that headers take presidence. Any Auth keys
     * are validated and authoristaion noted.
     * Then distpatch is called which uses the discoverred method.
     * On return from the method postDispatch is called.
     */
    public function preDispatch()
    {   
        
        $config=\mamur\config::get();
        $fileRefList=$config->uriList;
        $expectUri=array();
        $this->method='get';
        if(isset($_SERVER['REQUEST_METHOD'])){
            $this->method=strtolower($_SERVER['REQUEST_METHOD']);
        }

        if($this->method=='get'){
            $expectUri['method'] = 'method';
        }
        $expectHeaders=array(
            'authKey'=>'HTTP_X_MAMUR_AUTH_KEY',
            'apiId'  =>'HTTP_X_MAMUR_API_ID',
            'service'=>'HTTP_X_MAMUR_SERVICE'
        );

       
        foreach($expectHeaders as $var=>$name){
            if(isset($_SERVER[$name])){
                $this->$var=$_SERVER[$name];
            }else{
                $expectUri[$var]=$var;   //url has same variable
            }
            
        }
        
   
        $required=count($expectUri)*2;
        $i=0;
        while(isset($fileRefList[0 ])&& $i< $required ){
            if(substr_compare($fileRefList[0], '__', 0,2)===0){
                $varDef=array_shift($fileRefList);
                $varDef=substr($varDef,2);
                $val=array_shift($fileRefList);
                //print"<br>found $varDef = $val";
                foreach($expectUri as $var=>$name){
                    if($name==$varDef){
                        $this->$var=$val;
                        break;
                    }
                }
                
            }else{
                throw new \Exception("URI has an unknown Api request parameter: ".$fileRefList[0]);
                break;
            }
            $i+=2;
        }
      
        //@todo: add authetication note $thia->auth
        //may contain the word autheticate
        $this->autheticated=FALSE;
        $this->fileRef=implode('/',$fileRefList);
        $this->dispatch();
        $this->postDispatch();
        
    }
    
    
    /*
     * Simply goes to the method detected
     * by pre-dispatch. May be overriden
     */
    protected function dispatch()
    {
       if(!empty($this->method)){
           $action=$this->method;
           $this->$action(); 
           return;
       } else {
           throw new \Exception("no method found in abstract dispatch");
       }
    }
    
    
    
    /*
     * Override to implement postDispatch
     * processing in a controller
     */
    
    protected function postDispatch()
    {
        
        
    }
    
}