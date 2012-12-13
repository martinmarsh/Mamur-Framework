<?php
namespace mamur\services\models;

 
/*
 * This class abstracts the basic level logic
 */

abstract class abstractData
{

    private $data;
    
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
    



}
