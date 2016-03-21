Feature: Visit Google

  Scenario: Using prescribed values
    Given I create a "GET" request
    When I send the request to "/"
    Then I expect the status code to be 200
    And I expect the body to contain "<!doctype html>"

  Scenario: Using default values
    Given I create a request
    When I send the request
    Then I expect the status code to be 200
    And I expect the body to contain "<!doctype html>"
