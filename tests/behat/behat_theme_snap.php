<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Steps definitions for behat theme.
 *
 * @package   theme_snap
 * @category  test
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given,
    Behat\Mink\Element\NodeElement,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Choice activity definitions.
 *
 * @package   theme_snap
 * @category  test
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_snap extends behat_base {

    /**
     * Process givens array
     * @param array $givens
     * @return array
     */
    protected function process_givens_array(array $givens) {
        $givens = array_map(function($given){
            if (is_string($given)) {
                return new Given($given);
            } else if ($given instanceof Given) {
                return $given;
            } else {
                throw new coding_exception('Given must be a string or Given instance');
            }
        }, $givens);
        return $givens;
    }

    /**
     * Waits until the provided element selector is visible.
     *
     * @Given /^I wait until "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" is visible$/
     * @param string $element
     * @param string $selector
     * @return void
     */
    public function i_wait_until_is_visible($element, $selectortype) {
        $this->ensure_element_is_visible($element, $selectortype);
    }

    /**
     * Logs in the user. There should exist a user with the same value as username and password.
     *
     * @Given /^I log in with snap as "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_log_in_with_snap_as($username) {

        // Go back to front page.
        $this->getSession()->visit($this->locate_path('/'));

        // Generic steps (we will prefix them later expanding the navigation dropdown if necessary).
        $steps = array(
            new Given('I click on "' . get_string('login') . '" "link"'),
            new Given('I should not see "Log out"'),
            new Given('I wait until "#loginbtn" "css_element" is visible'),
            new Given('I set the field "' . get_string('username') . '" to "' . $this->escape($username) . '"'),
            new Given('I set the field "' . get_string('password') . '" to "'. $this->escape($username) . '"'),
            new Given('I press "' . get_string('login') . '"')
        );

        // If Javascript is disabled we have enough with these steps.
        if (!$this->running_javascript()) {
            return $steps;
        }

        // Wait for the homepage to be ready.
        $this->getSession()->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);

        return $steps;
    }

    /**
     * @param string $fixturefilename this is a filename relative to the snap fixtures folder.
     * @param string $input
     *
     * @Given /^I upload file "(?P<fixturefilename_string>(?:[^"]|\\")*)" to section "(?P<section>(?:[^"]|\\")*)"$/
     */
    public function i_upload_file($fixturefilename, $section = 1) {
        global $CFG;
        $fixturefilename = clean_param($fixturefilename, PARAM_FILE);
        //$filepath = $CFG->themedir.'/snap/tests/fixtures/'.$fixturefilename;
        $filepath = $CFG->dirroot.'/theme/snap/tests/fixtures/'.$fixturefilename;
        $input = '#snap-drop-file-'.$section;
        $file = $this->find('css', $input);
        $file->attachFile($filepath);
    }

    /**
     * Bypass javascript attributed to link and just go straight to href.
     * @param string $link
     *
     * @Given /^Snap I follow link "(?P<link>(?:[^"]|\\")*)"$/
     */
    public function i_follow_href($link) {
        $el = $this->find_link($link);
        $href = $el->getAttribute('href');
        $this->getSession()->visit($href);
    }

    /**
     * @param int $section
     * @Given /^I go to single course section (\d+)$/
     */
    public function i_go_to_single_course_section($section) {
        $generalcontext = behat_context_helper::get('behat_general');
        $generalcontext->wait_until_the_page_is_ready();
        $currenturl = $this->getSession()->getCurrentUrl();
        if (stripos($currenturl, 'course/view.php') === false) {
            throw new ExpectationException('Current page is not a course page!', $this->getSession());
        }
        if (strpos($currenturl, '?') !== false) {
            $glue = '&';
        } else {
            $glue = '?';
        }
        $newurl = $currenturl.$glue.'section='.$section;
        $this->getSession()->visit($newurl);
    }

    /**
     * @param int $section
     * @Given /^I go to course section (\d+)$/
     */
    public function i_go_to_course_section($section) {
        $generalcontext = behat_context_helper::get('behat_general');
        $generalcontext->wait_until_the_page_is_ready();
        $session = $this->getSession();
        $currenturl = $session->getCurrentUrl();
        if (stripos($currenturl, 'course/view.php') === false) {
            throw new ExpectationException('Current page is not a course page!', $session);
        }
        $session->executeScript('location.hash = "'.'section-'.$section.'";');

        $givens = [
            'I wait until the page is ready',
            'I wait until "#section-'.$section.'" "css_element" is visible'
        ];
        $givens = array_map(function($given){
            return new Given($given);
        }, $givens);
        return $givens;
    }

    /**
     * @param string
     * @return array
     * @Given  /^I can see course "(?P<course>(?:[^"]|\\")*)" in all sections mode$/
     */
    public function i_can_see_course_in_all_sections_mode($course) {
        $givens = [
            'I follow "Menu"',
            'Snap I follow link "'.$course.'"',
            'I wait until the page is ready',
            'I go to single course section 1',
            '".section-navigation.navigationtitle" "css_element" should not exist',
            # In the above, .section-navigation.navigationtitle relates to the element on the page which contains the single
            # section at a time navigation. Visually you would see a link on the left entitled "General" and a link on the right
            # enitled "Topic 2"
            # This test ensures you do not see those elements. If you swap to clean theme in a single section mode at a time
            # course you will see that navigation after clicking on topic 1.
        ];
        $givens = array_map(function($given){
            return new Given($given);
        }, $givens);
        return $givens;
    }

    /**
     * @param string
     * @return array
     * @Given  /^Snap I log out$/
     */
    public function i_log_out() {
        $this->getSession()->executeScript("window.scrollTo(0, 0);");
        $givens = [
            'I follow "Menu"',
            'I wait until ".btn.logout" "css_element" is visible',
            'I follow "Log out"',
            'I wait until the page is ready'
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * @param string $coursename
     * @Given /^I create a new section in course "(?P<coursename>(?:[^"]|\\")*)"$/
     * @return array
     */
    public function i_create_a_new_section_in_course($coursename) {
        $givens = [
            'I open the personal menu',
            'Snap I follow link "'.$coursename.'"',
            'I follow "Create a new section"',
            'I set the field "Title" to "New section title"',
            'I click on "Create section" "button"'
        ];
        $givens = array_map(function($a) {return new Given($a);}, $givens);
        return $givens;
    }

    /**
     * @param string $coursename
     * @Given /^I create a new section in weekly course "(?P<coursename>(?:[^"]|\\")*)"$/
     * @return array
     */
    public function i_create_a_new_section_in_weekly_course($coursename) {
        $givens = [
            'I open the personal menu',
            'Snap I follow link "'.$coursename.'"',
            'I follow "Create a new section"',
            'I should see "Title: 8 April-14 April"',
            'I click on "Create section" "button"'
        ];
        $givens = array_map(function($a) {return new Given($a);}, $givens);
        return $givens;
    }

    /**
     * I follow "Menu" fails randomly on occasions, this custom step is an alternative to resolve that issue.
     * It also avoids a failure if the menu is already open.
     * @Given /^I open the personal menu$/
     */
    public function i_open_the_personal_menu() {
        $node = $this->find('css', '#primary-nav');
        $this->getSession()->executeScript("window.scrollTo(0, 0);");
        if (!$node->isVisible()) {
            return [new Given('I click on "#js-personal-menu-trigger" "css_element"')];
        } else {
            // Already open.
            return null;
        }
    }

    /**
     * Checks that the provided node is visible.
     *
     * @throws ExpectationException
     * @param NodeElement $node
     * @param int $timeout
     * @param null|ExpectationException $exception
     * @return bool
     */
    protected function is_node_visible(NodeElement $node,
                                       $timeout = self::EXTENDED_TIMEOUT,
                                       ExpectationException $exception = null) {

        // If an exception isn't specified then don't throw an error if visibility can't be evaluated.
        $dontthrowerror = empty($exception);

        // Exception for timeout checking visibility.
        $msg = 'Something went wrong whilst checking visibility';
        $exception = new ExpectationException($msg, $this->getSession());

        $visible = false;

        try {
            $visible = $this->spin(
                function ($context, $args) {
                    if ($args->isVisible()) {
                        return true;
                    }
                    return false;
                },
                $node,
                $timeout,
                $exception,
                true
            );
        } catch (Exception $e) {
            if (!$dontthrowerror) {
                throw $exception;
            }
        }
        return $visible;
    }

    /**
     * Clicks link with specified id|title|alt|text.
     *
     * @When /^I follow visible link "(?P<link_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $link
     */
    public function click_visible_link($link) {
        $linknode = $this->find_link($link);
        if (!$linknode) {
            $msg = 'The "' . $linknode->getXPath() . '" xpath node could not be found';
            throw new ExpectationException($msg, $this->getSession());
        }

        // See if the first node is visible and if so click it.
        if ($this->is_node_visible($linknode)) {
            $linknode->click();
            return;
        }

        // The first node on the page isn't visible so we are going to have to get all nodes with the same xpath.
        // Extract xpath from the first node we found.
        $xpath = $linknode->getXpath();
        $matches = [];
        if (preg_match_all('|^\(//html/(.*)(?=\)\[1\]$)|', $xpath, $matches) !== false) {
            $xpath = $matches[1][0];
        } else {
            throw new coding_exception('Failed to extract xpath from '.$xpath);
        }

        // Now get all nodes.
        $linknodes = $this->find_all('xpath', $xpath);

        // Cycle through all nodes and if just one of them is visible break loop.
        foreach ($linknodes as $node) {
            if ($node === $linknode) {
                // We've already tested the first node, skip it.
                continue;
            }
            $visible = $this->is_node_visible($node, self::REDUCED_TIMEOUT);
            if ($visible) {
                break;
            }
        }

        if (!$visible) {
            // Oh dear, none of the links were visible.
            $msg = 'At least one node should be visible for the xpath "' . $node->getXPath();
            throw new ExpectationException($msg, $this->getSession());
        }

        // Let's also scroll to the node and make sure its in the viewport.
        $this->scroll_to_node($node);

        // Hurray, we found a visible link - let's click it!
        $node->click();
    }

    /**
     * Scroll to node
     * @param NodeElement $node
     */
    protected function scroll_to_node(NodeElement $node) {
        $nodexpath = str_replace('"', '\"', $node->getXpath());
        $script =
            <<<EOF
                        var el = document.evaluate("$nodexpath",
                                       document,
                                       null,
                                       XPathResult.FIRST_ORDERED_NODE_TYPE,
                                       null
                                       ).singleNodeValue;

            var rect = el.getBoundingClientRect();
            var height = rect.bottom - rect.top;
            window.scrollTo(0, rect.top-(height/2));
EOF;

        $this->getSession()->executeScript($script);
    }

    /**
     * Make sure element is within viewport
     *
     * @param string $element
     * @param string $selectortype
     * @Given /^I scroll until "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" is visible$/
     */
    public function i_scroll_to_element($element, $selectortype) {
        // Getting Mink selector and locator.
        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        // Get node.
        $node = $this->find($selector, $locator);
        $this->scroll_to_node($node);
    }

    /**
     * Presses button with specified id|name|title|alt|value.
     *
     * @When /^I press "(?P<button_string>(?:[^"]|\\")*)" \(theme_snap\)$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $button
     */
    public function snap_press_button($button) {
        // Ensures the button is present.
        $buttonnode = $this->find_button($button);
        $this->scroll_to_node($buttonnode);
        $this->ensure_node_is_visible($buttonnode);
        $buttonnode->press();
    }

    /**
     * List steps required for adding a date restriction
     * @param int $datetime
     * @param string $savestr
     * @return array
     */
    protected function add_date_restriction($datetime, $savestr) {

        $year = date('Y', $datetime);
        $month = date('n', $datetime);
        $day = date('j', $datetime);

        $givens = [
            'I expand all fieldsets',
            'I click on "Add restriction..." "button"',
            '"Add restriction..." "dialogue" should be visible',
            'I click on "Date" "button" in the "Add restriction..." "dialogue"',
            'I set the field "day" to "'.$day.'"',
            'I set the field "Month" to "'.$month.'"',
            'I set the field "year" to "'.$year.'"',
            'I press "'.$savestr.'" (theme_snap)',
            'I wait until the page is ready'
        ];

        return $givens;
    }

    /**
     * Restrict a course section by date.
     * @param int $section
     * @param string $date
     * @Given /^I restrict course section (?P<section_int>(?:\d+)) by date to "(?P<date_string>(?:[^"]|\\")*)"$/
     */
    public function i_restrict_course_section_by_date($section, $date) {
        $datetime = strtotime($date);

        $givens = [
            'I go to course section '.$section,
            'I follow visible link "Edit Topic"',
            'I wait until ".snap-form-advanced" "css_element" is visible',
            'I set the field "name" to "Topic '.$date.' '.$section.'"',
        ];

        $givens = array_merge($givens, $this->add_date_restriction($datetime, 'Save changes'));

        return $this->process_givens_array($givens);
    }

    /**
     * Restrict a course asset by date.
     * @param string $assettitle
     * @param string $date
     * @Given /^I restrict course asset "(?P<asset_string>(?:[^"]|\\")*)" by date to "(?P<date_string>(?:[^"]|\\")*)"$/
     */
    public function i_restrict_asset_by_date($assettitle, $date) {
        $datetime = strtotime($date);

        $givens = [
            'I follow asset link "'.$assettitle.'"',
            'I click on "#admin-menu-trigger" "css_element"',
            'I wait until ".block_settings.state-visible" "css_element" is visible',
            'I navigate to "Edit settings" node in "Assignment administration"'
        ];

        $givens = array_merge($givens, $this->add_date_restriction($datetime, 'Save and return to course'));

        return $this->process_givens_array($givens);
    }

    /**
     * Check conditional date message in given element.
     * @param string $date
     * @param string $element
     * @param string $selectortype
     * @Given /^I should see available from date of "(?P<date_string>(?:[^"]|\\")*)" in "(?P<element_string>(?:[^"]|\\")*)" "(?P<locator_string>(?:[^"]|\\")*)"$/
     */
    public function i_should_see_available_from_in_element($date, $element, $selectortype) {
        $datetime = strtotime($date);

        $date = userdate($datetime,
            get_string('strftimedate', 'langconfig'));

        $givens = [
            'I should see "Available from" in the "'.$element.'" "'.$selectortype.'"',
            'I should see "'.$date.'" in the "'.$element.'" "'.$selectortype.'"',
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * Check conditional date message does not exist in given element.
     * @param string $date
     * @param string $element
     * @param string $selectortype
     * @Given /^I should not see available from date of "(?P<date_string>(?:[^"]|\\")*)" in "(?P<element_string>(?:[^"]|\\")*)" "(?P<locator_string>(?:[^"]|\\")*)"$/
     */
    public function i_should_not_see_available_from_in_element($date, $element, $selectortype) {
        $datetime = strtotime($date);

        $date = userdate($datetime,
            get_string('strftimedate', 'langconfig'));

        $givens = [
            'I should not see "Available from" in the "'.$element.'" "'.$selectortype.'"',
            'I should not see "'.$date.'" in the "'.$element.'" "'.$selectortype.'"',
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * Check conditional date message in nth asset within section x.
     * @param string $date
     * @param string $nthasset
     * @param int $section
     * @Given /^I should see available from date of "(?P<date_string>(?:[^"]|\\")*)" in the (?P<nthasset_string>(?:\d+st|\d+nd|\d+rd|\d+th)) asset within section (?P<section_int>(?:\d+))$/
     */
    public function i_should_see_available_from_in_asset($date, $nthasset, $section) {
        $nthasset = intval($nthasset);
        $elementselector = '#section-'.$section.' li.snap-asset:nth-of-type('.$nthasset.')';
        return $this->i_should_see_available_from_in_element($date, $elementselector, 'css_element');
    }

    /**
     * Check conditional date message not in nth asset within section x.
     * @param string $date
     * @param string $nthasset
     * @param int $section
     * @Given /^I should not see available from date of "(?P<date_string>(?:[^"]|\\")*)" in the (?P<nthasset_string>(?:\d+st|\d+nd|\d+rd|\d+th)) asset within section (?P<section_int>(?:\d+))$/
     */
    public function i_should_not_see_available_from_in_asset($date, $nthasset, $section) {
        $nthasset = intval($nthasset);
        $elementselector = '#section-'.$section.' li.snap-asset:nth-of-type('.$nthasset.')';
        return $this->i_should_not_see_available_from_in_element($date, $elementselector, 'css_element');
    }

    /**
     * Check conditional date message in section.
     * @param string $date
     * @param int $section
     * @Given /^I should see available from date of "(?P<date_string>(?:[^"]|\\")*)" in section (?P<section_int>(?:\d+))$/
     */
    public function i_should_see_available_from_in_section($date, $section) {
        $elementselector = '#section-'.$section.' > div.content > div.snap-restrictions-meta';
        return $this->i_should_see_available_from_in_element($date, $elementselector, 'css_element');
    }

    /**
     * Check conditional date message not in section.
     * @param string $date
     * @param int $section
     * @Given /^I should not see available from date of "(?P<date_string>(?:[^"]|\\")*)" in section (?P<section_int>(?:\d+))$/
     */
    public function i_should_not_see_available_from_in_section($date, $section) {
        $elementselector = '#section-'.$section.' > div.content > div.snap-restrictions-meta';
        return $this->i_should_not_see_available_from_in_element($date, $elementselector, 'css_element');
    }


    /**
     * @param string $text
     * @param int $tocitem
     * @Given /^I should see "(?P<text_string>(?:[^"]|\\")*)" in TOC item (?P<tocitem_int>(?:\d+))$/
     */
    public function i_should_see_in_toc_item($text, $tocitem) {
        $tocitem++; // Ignore introduction item.
        $givens = [
            'I should see "'.$text.'" in the "#chapters li:nth-of-type('.$tocitem.')" "css_element"'
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * @param string $text
     * @param int $tocitem
     * @Given /^I should not see "(?P<text_string>(?:[^"]|\\")*)" in TOC item (?P<tocitem_int>(?:\d+))$/
     */
    public function i_should_not_see_in_toc_item($text, $tocitem) {
        $tocitem++; // Ignore introduction item.
        $givens = [
            'I should not see "'.$text.'" in the "#chapters li:nth-of-type('.$tocitem.')" "css_element"'
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * Open an assignment or resource based on title.
     *
     * @param string $assettitle
     * @throws ExpectationException
     * @Given /^I follow asset link "(?P<assettitle>(?:[^"]|\\")*)"$/
     */
    public function i_follow_asset_link($assettitle) {
        $xpath = '//a/span[contains(.,"'.$assettitle.'")]';

        // Now get all nodes.
        $linknodes = $this->find_all('xpath', $xpath);

        // Cycle through all nodes and if just one of them is visible break loop.
        foreach ($linknodes as $node) {
            $visible = $this->is_node_visible($node, self::REDUCED_TIMEOUT);
            if ($visible) {
                break;
            }
        }

        if (!$visible) {
            // Oh dear, none of the links were visible.
            $msg = 'At least one node should be visible for the xpath "' . $node->getXPath();
            throw new ExpectationException($msg, $this->getSession());
        }

        // Hurray, we found a visible link - let's click it!
        $node->click();
    }
}
