<?php
namespace mamur\services\model;
use mamur\database\models as dataModel;

class item extends dataModel\abstractPdo
{

public function save($ref,$content){
    
    $sql="insert into item (indexId,content)
            VALUES (:indexId,:content)";
    $prep=$this->db->prepare($sql);
   
    $value=1;
    $prep->bindValue(':indexId',$value,\PDO::PARAM_INT);
    $prep->bindValue(':content',$content,\PDO::PARAM_LOB);
    $prep->execute();
     
}   
    
public function getitemId($ref){
    $sql="select itemID from item,index where item.indexId=index.indexId and dirRef=:dirRef";
    $prep=$this->db->prepare($sql);
    $prep->execute(array(':dirRef' => $ref));
    return $prep->fetchColumn();
    
}      

}