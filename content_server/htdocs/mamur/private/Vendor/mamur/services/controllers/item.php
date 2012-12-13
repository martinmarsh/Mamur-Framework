<?php
namespace mamur\services\controllers;
use mamur\dataDrivers\models\pdo as db;

use mamur\services\models as model;

class item extends abstractController
{
    
    protected $item; 
    
    function __construct()
    {
        //implement parent construct
        parent::__construct();
        //inject database dependncy into item data driver model
        $this->item = new model\item();
     }
    
    /* The post action expects the body to contain post variables
     * content = content
     * other variables = metadata`
     * 
     */
    public function post()
    {
        /**
         * @todo may be need to check post for secuity
         */
        //save content in post parameter as content
        foreach($_POST as $meta=>$data){
            if($meta=='content'){
                $this->item->saveContent($this->fileRef,$data);
            }
        }
        //now save
        
    }
    
     
    /* The post action expects the body to contain
     * the contents in xml.
     */
    public function put()
    {
        print "in put";
    }
    
    
    /* The get action returns in the response body
     * the xml contents. 
     */
    public function get()
    {
        print "in item get";
       // print_r($_SERVER);
       // $item = new item(db\connection::get('contentDb'));
    }
    
    /* The delete action removes the contents at fileref
     */
    public function delete()
    {
        print "in delete";
    }
    
    public function head()
    {
        print "in head";
        print "<BR>".$this->method;
       print "<BR>auth key =".$this->authKey;
       print "<BR>api id=".$this->apiId;
       print "<BR>file ref=".$this->fileRef;
       
       $config=\mamur\config::get();
       print_r($config->contentDb);
       
    }
    
    
   
}