Feature: Visit Google

  Scenario:
    Given I create a "GET" request
    When I send the request to "/"
    Then I expect the status code to be 200
