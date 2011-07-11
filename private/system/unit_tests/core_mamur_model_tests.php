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
require($system.'/modules/core/models/mamurModel.php');

/**
 * 
 * Unit tests to validate core mamurModel class
 * Assumes phpunit has been installed and autoloads
 * @author martinmarsh@sygenius.com
 *
 */
class coreMamurModeltest extends PHPUnit_Framework_TestCase{
	
	public static $model;
	
	public static function setUpBeforeClass()
    {
        GLOBAL $user,$system,$mamur; 
		copy($user.'/test_configuration.xml',$user.'/configuration.xml');	
		$set=new mamurConfigData();
	   //$set->root=$root;
	   // $set->startFile=__FILE__;
		$set->mamur=$mamur;
        $set->system=$system;
        //$set->root=realpath($root);
        //$set->public=$publicDir;
        
        $set->user=$user;
     
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
        //error_reporting(-1);
       // register_shutdown_function('mamurShutDown');
		//set_error_handler('mamurErrorHandler');
		//set_exception_handler('mamurExceptionHandler');
		//if(strtolower($set->firePhp)=='yes'){
		//	require($set->mamur.'/firephp/FirePHP.class.php');
		//} 

		//Define the top most autoload function
		//In mamur you can use this function to add aditional autoloaders
		spl_autoload_register('mamurAutoClassLoad',false);
	/*
		//now load the required pre-loaded classes
		$classes=$config->classes;
		foreach($classes->getAll() as $name=>$class){ 
			$type=$class->type;
            $file=$set->$type."/modules/{$class->module}/{$class->mvc}/";
			if($class->load=='onstart'){
				if(!empty($class->file)){
					$file.=$class->file;
				}else{
					$file.="$name.php";
				
				}
				include_once($file);
			}
			
		}
		 */
    }
    public function testModel(){
       	$config=mamurConfig::getInstance();
		$set=$config->settings;	
		$this->assertEquals("new",$set->apiId);
        $this->assertEquals("new",$set->salt);
        self::$model=new mamurModel();
        $this->assertEquals("index.html",$set->homePage);
        $this->assertEquals(15,strlen($set->apiId));
        $this->assertEquals(117,strlen($set->salt));
        
        //check salt and api have been updated
        $configXML = new DOMDocument();
	    $configXML->load($set->user.'/configuration.xml');
	    $xpath = new DOMXPath($configXML);
	    $nodeList=$xpath->query("/configuration/settings/set[@salt]");
	    $this->assertEquals($set->salt,$nodeList->item(0)->getAttribute('salt'));
	    $nodeList=$xpath->query("/configuration/settings/set[@apiId]");
	    $this->assertEquals($set->apiId,$nodeList->item(0)->getAttribute('apiId'));
					
    }
    
   /**
     * @depends  testModel
     * Tests getting page content
     */
    function testSetUri(){
    	self::$model->setPageUri('/');
    	$this->assertEquals("",self::$model->getPageDir());
    	$this->assertEquals("index",self::$model->getPageName());
    	$this->assertEquals("html" ,self::$model->getPageExt());
    	self::$model->setPageUri('/testpage');
    	$this->assertEquals("",self::$model->getPageDir());
    	$this->assertEquals("testpage",self::$model->getPageName());
    	$this->assertEquals("html" ,self::$model->getPageExt());
    	self::$model->setPageUri('/test/');
    	$this->assertEquals("/test",self::$model->getPageDir());
    	$this->assertEquals("index",self::$model->getPageName());
    	$this->assertEquals("html" ,self::$model->getPageExt());
    	
    }
    
    /**
     * @depends  testModel
     * Tests getting page content
     */
    function testGetPageContent(){
    	self::$model->setPageUri('/');
    	$content=self::$model->getPageContent("main");
    	$expected="This is main index Content [?:article name=test1 :?][?:shared name=test2 type=mytype :?]";
    	$this->assertEquals($expected,$content);
    }
    
    /**
     * @depends  testModel
     * Tests getting shared content
     */
    function testGetSharedContent(){
        $content=self::$model->getSharedContent("testarticle","article");
    	$expected="This is test article Content";
    	$this->assertEquals($expected,$content);
    	
    	$content=self::$model->getSharedContent("testarticle","article","","");
    	$expected="This is test article Content";
    	$this->assertEquals($expected,$content);
    	
    	$content=self::$model->getSharedContent("my_article1","article","group1");
    	$expected="This is my article1 group1";
    	$this->assertEquals($expected,$content);
    	
    	$content=self::$model->getSharedContent("about_article2","article","group1/group2");
    	$expected="This is about article2 group1/group2";
    	$this->assertEquals($expected,$content);
    	
    	$content=self::$model->getSharedContent("about_article2","article","/group1/group2");
    	$expected="This is about article2 group1/group2";
    	$this->assertEquals($expected,$content);
    	
    	$content=self::$model->getSharedContent("about","article","/group1/group2/");
    	$expected="This is about article group1/group2";
    	$this->assertEquals($expected,$content);
    	
    	$content=self::$model->getSharedContent("testarticle_art1","article","/");
    	$expected="This is test article Content art1";
    	$this->assertEquals($expected,$content);
    	
    	$content=self::$model->getSharedContent("my_article1","article","group1");
    	$expected="This is my article1 group1";
    	$this->assertEquals($expected,$content);
    	
    	
    }
    
    /**
     * @depends  testModel
     * Tests getting shared content mapped by mapped by First uri section
     */
    function testMappedContentbyFirstSection(){
    	self::$model->setPageUri('/');
    	$content=self::$model->getSharedContent("testarticle","article","","byFirstSection");
    	$expected="This is test article Content";
    	$this->assertEquals($expected,$content);
    	
    	self::$model->setPageUri('/art1/');
    	$content=self::$model->getSharedContent("testarticle","article","","byFirstSection");
    	$expected="This is test article Content art1";
    	$this->assertEquals($expected,$content);
    	
    	self::$model->setPageUri('/art2/xxx/page1.html');
    	$content=self::$model->getSharedContent("testarticle","article","","byFirstSection");
    	$expected="This is test article Content art2";
    	$this->assertEquals($expected,$content);
    	
    	self::$model->setPageUri('/art3/page1.html');
    	$content=self::$model->getSharedContent("testarticle","article","","byFirstSection");
    	$expected="";
    	$this->assertEquals($expected,$content);
    	
    	//now group1
    	self::$model->setPageUri('/');
    	$content=self::$model->getSharedContent("my","article","group1","byFirstSection");
    	$expected="This is my article group1";
    	$this->assertEquals($expected,$content);
    	
    	self::$model->setPageUri('/article1/');
    	$content=self::$model->getSharedContent("my","article","group1","byFirstSection");
    	$expected="This is my article1 group1";
    	$this->assertEquals($expected,$content);
    	
    	self::$model->setPageUri('/article2/xxx/page1.html');
    	$content=self::$model->getSharedContent("my","article","group1","byFirstSection");
    	$expected="This is my article2 group1";
    	$this->assertEquals($expected,$content);
    	
    	self::$model->setPageUri('/article3/page1.html');
    	$content=self::$model->getSharedContent("my","article","group1","byFirstSection");
    	$expected="";
    	$this->assertEquals($expected,$content);
    }
    
}