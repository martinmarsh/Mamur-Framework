# features/items_get.feature
Feature: items_get
  In order to get a known item  
  Using the API  item service
  I need to receive the item in reply to a get request

Scenario: Get an existing item using only headers
  Given I have an item "test/content1"
  And I set header "HTTP_X_MAMUR_SERVICE" to "__item"
  And I set header "HTTP_X_MAMUR_AUTH_KEY" to "cn4&in2w1$FaP£vS4"
  And I set header "HTTP_X_MAMUR_API_ID" to "A00001.mamur2.local"
  When I make a "GET" request
  Then I should get response code "200" and content:
    """
    test result
    content 1
    """

Scenario: Get some content without using headers
  Given I have an item "test/content1"
  And I prepend to the uri "__service/item/__auth_key/cn4&in2w1$FaP£vS4/__api_id/A00001.mamur2.local/"
  When I make a "GET" request
  Then I should get response code "200" and content:
    """
    test result
    content 1
    """