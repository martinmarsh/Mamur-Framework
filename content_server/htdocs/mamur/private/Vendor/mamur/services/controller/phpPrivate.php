<?php
namespace mamur\services\controller;

class phpPrivate extends abstractController{
    
   protected function dispatchMethod($action=FALSE)
   {
       print "in web";
       if($action && $this->autheticated ){
           $this->action=$action;
           $this->$action();       
       } else {
           tigger_error("no action in abstract dispatchMethod",E_USER_ERROR);
       }
   }
}   