<?php

$test=realpath(dirname(__FILE__));
$system=realpath($test."../..");
$mamur=realpath($test."../../..");
$user="$system/unit_tests/testuser";
print "System Directory=".$system."\n";
print "Mamur Directory=".$mamur."\n";
print "Test user directory=".$user."\n";

require($system.'/modules/core/models/mamurDataObject.php');


/**
 * 
 * Unit tests to validate configuration classes
 * Assumes phpunit has been installed and autoloads
 * @author martinmarsh@sygenius.com
 * @package mamur
 * @subpackage test
 *
 */
class dataObjectTests extends PHPUnit_Framework_TestCase{
	

	
	public static function setUpBeforeClass()
    {
      
    }
	
	/**
	 * 
	 * Test dataobject constuct
	 */
    public function testDataObjectConstruct(){
		$data=new mamurDataObject();
	
    	//on contruction 1st record exists and getAll is not empty
    	//but the current record 0 is null
		$this->assertEquals(1,count($data->getAll()));
		
		
		$this->assertEquals(0,count($data->getRecord()));
		$this->assertEmpty($data->getRecord());
		
		$this->assertEquals(0,$data->getCurrentRecordNumber());
	
		$this->assertEquals('none',$data->getStatus('data'));
		$this->assertEquals('no',$data->getStatus('read'));
		$this->assertEquals('no',$data->getStatus('save'));
    }
    
    
    /**
     * 
     * 
     * Test setting items and reading them
     */
    public function testDataObjectSetting(){
    	
    	$data=new mamurDataObject();
		$x=$data->myvar=1234;
		$this->assertEquals(1234,$data->myvar);
		$this->assertEquals(1234,$x);
		$this->assertEquals('modified',$data->getStatus('data'));
		
		
		$this->assertEquals(1,count($data->getAll()));
		$this->assertEquals(1,count($data->getRecord()));
		
		$data->mystring="hello";
		$this->assertEquals('hello',$data->mystring);
		$this->assertEquals(1,count($data->getAll()));
		$this->assertEquals(2,count($data->getRecord())); 
		$this->assertEquals('modified',$data->getStatus('data'));
		
		$this->assertTrue(isset($data->mystring));
		$this->assertFalse(isset($data->myother));
		$this->assertEmpty($data->myother);
		
		unset($data->mystring);
		$this->assertFalse(isset($data->mystring));
		$this->assertEmpty($data->mystring);
		$this->assertEquals(1,count($data->getRecord())); 
    }
    
    
       
    /**
     * Test Attribute settings
     */
 	 public function testDataObjectAttributes(){	
 		$data=new mamurDataObject();
 		$this->assertNull($data->getAttribute('myattrib'));
 		$data->setAttribute('myattrib',"attribute value 1");
 		$this->assertEquals("attribute value 1",$data->getAttribute('myattrib'));
    	$this->assertEquals('modified',$data->getStatus('data'));
    }
    
    
    
    /**
     * 
     * Test record functions
     */
    public function testDataObjectRecords(){
    	$data=new mamurDataObject();
    	$this->assertEquals('none',$data->getStatus('data'));
    	$this->assertEquals(1,count($data->getAll()));
    	$this->assertEquals(0,count($data->getRecord()));
    	$this->assertEmpty($data->getRecord());
    	
    	$this->assertFalse($data->next());
    	$this->assertEquals(0,$data->getCurrentRecordNumber());
    	
    	$this->assertFalse($data->back());
    	$this->assertEquals(0,$data->getCurrentRecordNumber());
    	
    	//last returns current record number
    	$this->assertEquals(0,$data->last());
    	$this->assertEquals(0,$data->getCurrentRecordNumber());
    	
    	//add a new blank record returning new record pointer
    	$this->assertEquals(1,$data->appendRecord());
    	$this->assertEquals('modified',$data->getStatus('data'));
    	$this->assertEquals(2,count($data->getAll()));
    	$this->assertEquals(0,count($data->getRecord()));
    	$this->assertEquals(1,$data->getCurrentRecordNumber());
    	
    	$this->assertEquals(0,$data->deleteRecord());
    	$this->assertEquals('modified',$data->getStatus('data'));
    	$this->assertEquals(1,count($data->getAll()));
    	$this->assertEquals(0,count($data->getRecord()));
    	$this->assertEquals(0,$data->getCurrentRecordNumber());
    	
    
    }
    
     /**
     * 
     * Deleting 1st Record when  1 or less record exists
     */
    public function testDataObjectDelete1stRecord(){
    	$data=new mamurDataObject();
    	$this->assertEquals('none',$data->getStatus('data'));
    	$this->assertEquals(1,count($data->getAll()));
    	$this->assertEquals(0,count($data->getRecord()));
    	$this->assertEmpty($data->getRecord());
    	
    	$this->assertEquals(0,$data->deleteRecord());
    	$this->assertEquals('modified',$data->getStatus('data'));
    	$this->assertEquals(0,count($data->getAll()));
    	$this->assertEquals(0,count($data->getRecord()));
    	$this->assertEmpty($data->getRecord());
    	$this->assertEmpty($data->getAll());
    	
    	//try deleting current
    	$this->assertFalse($data->deleteRecord());
    	//try deleting current record 9
    	$this->assertFalse($data->deleteRecord(9));
    	
    	//try adding variable to empty object
    	$data->myvar=1234;
		$this->assertEquals(1234,$data->myvar);
		$this->assertEquals(1,count($data->getAll()));
    	$this->assertEquals(1,count($data->getRecord()));
    	
    	$this->assertEquals(0,$data->deleteRecord(0));
    	$this->assertEquals(0,count($data->getAll()));
    	$this->assertEquals(0,count($data->getRecord()));	
    }	
  
    /**
     * 
     * Record selection tests
     */
     public function testDataObjectSelectRecord(){
     	$data=new mamurDataObject();
     	$data->abc='123';
     	$data->def='45677';
     	$this->assertEquals(1,$data->appendRecord(array('abc'=>'435gdf','def'=>'hgfhgf')));
	    $this->assertEquals(2,$data->appendRecord(array('abc'=>'54645gfh','def'=>'rt','xyz'=>'qerty')));
     	$this->assertEquals(3,$data->appendRecord(array('abc'=>'rttr','def'=>'trtrh')));
     	$this->assertEquals(3,$data->getCurrentRecordNumber());
     	$this->assertEquals(array('abc'=>'rttr','def'=>'trtrh'),$data->getRecord());
     	$this->assertEquals(2,$data->back());
     	$this->assertEquals(2,$data->getCurrentRecordNumber());
     	$this->assertEquals(array('abc'=>'54645gfh','def'=>'rt','xyz'=>'qerty'),$data->getRecord());
	    $data->new="newdata";
	    $this->assertEquals(array('abc'=>'54645gfh','def'=>'rt','xyz'=>'qerty','new'=>'newdata'),$data->getRecord());
		$this->assertEquals(2,$data->getCurrentRecordNumber());
	    $this->assertEquals(3,$data->next());
     	$this->assertEquals(3,$data->getCurrentRecordNumber());
        $this->assertEquals(array('abc'=>'rttr','def'=>'trtrh'),$data->getRecord());
        $this->assertFalse($data->next());
        $this->assertEquals(3,$data->getCurrentRecordNumber());
        $this->assertFalse($data->next());
        $this->assertFalse($data->next());
        $this->assertFalse($data->setCurrentRecordNumber(4));
        $this->assertEquals(3,$data->getCurrentRecordNumber());
        $this->assertEquals(3,$data->setCurrentRecordNumber(3));
        $this->assertEquals(3,$data->getCurrentRecordNumber());
        $this->assertEquals(1,$data->setCurrentRecordNumber(1));
        $this->assertEquals(1,$data->getCurrentRecordNumber());
        $this->assertEquals(array('abc'=>'435gdf','def'=>'hgfhgf'),$data->getRecord());
        $this->assertEquals(0,$data->back());
        $this->assertEquals(0,$data->setCurrentRecordNumber(0));
        $this->assertEquals('123',$data->abc);
        $this->assertFalse($data->back());
        $this->assertEquals('45677',$data->def);
        $this->assertFalse($data->back());
        $this->assertFalse($data->back());
        $this->assertEquals('45677',$data->def);
        $this->assertEquals('123',$data->abc);
        $this->assertEquals(0,$data->getCurrentRecordNumber()); 
        $this->assertEquals(3,$data->last());
        $this->assertEquals(3,$data->getCurrentRecordNumber()); 
        $this->assertEquals(array('abc'=>'rttr','def'=>'trtrh'),$data->getRecord());
        
     }
     
    
    
}