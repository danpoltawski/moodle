@lessonscaleproblems
Feature: Incorrect grade scale precission in lesson
    In order to demonstrate a bug
    As Dan
    I need use behat and idnumber mangling

    @javascript
    Scenario: Bugs are us
        Given the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C1 | 0 |
        And I log in as "admin"
        And I follow "Course 1"
        And I turn editing mode on
        And I add a "Lesson" to section "1" and I fill the form with:
            | Name | Test lesson name |
            | Description | Lesson description |
            | Type | Scale |
