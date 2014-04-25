@block @block_admin_bookmarks
Feature: Add a bookmark to an admin pages
  In order to speed up common tasks
  As an admin
  In need to add and access pages through bookmarks

  @javascript
  Scenario: Admin bookmarks block is installed by default
    Given I log in as "admin"
    And I collapse "Front page settings" node
    And I expand "Site administration" node
    And I expand "Users" node
    And I expand "Accounts" node
    When I follow "Browse list of users"
    Then "Admin bookmarks" "block" should exist

  # Test bookmark functionality using the "User profile fields" page as our bookmark.
  @javascript
  Scenario: Admin page can be bookmarked
    Given I log in as "admin"
    And I collapse "Front page settings" node
    And I expand "Site administration" node
    And I expand "Users" node
    And I expand "Accounts" node
    And I follow "User profile fields"
    When I follow "Bookmark this page"
    Then I should see "User profile fields" in the "Admin bookmarks" "block"

  @javascript
  Scenario: Admin page can be accessed through bookmarks block
    Given I log in as "admin"
    And I collapse "Front page settings" node
    And I expand "Site administration" node
    And I expand "Users" node
    And I expand "Accounts" node
    And I follow "Browse list of users"
    When I click on "User profile fields" "link" in the "Admin bookmarks" "block"
    # Verify that we are on the right page.
    Then I should see "User profile fields" in the "h2" "css_element"

  @javascript
  Scenario: Admin page can be removed from bookmarks
    Given I log in as "admin"
    And I collapse "Front page settings" node
    And I expand "Site administration" node
    And I expand "Users" node
    And I expand "Accounts" node
    And I follow "User profile fields"
    When I follow "Unbookmark this page"
    Then I should see "Bookmark deleted"
    And I wait to be redirected
    And I should not see "Browse list of users" in the "Admin bookmarks" "block"
