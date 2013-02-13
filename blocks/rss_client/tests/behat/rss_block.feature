@block_rss
Feature: Add and configure RSS block
  In order to add more functionality to pages
  As a teacher
  I need to add rss feeds

  Background:
    Given the following "courses" exists:
      | fullname | shortname | format |
      | RSS Test Course | RSSTest | topics |
    And I log in as "admin"
    And I am on homepage
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Blocks" node
    And I follow "Remote RSS feeds"
    And I follow "Add/edit feeds"
    And I press "Add a new feed"
    Then I should see "Add a new feed"
    When I fill in "Feed URL" with "http://download.moodle.org/unittest/behat-rsstest.xml"
    And I fill in "Custom title (leave blank to use title supplied by feed):" with "$NASTYSTRING1"
    And I press "Add a new feed"
    Then I should see "$NASTYSTRING1"
    And I should see "http://download.moodle.org/unittest/behat-rsstest.xml"
    # Add block (unconfigured) to the homepage, to simplify next tests:
    When I am on homepage
    And I follow "RSS Test Course"
    And I turn editing mode on
    When I add the "Remote RSS feeds" block
    Then I should see "Click the edit icon above to configure this block to display RSS feeds"
    When I follow "Configure Remote news feed block"
    And I select "$NASTYSTRING1" from "Choose the feeds which you would like to make available in this block:"
    And I press "Save changes"
    Then I should see "$NASTYSTRING1"
    When I log out
    Then I should see "You are not logged in."

  @javascript
  Scenario: Switch "Title" setting
    Given I log in as "admin"
    And I follow "RSS Test Course"
    Then I should see "$NASTYSTRING1"
    When I turn editing mode on
    And I follow "Configure $NASTYSTRING1 block"
    And I fill in "Title:" with "$NASTYSTRING2"
    And I press "Save changes"
    Then I should see "$NASTYSTRING2"
    And I should not see "$NASTYSTRING1"

  @javascript
  Scenario: Switch "Source Site" setting
    Given I log in as "admin"
    And I follow "RSS Test Course"
    And I turn editing mode on
    And I follow "Configure $NASTYSTRING1 block"
    And I select "No" from "Should a link to the original site (channel link) be displayed? (Note that if no feed link is supplied in the news feed then no link will be shown) :"
    And I press "Save changes"
    Then I should not see "Source site..."
    When I follow "Configure $NASTYSTRING1 block"
    And I select "Yes" from "Should a link to the original site (channel link) be displayed? (Note that if no feed link is supplied in the news feed then no link will be shown) :"
    And I press "Save changes"
    Then I should see "Source site..."

  @javascript
  Scenario: Switch "Max Entries" setting
    Given I log in as "admin"
    And I follow "RSS Test Course"
    And I turn editing mode on
    And I follow "Configure $NASTYSTRING1 block"
    And I fill in "Max number entries to show per block." with "not a number"
    And I press "Save changes"
    Then I should see "You must enter a number here."
    When I fill in "Max number entries to show per block." with "1"
    And I press "Save changes"
    Then I should see "$NASTYSTRING1"
    # Should see the first item in the feed:
    And I should see "Feed item 1 title"
    # But not the second item:
    And I should not see "Feed item 2 title"
    When I follow "Configure $NASTYSTRING1 block"
    And I fill in "Max number entries to show per block." with "2"
    And I press "Save changes"
    Then I should see "$NASTYSTRING1"
    # Should see the first item in the feed:
    And I should see "Feed item 1 title"
    # And the second item:
    And I should see "Feed item 2 title"

  @javascript
  Scenario: Switch "link description" setting
    Given I log in as "admin"
    And I follow "RSS Test Course"
    And I turn editing mode on
    And I follow "Configure $NASTYSTRING1 block"
    And I select "No" from "Display each link's description?"
    And I press "Save changes"
    Then I should not see "Item 1 description."
    When I follow "Configure $NASTYSTRING1 block"
    And I select "Yes" from "Display each link's description?"
    And I press "Save changes"
    Then I should see "Item 1 description."
