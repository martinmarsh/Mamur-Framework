<?php

namespace mamur\dataDrivers\models\pdo;

/*
 * Connect is a static class to create, store
 * and retrieve database connections
 * It is shared by all pdo dataDrivers
 */
class connection
{

    protected static $dbNames;
    
    /*
     * The first get opens a connection and
     * returns the pdo database class.
     * Use connection::get('databaseName') to
     * get and  open pdo connection instance
     */
    static function get($dbName)
    {
       if(!isset(self::$dbNames[$dbName])){
            self::$dbNames[$dbName]=self::open($dbName);     
       }
       return self::$dbNames[$dbName];
    }
    
    final static private function open($dbName){ 
        //raise exception if open
               print "name=$dbName";
        if (isset(self::$dbNames[$dbName])){
            throw new \Exception("Databse '$dbName' is already open");
        }
        
        $config=\mamur\config::get();
        if (empty($config->databases[$dbName])){
            throw new \Exception("Unknown databse '$dbName'; the Name and properties should be defined in config.");
        } else {
            $setup=$config->databases[$dbName];    
        }
        $db=false;
 
        if (!empty($setup['dsn'])){
           $dsn=str_replace(array(
                                '%%database%%',
                                '%%mode%%' ),
                            array(
                                $config->databaseDir,
                                $config->databaseMode ),
                            $setup['dsn'] ); 
        } else {
           throw new \Exception("No dsn given in config. for databse '$dbName'");
   
        }
        try{
            if (isset($setup['options']) && 
                isset($setup['user']) && 
                isset($setup['password'])
                ){
                $db = new \PDO($dsn,
                                        $setup['user'],
                                        $setup['password'],
                                        $setup['options']    
                                      );
            } elseif (
                isset($setup['user']) &&
                isset($setup['password'])
                ){
                $db = new \PDO($dsn,
                                        $setup['user'],
                                        $setup['password']   
                                      );                
            } else {
                $db = new \PDO($dsn);    
            } 
            
        } catch (PDOException $e) {
            throw new \Execption("Connection to '$dbName' with DSN: $dsb failed: " . $e->getMessage());
        }
             
       if($db){
           //Use exception based error reporting
           $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
           self::$dbNames[$dbName]=$db;
       } else  {
           throw new \Exception("Connection to '$dbName' failed: Check config. parameters");
       } 
        return $db;
    }
    
    
    
    static function close($dbName)
    {
       if(isset(self::$dbNames[$dbName])){
            self::$dbNames[$dbName]=null;
            unset(self::$dbNames[$dbName]);
       }
    }
}
