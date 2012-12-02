<?php

namespace mamur\database\models;
 

abstract class abstractPdo
{

    protected $db;
    
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
            
    function __construct($db){ 
        $this->db=$db;
    }


 
    function  query($sql,$variables="",$prep='default'){
//returns only one result if multi rows returned returns first value

   if(isset($db['handle']) && $db['handle']!==false ){
        if(is_array($variables)){
               $statm=$db['handle']->prepare($sql, array(PDO::ATTR_CURSOR, PDO::CURSOR_FWDONLY));
               $db['statement'][$prep]=$statm;
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




}
