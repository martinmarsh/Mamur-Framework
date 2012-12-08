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
    
    protected $uri;
    protected $headers;
    protected $result;
    protected $resultInfo;
    protected $postFields;
    
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
     * @Given /^I have an item "([^"]*)"$/
     */
    public function iHaveAnItem($item)
    {
        $this->uri=$item;
        
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

        curl_setopt($ch, CURLOPT_URL, "http://www.ardington.mamur/" );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_TIMEOUT, '3');
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
        if(!$this->result){
           throw new Exception("No Server response in iMakeARequest using $requestType -- you may wantt to check the test paramters");   
        }
        $this->resultInfo = curl_getinfo($ch);
       
        curl_close($ch);
       // print "result=";
       // print_r($this->result);
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
     * @Then /^I should get response code "([^"]*)" and content:$/
     */
    public function iShouldGetResponseCodeAndContent($response, PyStringNode $string)
    {
        $error="";
        if($response!=$this->resultInfo['http_code']){
            $error="Got response code {$this->resultInfo['http_code']} when $response was expected";
        }
    
        if($string != $this->result){
            if($error!=''){
                $error.=" also ";
            }
            $error.="The expected content was not recieved";
            
        }
        
        if($error){
            throw new Exception($error);
        }
    }




//
}
