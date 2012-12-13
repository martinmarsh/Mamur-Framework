<?php
namespace mamur\services\models;
use mamur\dataDrivers\models\pdo as db;

//this selects the driver for item to interface
//to data store - no assumptions about data base
//type are made in this item class
use mamur\dataDrivers\models\pdo\sqlLite as dataDriver;


class item extends abstractData
{

public function __construct(){ 
    //inject database connection into item data driver model
    $this->data = new dataDriver\item(db\connection::get('contentDb'));
   
} 
    
public function saveContent($ref,$content){
    //Check if there is the same content
    $hash=sha1($content,true);
    $itemId=$this->data->getItemId($hash);   
    if($itemId===FALSE ){
        //add new content data and see if it replaces
        //older content saved by $ref (the directory name)
        $itemIdd=$this->data->saveContent($hash,$content);
    }
    //check content is referenced by $ref in index
    
   print "\n\n**********> $itemId   ******\n\n";
    
}   
    
     

}