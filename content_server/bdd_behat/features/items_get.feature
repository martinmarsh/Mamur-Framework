# features/items_get.feature
Feature: items_get
  In order to get a known item  
  Using the API  item service
  I need to receive the item in reply to a get request


 Background:
    Given I have a server at "http://www.mamur2.local"
    And my api id is "A00001.mamur2.local"
    And my api secret is "cn4&in2w1$FaP£vS4"
    And my authKey is "testing1234567890"
    And my userId is "1"
    And I have a test content "/test/content1" I will refer to as "content1"  and which contains
        """
<p>This my test item content which may can contain HTML</p>
<p>Or any other format I choose</p> 
        """ 

Scenario: Before I can get content I have to post some
  Given I have item "content1"
    And I set HTTP header "X_MAMUR_SERVICE" to "item"
    And I set HTTP header "x-insight" to "activate"
    And I set api id and api key in HTTP header
    And I set POST content data
   When I make a "POST" request
  Then I should get response codes "200,201,202,302,304"
   

 
Scenario: Get test item's content using only headers
  Given I have item "content1"
    And I set HTTP header "X_MAMUR_SERVICE" to "item"
    And I set api id in header
    When I make a "GET" request
  Then I should get response codes "200,201,202,302,304"
    And I should get my test content
 

Scenario:  Get test item's content without headers
  Given I have item "content1"
    And I prepend to the uri "/__service/item/__method/get"
  When I make a "GET" request
  Then I should get response codes "200,201,202,302,304"
    And I should get my test content

Scenario:  Get test item's content with only service set in uri
  Given I have item "content1"
    And I prepend to the uri "/__service/item"
    And I set api id in header
  When I make a "GET" request
  Then I should get response codes "200,201,202,302,304"
    And I should get my test content


