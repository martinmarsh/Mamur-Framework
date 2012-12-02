<?php
namespace mamur\services\controller;

class www extends abstractController{
    
   protected function dispatch()
   {
       print "in web<br>";
       print "{$_SERVER['HTTP_X_MAMUR_AUTH_KEY']}<br />";
       print "{$_SERVER['HTTP_X_MAMUR_API_ID']}<br />";
       print "{$_SERVER['REQUEST_METHOD']}<br />";
       print "{$_SERVER['QUERY_STRING']}<br />";
   
       print_r($_REQUEST);
   }
}