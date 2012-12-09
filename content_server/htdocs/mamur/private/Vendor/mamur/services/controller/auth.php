<?php
namespace mamur\services\controller;

use mamur\database\models as db;

  //   * $this->myClassInstance= new myClass(connection::get('contentDb'));
   
class auth extends abstractController{
    
   protected function dispatch($action=FALSE)
   {
       
      $db=db\connection::get('contentDb');
      $db->prepare("select * from item", array(\PDO::ATTR_CURSOR, \PDO::CURSOR_FWDONLY));
      
   
       print "in web<br>";
      // print "{$_SERVER['HTTP_X_MAMUR_AUTH_KEY']}<br />";
      // print "{$_SERVER['HTTP_X_MAMUR_API_ID']}<br />";
       print "{$_SERVER['REQUEST_METHOD']}<br />";
       print "{$_SERVER['QUERY_STRING']}<br />";
   
       print_r($_REQUEST);
   }
}