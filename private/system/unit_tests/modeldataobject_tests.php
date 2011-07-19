<?php

$test=realpath(dirname(__FILE__));
$system=realpath($test."../..");
$mamur=realpath($test."../../..");
$user="$system/unit_tests/testuser";
$webBaseDir=realpath("$system/unit_tests/testuser/webdir");
print "System Directory=".$system."\n";
print "Mamur Directory=".$mamur."\n";
print "Test user directory=".$user."\n";
print "Web directory=".$webBaseDir."\n";

require($system.'/modules/core/models/mamurConfigData.php');
require($system.'/modules/core/models/mamurConfig.php');
require($system.'/modules/core/models/mamurDataObject.php');
require($system.'/modules/core/models/mamurModel.php');


/**
 * 
 * Unit tests to validate configuration classes
 * Assumes phpunit has been installed and autoloads
 * @author martinmarsh@sygenius.com
 * @package mamur
 * @subpackage test
 *
 */
class modelDataObjectTests extends PHPUnit_Framework_TestCase{
	
	public static $model;
	
	public static function setUpBeforeClass()
    {
         
	    GLOBAL $user,$system,$mamur,$webBaseDir; 
		copy($user.'/test_configuration.xml',$user.'/configuration.xml');	
		$set==new mamurConfigData();
		
		$set->mamur=$mamur;
        $set->system=$system;
        $set->user=$user;
        $set->webBaseDir=$webBaseDir;
        $set->plugins="$mamur/plugins";
        $set->logDir=$set->user."/errorlogs";
        $set->build=$set->user."/build";
        $set->uri="/";  //as in uri but with / if just domain name eg / but slash not always added to end ie /xxxx 
        $set->host="www.website.com";   //defines host domain

		list($set->start_usec, $set->start_sec)= explode(" ", microtime());
	
		//get configuration class
		$config=mamurConfig::getInstance();
        $config->processConfig($set);
        
        //use object methods with $set from now on:
        $set=$config->settings;
       
		//Define the top most autoload function
		//In mamur you can use this function to add aditional autoloaders
		spl_autoload_register('testAutoClassLoad',false);
		
	
		
        self::$model=new mamurModel();
        self::$model->setUpSession();
	
    }
	
	/**
	 * 
	 * Test getDataObject
	 */
    public function testGetDataObject(){
		//get a new data object from model
	    $data=self::$model->getDataObject('testObject');
    	
		$this->assertEquals(1,count($data->getAll()));
		$data->testVar='123';
		$this->assertEquals('modified',$data->getStatus('data'));
		//try getting same data object again
		$data2=self::$model->getDataObject('testObject');
		$this->assertEquals('123',$data2->testVar);
		$this->assertEquals('modified',$data2->getStatus('data'));
		
		//data and data2 point to same object add new var to
		//data2 and see if present in data
		$data2->testVar2='abcdef';
		$this->assertEquals('abcdef',$data->testVar2);
		
    }
    
    /**
	 * 
	 * Test saveDataObjects
	 */
    public function testSaveDataObjects(){
    	//test object persisted
    	$data=self::$model->getDataObject('testObject');
    	$data->persist();
    	//testObjectA not persisted
    	$dataA=self::$model->getDataObject('testObjectA');
    	$dataA->testVar3='var3 - 123';
    	$dataA->tesVar4='var4 - 123';
    	//testObjectB  persisted
    	$dataB=self::$model->getDataObject('testObjectB');
    	$dataB->testVar3='var3 - 123';
    	$dataB->testVar5='var5 - 123';
    	$dataB->persist();
    	
    	$this->assertEquals('persist',$data->getStatus('save'));
    	$t1=self::$model->pageTime();
    	self::$model->saveDataObjects();
    	$t2=self::$model->pageTime();
    	$t=$t2-$t1;
    	print "\nTime to save objects = $t ms\n";
    	
    	$_COOKIE['session']=self::$model->getEncryptedSession();
    	$t3=self::$model->pageTime();
    	$t=$t3-$t2;
    	print "\nTime to save encrypt default 256= $t ms\n";
    	
    	print "\n\ncookie=".$_COOKIE['session']."\n\n";
    	print "cookie decoded=";
    	print_r(unserialize(base64_decode($_COOKIE['session'])));
    	print "\n\n";
    	$this->assertLessThan(1000,strlen($_COOKIE['session']));
    	//test that a new model will read the data back
    	$model2=new mamurModel();
    	$model2->setUpSession();
    	$dataNew=$model2->getDataObject('newObject');
    	$data2=$model2->getDataObject('testObject');
    	
    	//data & dataB will have data status set to saved
    	//when read so set this status to allow compare to work
    	$data->setStatus('data','saved');
    	$dataB->setStatus('data','saved');
    	
    	$this->assertEquals($data,$data2);
    	//test object A was not persisted so equals new object
    	$data2=$model2->getDataObject('testObjectA');
    	$this->assertEquals($dataNew,$data2);
    	$data2=$model2->getDataObject('testObjectB');
    	$this->assertEquals($dataB,$data2);
    	$this->assertEquals('var3 - 123',$data2->testVar3);
    	$this->assertEquals('var5 - 123',$data2->testVar5);
    }
    
     
      /**
	 * 
	 * Test saveDataObjects - no cipher
	 */
    public function testSaveDataObjectsNone(){
    	$config=mamurConfig::getInstance();
    	$set=$config->settings;
    	$data=self::$model->getDataObject('testObject');
    	$set->cipher="none";
    	
    	self::$model->saveDataObjects();
    	
    	$t1=self::$model->pageTime();
    	$_COOKIE['session']=self::$model->getEncryptedSession();
    	$t2=self::$model->pageTime();
    	$t=$t2-$t1;
    	print "\nTime to save encrypt None= $t ms\n";
    	
    	print "\n\ncookie=".$_COOKIE['session']."\n\n";
    	print "cookie decoded=";
    	print_r(unserialize(base64_decode($_COOKIE['session'])));
    	print "\n\n";
    	$this->assertLessThan(1000,strlen($_COOKIE['session']));
    	//test that a new model will read the data back
    	$model2=new mamurModel();
    	$model2->setUpSession();
    	$dataNew=$model2->getDataObject('newObject');
    	$data2=$model2->getDataObject('testObject');
    	$this->assertEquals($data,$data2);
    	//test object A was not persisted so equals new object
    	$data2=$model2->getDataObject('testObjectA');
    	$this->assertEquals($dataNew,$data2);
    	$data2=$model2->getDataObject('testObjectB');
    	$this->assertEquals('var3 - 123',$data2->testVar3);
    	$this->assertEquals('var5 - 123',$data2->testVar5);
    	
    }
    

     /**
	 * 
	 * Test saveDataObjects - tripleDes
	 */
    public function testSaveDataObjects3Des(){
    	$config=mamurConfig::getInstance();
    	$set=$config->settings;
    	$data=self::$model->getDataObject('testObject');
    	$set->cipher="tripledes";
    	self::$model->saveDataObjects();
    	
    	$t1=self::$model->pageTime();
    	$_COOKIE['session']=self::$model->getEncryptedSession();
    	$t2=self::$model->pageTime();
    	$t=$t2-$t1;
    	print "\nTime to save encrypt 3DES= $t ms\n";
    	
    	print "\n\ncookie=".$_COOKIE['session']."\n\n";
    	print "cookie decoded=";
    	print_r(unserialize(base64_decode($_COOKIE['session'])));
    	print "\n\n";
    	$this->assertLessThan(1000,strlen($_COOKIE['session']));
    	//test that a new model will read the data back
    	$model2=new mamurModel();
    	$model2->setUpSession();
    	$dataNew=$model2->getDataObject('newObject');
    	$data2=$model2->getDataObject('testObject');
    	$this->assertEquals($data,$data2);
    	//test object A was not persisted so equals new object
    	$data2=$model2->getDataObject('testObjectA');
    	$this->assertEquals($dataNew,$data2);
    	$data2=$model2->getDataObject('testObjectB');
    	$this->assertEquals('var3 - 123',$data2->testVar3);
    	$this->assertEquals('var5 - 123',$data2->testVar5);	
    }
    
   /**
	 * 
	 * Test saveDataObjects - 2fish
	 */
    public function testSaveDataObjects2fish(){
    	$config=mamurConfig::getInstance();
    	$set=$config->settings;
    	$data=self::$model->getDataObject('testObject');
    	$set->cipher="twofish";
    	self::$model->saveDataObjects();
    	
    	$t1=self::$model->pageTime();
    	$_COOKIE['session']=self::$model->getEncryptedSession();
    	$t2=self::$model->pageTime();
    	$t=$t2-$t1;
    	print "\nTime to save encrypt 2fish= $t ms\n";
    	
    	print "\n\ncookie=".$_COOKIE['session']."\n\n";
    	print "cookie decoded=";
    	print_r(unserialize(base64_decode($_COOKIE['session'])));
    	print "\n\n";
    	$this->assertLessThan(1000,strlen($_COOKIE['session']));
    	//test that a new model will read the data back
    	$model2=new mamurModel();
    	$model2->setUpSession();
    	$dataNew=$model2->getDataObject('newObject');
    	$data2=$model2->getDataObject('testObject');
    	$this->assertEquals($data,$data2);
    	//test object A was not persisted so equals new object
    	$data2=$model2->getDataObject('testObjectA');
    	$this->assertEquals($dataNew,$data2);
    	$data2=$model2->getDataObject('testObjectB');
    	$this->assertEquals('var3 - 123',$data2->testVar3);
    	$this->assertEquals('var5 - 123',$data2->testVar5);	
    }
    
}

/**
 *  Autoload Class function registerred at start up
 * Loads a class configured in confguration.xml
 * @author martinmarsh@sygenius.com
 * @ignore
 */
function testAutoClassLoad($name){
   $config=mamurConfig::getInstance();
   $classes=$config->classes;	
   if (isset($classes->$name)){
   	   $type=$classes->$name->type;
   	   $file=$config->settings->$type."/modules/{$classes->$name->module}/{$classes->$name->mvc}/$name.php";
   	   require_once($file);
   }
}


