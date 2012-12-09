<?php
namespace mamur\services\controller;


class item extends abstractController{
    
     
    
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
        print "in get";
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