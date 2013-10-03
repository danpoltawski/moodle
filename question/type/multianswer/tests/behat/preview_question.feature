@core @qtype_multianswer
Feature: A teacher can add a Embeded answer question and preview it
  In order to ensure the Embeded answer questions are properly created
  As a teacher
  I need to preview the cloze question

  @javascript
  Scenario: Add a cloze question
    Given the following "users" exists:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
    And the following "courses" exists:
      | fullname | shortname | format |
      | Course 1 | C1 | weeks |
    And the following "course enrolments" exists:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I add a "Embedded answers (Cloze)" question filling the form with:
      | Question name | Test cloze question name |
      | Question text | {1:SHORTANSWER:=Berlin} is the capital of Germany. |
    When I click on "Preview" "link" in the "Test cloze question name" table row
    And I switch to "questionpreview" window
    And I fill the moodle form with:
      | Whether correct | Shown |
      | How questions behave | Deferred feedback |
    And I press "Start again with these options"
    Then I should see "Not yet answered"
    And I fill in "Answer" with "Berlin"
    And I press "Submit and finish"
    Then I should see "Correct"
    When  I press "Start again"
    Then I should see "Not yet answered"
    And I fill in "Answer" with "moodle"
    And I press "Submit and finish"
    Then I should see "Incorrect"
    When  I press "Start again"
    Then I should see "Not yet answered"
    And I press "Fill in correct responses"
    And the "Answer" field should match "Berlin" value
    And I press "Close preview"
    And I switch to the main window
