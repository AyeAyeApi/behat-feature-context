Feature: Test the indices of the Mock Api

  Scenario: Default
    Given the server at "mock" is started
    When I create a request
    And I send the request
    Then I expect the status code to be "200"
    And I expect the body to contain "data"
    And I expect the header "Content-Type" to be "application/json"

  Scenario: Coffee
    Given the server at "mock" is started
    When I create a request
    And I send the request to "/coffee"
    Then I expect the status code to be "418"
    And I expect "[data]" to be "I'm a teapot"
    And I expect the header "Content-Type" to be "application/json"

  Scenario Outline: Suffixes
    Given the server at "mock" is started
    When I create a request
    And I send the request to "version1<suffix>"
    Then I expect the status code to be "200"
    And I expect the body to contain "data"
    And I expect the header "Content-Type" to be "<content-type>"

    Examples:
      | suffix | content-type     |
      |        | application/json |
      | .json  | application/json |
      | .xml   | application/xml  |

  Scenario Outline: Headers
    Given the server at "mock" is started
    When I create a request
    And I set header "Accept" to "<accepts>"
    And I send the request
    Then I expect the status code to be "200"
    And I expect the body to contain "data"
    And I expect the header "Content-Type" to be "<content-type>"

    Examples:
      | accepts          | content-type     |
      | application/json | application/json |
      | application/xml  | application/xml  |

  Scenario Outline: Get parameters
    Given the server at "mock" is started
    When I create a "POST" request
    And I set parameter "parameter" to "<value>"
    And I send the request to "repeat-parameter"
    Then I expect the status code to be "200"
    And I expect "[data]" to be "<value>"

    Examples:
      | value |
      | foo   |
      | bar   |

  Scenario Outline: Body parameters
    Given the server at "mock" is started
    When I create a "POST" request
    And I set the body to:
    """
    {
      "parameter":"<value>"
    }
    """
    And I send the request to "repeat-parameter"
    Then I expect the status code to be "200"
    And I expect "[data]" to be "<value>"

    Examples:
      | value |
      | foo   |
      | bar   |

  Scenario Outline: Body parameters
    Given the server at "mock" is started
    When I create a "POST" request
    And I set the body to:
    """
      <?xml version="1.0" ?>
      <document>
        <parameter><value></parameter>
      </document>
    """
    And I send the request to "repeat-parameter"
    Then I expect the status code to be "200"
    And I expect "[data]" to be "<value>"

    Examples:
      | value |
      | foo   |
      | bar   |
