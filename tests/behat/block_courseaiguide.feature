@block @block_courseaiguide
Feature: Add and safely configure the Course AI Guide block
  In order to expose a course guide only after secure setup
  As an authorised course manager
  I need course-only placement and fail-closed defaults

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: A manager adds one disabled course block
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add the "Course AI Guide" block
    Then I should see "Course AI Guide"
    And I should see "Disabled" in the "Course AI Guide" "block"
    And I should see "The course guide is not available yet." in the "Course AI Guide" "block"

  @javascript
  Scenario: The participant entry point stays closed before provider and index readiness
    Given I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should not see "Ask the course guide"
