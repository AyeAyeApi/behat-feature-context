Feature: Test the indices of the Mock Api

  Scenario: Root controller indices
    Given the server at "mock" is started
    When I create a request
    And I send the request
    Then I expect the status code to be "200"
    And I expect the body to contain "data"
