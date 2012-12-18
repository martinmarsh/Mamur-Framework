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

class failedException extends Exception
{
    function __construct($message){      
       // print_r($this->resultInfo);
        print "*** FAILED ***".$message;
    }
}

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    
    protected $server;
    protected $uri;
    protected $headers;
    protected $result;
    protected $resultHeader;
    protected $resultInfo;
    protected $postFields;
    protected $item;
    protected $currentItem;
    protected $apiID;
    protected $apiSecret;
    protected $apiKey;
    protected $authKey;
    protected $userId;

    
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
       // $this->headers=array('Content-Type' => 'text/plain');
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
     * @Given /^my api id is "([^"]*)"$/
     */
    public function myApiIdIs($id)
    {
        $this->apiID=$id;
    }
    
    
    /**
     * @Given /^my authKey is "([^"]*)"$/
     */
    public function myAuthkeyIs($authKey)
    {
        $this->authKey=$authKey;
    }

    /**
     * @Given /^my userId is "([^"]*)"$/
     */
    public function myUseridIs($userId)
    {
        $this->userId = $userId;
    }


    /**
     * @Given /^my api secret is "([^"]*)"$/
     */
    public function myApiSecretIs($secret)
    {
        $this->apiSecret=$secret;
    }

    /**
     * @Given /^I set api id and api key in HTTP header$/
     */
    public function iSetApiIdAndApiKeyInHttpHeader()
    {
        $this->headers['X_MAMUR_API_ID']=$this->apiID;
        $this->headers['X_MAMUR_AUTH_KEY']=$this->authKey;
   
    }


    /**
     * @Given /^I set api id in header$/
     */
    public function iSetApiIdInHeader()
    {
        $this->headers['X_MAMUR_API_ID']=$this->apiID;
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
     * @Given /^I set HTTP header "([^"]*)" to "([^"]*)"$/
     */
    public function iSetHttpHeaderTo($header, $value)
    {
        $this->headers[$header]=$value;
    }

   
    /**
     * @Given /^I set POST content data$/
     */
    public function iSetPostContentData()
    {
        $this->postFields['content']=$this->item[$this->currentItem]['content'];
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 5.1; rv:17.0) Gecko/20100101 Firefox/17.0 FirePHP/0.7.1');
        if($requestType=="GET"){
        }elseif($requestType=="POST"){
            curl_setopt($ch, CURLOPT_POST,           1 );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postFields); 
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
           $headers[]=$header.": ".$value; 
        }                                   
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers); 

        $this->result=curl_exec ($ch);
        if($this->result===FALSE){
          
           throw new ErrorException("No Server response in iMakeARequest using $requestType -- you may want to check the test paramters");   
        }
        $this->resultInfo = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->resultHeader = substr($this->result, 0, $header_size);
        $this->result = substr($this->result, $header_size);
     // print_r($this->resultInfo ); 
        curl_close($ch);
      
     // print "\nresultInfo=";
     // print_r($this->resultInfo);
      // print "\n__\n"; 
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
            throw new failedException("Got response code ".$this->resultInfo['http_code']." when code in $response was expected\nReply Header:\n{$this->resultHeader}\n ");
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
            throw new failedException("The expected content was not recieved.\nReply Header:\n{$this->resultHeader}\nContent returned:\n$this->result\nReturned from Url: {$this->uri}\n");
           
        }
    }




//
}
