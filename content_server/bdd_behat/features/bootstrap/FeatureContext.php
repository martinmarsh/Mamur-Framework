<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    
    protected $server;
    protected $uri;
    protected $headers;
    protected $result;
    protected $resultInfo;
    protected $postFields;
    protected $item;
    protected $currentItem;

    
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->headers=array('Content-Type' => 'text/plain');
        $this->postFields= array();
        $this->result = NULL;
        $this->resultInfo = NULL;
       
    }

 
    /**
     * @Given /^I have a server at "([^"]*)"$/
     */
    public function iHaveAServerAt($server)
    {
        $this->server=$server;
    }

  
    /**
     * @Given /^I have a test content "([^"]*)" I will refer to as "([^"]*)"  and which contains$/
     */
    public function iHaveATestContentIWillReferToAsAndWhichContains($address, $name, PyStringNode $content)
    {
        $this->item=array();
        $this->item[$name]['address']=$address;
        $this->item[$name]['content']=$content;
        $this->currentItem=$name;
     
    }


    /**
     * @Given /^I have item "([^"]*)"$/
     */
    public function iHaveItem($name)
    {
        $this->uri=$this->item[$name]['address'];
        $this->currentItem=$name;
     
    }

    /**
     * @Given /^I set header "([^"]*)" to "([^"]*)"$/
     */
    public function iSetHeaderTo($header, $value)
    {
        $this->headers[$header]=$value;
        //throw new PendingException();
    }

    
    /**
     * @When /^I make a "([^"]*)" request$/
     */
    public function iMakeARequest($requestType)
    {
        //assumes uri and headers have been set
        //
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->server.$this->uri );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_TIMEOUT, '10');
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        if($requestType=="GET"){
        }elseif($requestType=="POST"){
            $fields=array();
            curl_setopt($ch, CURLOPT_POST,           1 );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); 
        }elseif($requestType=="PUT"){
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch,CURLOPT_INFILE,"");
            curl_setopt($ch, CURLOPT_INFILESIZE, 1000);
            
        }elseif($requestType=="DELETE"){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }else{
           throw new Exception("unsupported test request method"); 
        }
        $headers=array();
        foreach($this->headers as $header=>$value){
           $headers[]=$header.":".$value; 
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers); 

        $this->result=curl_exec ($ch);
        if($this->result===FALSE){
          
           throw new ErrorException("No Server response in iMakeARequest using $requestType -- you may want to check the test paramters");   
        }
        $this->resultInfo = curl_getinfo($ch);
       
        curl_close($ch);
      
      //  print "resultInfo=";
      //  print_r($this->resultInfo);
        
        //throw new PendingException();
    }

    /**
     * @Given /^I prepend to the uri "([^"]*)"$/
     */
    public function iPrependToTheUri($prepend)
    {
       $this->uri=$prepend.$this->uri;
    }


   

    /**
     * @Then /^I should get response codes "([^"]*)"$/
     */
    public function iShouldGetResponseCodes($response)
    {
        $codes=explode(",",trim($response));
        if(!in_array($this->resultInfo['http_code'],$codes)){
            throw new ErrorException("**FAILED** Got response code ".$this->resultInfo['http_code']." when code in $response was expected");
        }
    }

    /**
     * @Given /^I should get my test content$/
     */
    public function iShouldGetMyTestContent()
    {
        //print "\n\nitem===";
        //print_r($this->item);
        //print "\nresult=".$this->result."<-\n";
       // print "\nexpected for ".$this->currentItem." is ".$this->item[$this->currentItem]['content']."<-";
        
        if($this->item[$this->currentItem]['content'] != $this->result){ 
            throw new ErrorException("**FAILED** The expected content from {$this->uri} was not recieved: $this->result");
           
        }
    }




//
}
