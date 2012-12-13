<?php
namespace mamur\dataDrivers\models\pdo\sqlLite;
use mamur\dataDrivers\models\pdo as db;

/* This is a pdo SQLite interface for services
 * item model.
 * This class provides sql Lite abstraction at a
 * level above PDO which abstracts the database interface
 */
class item extends db\abstractPdo
{

public function saveContent($hash,$content){
    
    if ($this->prepBindExec(
            $sql="insert into item (hash,content)
                VALUES (:hash,:content)",
            array(
              ':hash'=>array($hash,\PDO::PARAM_LOB),
              ':content'=>array($content,\PDO::PARAM_LOB)
            )
       )
    ){   
        return $this->db->lastInsertId();
    } 
    return FALSE;
   
} 

public function getItemId($hash){
    
    if($this->getOne(
            "select itemId from item where hash=:hash",
            array(
                ':hash'=>array($hash,\PDO::PARAM_LOB)
            ))){
        return $this->result;
    }
    
    return FALSE;
}      

    
public function getContentID($hash){
    $sql="select itemID from item,index where item.indexId=index.indexId and dirRef=:dirRef";
    $prep=$this->db->prepare($sql);
    $prep->execute(array(':dirRef' => $ref));
    return $prep->fetchColumn();
    
}      

}