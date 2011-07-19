<?php

$test=realpath(dirname(__FILE__));
$system=realpath($test."../..");
$mamur=realpath($test."../../..");
$user="$system/unit_tests/testuser";
print "System Directory=".$system."\n";
print "Mamur Directory=".$mamur."\n";
print "Test user directory=".$user."\n";

require($system.'/modules/core/models/mamurConfigData.php');
require($system.'/modules/core/models/mamurConfig.php');

/**
 * 
 * Unit tests to validate configuration classes
 * Assumes phpunit has been installed and autoloads
 * @author martinmarsh@sygenius.com
 * @package mamur
 * @subpackage test
 *
 */
class coreConfigTests extends PHPUnit_Framework_TestCase{
	
	public static function setUpBeforeClass()
    {
        GLOBAL $user; 
		copy($user.'/test_configuration.xml',$user.'/configuration.xml');	
    }
	
	/**
	 * 
	 * Test mamurConfigData class  methods work
	 */
    public function testSetConfigData(){
		$configData=new mamurConfigData();
		$this->assertEquals(0,count($configData->getAll()));
		$this->assertEmpty($configData->test);
		$this->assertEquals(false,$configData->test);
		$this->assertEquals(false,isset($configData->test));
		$configData->test="testData";
		$this->assertEquals(true,isset($configData->test));
		$this->assertEquals("testData",$configData->test);
		$configData->value1="testString2";
		$configData->value2="testString3";
		$this->assertEquals( 3,count($configData->getAll()));
		$myValues=$configData->getAll();
		$myValue=array_pop($myValues);
		$this->assertEquals($myValue, $configData->value2);
		$this->assertEquals($myValue,"testString3");
		$myValue=array_pop($myValues);
		$this->assertEquals($myValue, $configData->value1);
		$this->assertEquals($myValue,"testString2");	
	}
	
	 public function testConfigSetup(){
	 	GLOBAL $user; 
	 	$set=new mamurConfigData();
	    $set->var1="value1";
	    $set->var2="value2";
		$set->var3="value3";
		$set->user=$user;
	 	$config=mamurConfig::getInstance();
        $config->processConfig($set);
        //test that settings values passed have been added
        $this->assertEquals("value1",$config->settings->var1);
        $this->assertEquals("value2",$config->settings->var2);
        $this->assertEquals("value3",$config->settings->var3);
        $set=$config->settings;
        $this->assertEquals("value2",$set->var2);
        $set->var2="newvalue2";
        $this->assertEquals("newvalue2",$config->settings->var2);
	 }
	 
	 /**
	  * 
	  * Test settings XML values in Settings group
	  */
	 public function testConfigSettings(){
        $config=mamurConfig::getInstance();
        $set=$config->settings; 
        $this->assertEquals("new",$set->salt);
        $this->assertEquals("new",$set->apiId);
        $this->assertEquals("index.html",$set->homePage);
	 }
	 
	 	 
	 /**
	  * 
	  * Test settings XML values in Globals group
	  */
	 
	 public function testConfigGlobals(){
        $config=mamurConfig::getInstance();
        //some globals
        $this->assertEquals($config->globals->firstGlobal,"First global Value");
        $this->assertEquals($config->globals->test,"Test Global");
        $this->assertEquals($config->globals->lastGlobal,"Last global Value");
	 }   
     
      /**
	  * 
	  * Test settings XML values in Classes group
	  */
	 public function testConfigClasses(){ 
        $config=mamurConfig::getInstance();
       
        $this->assertEquals($config->classes->mamurController->load,"onstart");
        $this->assertEquals($config->classes->mamurController->type,"system");
        $this->assertEquals($config->classes->mamurController->module,"core");
        $this->assertEquals($config->classes->mamurController->mvc,"controllers");
        
        $this->assertEquals($config->classes->mamurModel->load,"onstart");
        $this->assertEquals($config->classes->mamurModel->type,"system");
        $this->assertEquals($config->classes->mamurModel->module,"core");
        $this->assertEquals($config->classes->mamurModel->mvc,"models");
        
        $this->assertEquals($config->classes->mamurView->load,"onstart");
        $this->assertEquals($config->classes->mamurView->type,"system");
        $this->assertEquals($config->classes->mamurView->module,"core");
        $this->assertEquals($config->classes->mamurView->mvc,"views");
      
        //check a defined view class
        $this->assertEquals($config->classes->mamurPlaceholders->load,"ondemand");
        $this->assertEquals($config->classes->mamurPlaceholders->type,"system");
        $this->assertEquals($config->classes->mamurPlaceholders->module,"core");
        $this->assertEquals($config->classes->mamurPlaceholders->mvc,"views");
        
        //check mvc type expands correctly for admin module
        $this->assertEquals($config->classes->mamurAdminController->load,"ondemand");
        $this->assertEquals($config->classes->mamurAdminController->type,"system");
        $this->assertEquals($config->classes->mamurAdminController->module,"admin");
        $this->assertEquals($config->classes->mamurAdminController->mvc,"controllers");
        
        $this->assertEquals($config->classes->mamurAdminModel->load,"ondemand");
        $this->assertEquals($config->classes->mamurAdminModel->type,"system");
        $this->assertEquals($config->classes->mamurAdminModel->module,"admin");
        $this->assertEquals($config->classes->mamurAdminModel->mvc,"models");
        
        $this->assertEquals($config->classes->mamurAdminView->load,"ondemand");
        $this->assertEquals($config->classes->mamurAdminView->type,"system");
        $this->assertEquals($config->classes->mamurAdminView->module,"admin");
        $this->assertEquals($config->classes->mamurAdminView->mvc,"views");
	 }

	  /**
	  * 
	  * Test settings XML values in Placeholders group
	  */
	 
	  public function testConfigPlaceholders(){
     
        $config=mamurConfig::getInstance();
       
        $this->assertEquals($config->placeholders->page_content->default,"generalPlaceholder");
        $this->assertEquals($config->placeholders->page_content->class,"mamurPlaceholders");
        $this->assertNull($config->placeholders->page_content->action);

        $this->assertEquals($config->placeholders->testholder1->class,"test2");
        $this->assertEquals($config->placeholders->testholder2->class,"test2");
        $this->assertEquals($config->placeholders->testholder2->action,"testaction2");
        $this->assertNull($config->placeholders->testholder2->default);
        $this->assertEquals($config->placeholders->testholder3->class,"test3");
        $this->assertEquals($config->placeholders->testholder3->default,"testactiondefault");

        $this->assertNull($config->placeholders->testholder3->action);
        $this->assertEquals($config->placeholders->testholder4->class,"test3");
        $this->assertEquals($config->placeholders->testholder3->default,"testactiondefault");
        $this->assertEquals($config->placeholders->testholder4->action,"testaction2");
        $this->assertEquals($config->placeholders->testholder3->default,"testactiondefault");
	  }
	  
	  
	  /**
	  * 
	  * Test config settings persistance
	  * and XML config updating
	  */
	 
	 public function testConfigPersist(){
     
        $config=mamurConfig::getInstance();
        $set=$config->settings;

        //now test persist to xml methods
        $testStr="ewfwefewfefewfewfewfewfewfeefewf7794375945797439347934793";
        $config->persistSetting('salt',$testStr);
        $this->assertEquals($config->settings->salt,$testStr);
        $config->persistSetting('salt',"should not work!");
        $this->assertEquals($config->settings->salt,$testStr);
        
        //new setting
        $config->persistSetting('newtestsset',"abcd");
        $this->assertEquals($config->settings->newtestsset,"abcd");
        $config->persistSetting('newtestsset',"deffgh");
        $this->assertEquals($config->settings->newtestsset,"deffgh");
        $config->persistSetting('newtestsset',"12345");
        $this->assertEquals($config->settings->newtestsset,"12345");
	 	$config->upDateConfig();
	 	
	 	$configXML = new DOMDocument();
	    $configXML->load($set->user.'/configuration.xml');
	    $xpath = new DOMXPath($configXML);
	    $nodeList=$xpath->query("/configuration/settings/set[@salt]");
	    $this->assertEquals($nodeList->item(0)->getAttribute('salt'),$testStr);
	    $nodeList=$xpath->query("/configuration/settings/set[@newtestsset]");
	    $this->assertEquals($nodeList->item(0)->getAttribute('newtestsset'),"12345"); 	 	
	 }
	
}