<?php
namespace mamur\dataDrivers\models\pdo;
 
/*
 * This class abstracts the basic level logic
 */

abstract class abstractPdo
{

    protected $db;
    protected $statement;
    protected $result;
    protected $validResult;
    
    /*
     * Create an instance using a connection to a
     * PDO dtatbase.
     * Dependency injection of db handle is via this
     * constructor.
     * eg
     * assuming myClass extentds db\abstactPdo:
     * 
     * use mamur\database\models;
     * $this->myClassInstance= new myClass(connection::get('contentDb'));
     */
            
    function __construct($db)
    { 
        $this->db=$db;
    }

    
      
    public function getOne($sql,$bindList=NULL)
    {
        if ($this->prepBindExec($sql,$bindList)){
            $this->result = $this->statement->fetchColumn(0);
        }
        return $this->resultValid;
    }
    
    public function getAll($sql,$bindList=NULL,$fetch=\PDO::FETCH_ASSOC)
    {
        if ($this->prepBindExec($sql,$bindList)){
            $this->result = $this->statement->fetchAll($fetch);
        }
        return $this->resultValid;
    }
   
    
    public function prepBindExec($sql,$bindList=NULL)
    {
        $this->result=FALSE;
        $this->resultValid=FALSE;
        $this->statement=$this->db->prepare($sql);
        foreach($bindList as $place => $bind){
            if(is_array($bind)){
                $this->statement->bindValue($place,$bind[0],$bind[1]);
            } else {
                $this->statement->bindValue($place,$bind);
            }
        }
        
        $this->resultValid=$this->statement->execute();
        return $this->resultValid;
    }
    
 /*
    function  query($sql,$variables="",$statement='default'){
//returns only one result if multi rows returned returns first value

   if(isset($db['handle']) && $db['handle']!==false ){
        if(is_array($variables)){
               $statm=$db['handle']->statementare($sql, array(PDO::ATTR_CURSOR, PDO::CURSOR_FWDONLY));
               $db['statement'][$statement]=$statm;
               $statm->execute($variables);

        }else{
                $statm=$db['handle']->query($sql);
        }
       if ($statm!==false){
          $result = $statm->fetchAll(PDO::FETCH_NUM);
          if(is_array($result) && count($result)==0){
               $ret=false;
          }elseif(!is_array($result) || !isset($result[0]) || count($result[0])!=1 ){
                $ret= $result;
          }else{
               $ret= $result[0][0];
          }
       }else{
          $ret=false;
       }
   }else{
       $db['status']='not open';
       trigger_error("query failed - not open", E_USER_ERROR);
       $ret= false;
   }
   return $ret;
    }

*/


}
