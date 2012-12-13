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

public function getItemIdByHash($hash){
    
    if($this->getOne(
            "select itemId from item where hash=:hash",
            array(
                ':hash'=>array($hash,\PDO::PARAM_LOB)
            ))){
        return $this->result;
    }
    
    return FALSE;
} 
public function getIndexId($ref){
    
    if($this->getOne(
            "SELECT indexId FROM 
                `index` 
                WHERE
                  dirRef=:dirRef",
            array(
                ':dirRef'=> $ref
            ))){
        return $this->result;
    }
    
    return FALSE;
}  




public function getItemId($ref){
    
    if($this->getOne(
            "SELECT itemId FROM 
                `index` as ix,
                `indexItemAssoc` as ia,
                 item as i
                WHERE
                  ia.indexId = ix.indexId AND
                  ia.itemId = i.itemId AND
                  ref=:ref",
            array(
                ':ref'=> $ref
            ))){
        return $this->result;
    }
    
    return FALSE;
}  

public function getItemHistory($ref){
    
    if($this->getAll(
            "SELECT itemId FROM index as ix,indexItemAssoc as ia,item as i
                WHERE
                  ia.indexId = ix.indexId AND
                  ia.itemId = i.itemId AND
                  ref=:ref",
            array(
                ':ref'=> $ref
            ))){
        return $this->result;
    }
    
    return FALSE;
}

public function getContent($ref,$status)
{
    
    if($this->getAll(
            "SELECT content FROM `index` as ix,
                indexHistory as ih,
                indexItemAssoc as ia,
                item as i
                WHERE
                  ih.indexId = ih.indexId AND
                  ia.indexHistoryId = ih.indexHistoryId AND
                  ia.itemId = i.itemId AND
                  dirRef=:ref AND
                  status = :status",
            array(
                ':ref'      => $ref,
                ':status'   => $status
            ))){
        
        //$this->statement->bindColumn(1, $this->result[0]);

        return $this->result[0]['content'];
    }
    
    return FALSE;
}

public function indexItem($dirRef,$itemId,$status='new',$userId=0,$type='HTML')
{
  
    $indexId=$this->getIndexId($dirRef);
    
    if($indexId===FALSE){
        $this->prepBindExec("INSERT INTO `index` 
        (dirRef,userId,type)
        VALUES 
        (:dirRef,:userId,:type)",
        array(
            ':dirRef' => $dirRef, 
            ':userId' => $userId,
            ':type'   => $type
            )
        );
    
        $indexId=$this->db->lastInsertId();   
        
    }
    
    
    $this->prepBindExec('UPDATE `indexHistory` SET status="old" WHERE
             indexId = :indexId AND status=:status',
        array(
            ':indexId'=> $indexId, 
            ':status' => $status,
            )
    );
   
    $this->prepBindExec("INSERT INTO `indexHistory` 
        (indexId,status,userId)
        VALUES 
        (:indexId,:status,:userId)",
        array(
            ':indexId' => $indexId, 
            ':status' => $status,
            ':userId' => $userId
            )
        );
    
    $indexHistoryId=$this->db->lastInsertId();
    
    $this->prepBindExec("INSERT INTO `indexItemAssoc`
            (indexHistoryId,itemId) VALUES (:indexHistoryId,:itemId)",
        array(
            ':indexHistoryId' => $indexHistoryId, 
            ':itemId'         => $itemId
            )
        );
    
    
}

public function getContentID($hash){
    $sql="select itemID from item,index where item.indexId=index.indexId and dirRef=:dirRef";
    $prep=$this->db->prepare($sql);
    $prep->execute(array(':dirRef' => $ref));
    return $prep->fetchColumn();
    
}      

}