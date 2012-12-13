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
    
public function saveContent($ref,$content,$status='new'){
    //Check if there is the same content
    $hash=sha1($content,true);
    $itemId=$this->data->getItemIdByHash($hash);
    $addIndex=TRUE;
    if ($itemId===FALSE ){
        //add new content data and update index which replaces
        //nay older content saved by $ref (the directory name)
        $itemId=$this->data->saveContent($hash,$content);
           
    } 
    
    //if status is publish or preview you then
    //old value must be changed to 'previous_' $status
    $this->data->indexItem($ref,$itemId,$status,0,'HTML');
   
    
    print "\n\n**********> $itemId   ******\n\n";
    
}   
    
public function getContent($ref,$status)
{
    return $this->data->getContent($ref,$status);
}     

}