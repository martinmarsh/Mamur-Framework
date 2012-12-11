<?php
namespace mamur\services\controller;
use mamur\database\models as db;
use mamur\services\model as model;

class item extends abstractController{
    
    protected $item; 
    
    function __constuct(){
        //inject database dependncy into item model
        $this->item = new model\item(db\connection::get('contentDb'));
    }
    
    /* The post action expects the body to contain
     * the contents in xml. Without headers serivice must be first
     * eg /__service/content/__action/get/__authKey/key/file_reference
     * 
     */
    public function post(){
        print "in post";
    }
    
     
    /* The post action expects the body to contain
     * the contents in xml.
     */
    public function put(){
        print "in put";
    }
    
    
    /* The get action returns in the response body
     * the xml contents. 
     */
    public function get(){
        print "in item get";
        print_r($_SERVER);
       // $item = new item(db\connection::get('contentDb'));
    }
    
    /* The delete action removes the contents at fileref
     */
    public function delete(){
        print "in delete";
    }
    
    public function head(){
        print "in head";
        print "<BR>".$this->method;
       print "<BR>auth key =".$this->authKey;
       print "<BR>api id=".$this->apiId;
       print "<BR>file ref=".$this->fileRef;
       
       $config=\mamur\config::get();
       print_r($config->contentDb);
       
    }
    
    
   
}