<?php

/**
 * @file
 * Contains \Drupal\mymodule\Tests\MyModulePageTest.
 */

namespace Drupal\mymodule\Tests;

/**
 * Tests page output.
 *
 * @group Programmers Guide to Drupal
 */
class MyModulePageTest extends ProgrammersGuideTestBase {

  /**
   * Modules to enable (the book's module, plus User, Block, Node).
   *
   * @var array
   */
  public static $modules = array('mymodule', 'user', 'block', 'node');

  /**
   * Array of generated nodes.
   *
   * @var array
   */
  protected $nodes = array();

  function setUp() {
    parent::setUp();

    // Generate 15 random nodes. Ensure the created times are in order.
    for ($i = 0; $i < 15; $i++ ) {
      $start = REQUEST_TIME - 30;;
      $this->nodes[] = $this->drupalCreateNode(array(
          'changed' => $start + $i,
          'created' => $start + $i,
        ));
    }
  }

  /**
   * Tests the pages and blocks generated by the module.
   *
   * @see mymodule.routing.yml
   * @see mymodule.links.menu.yml
   * @see \Drupal\mymodule\Routing\MyModuleRouting
   * @see \Drupal\mymodule\Controller\MyUrlController
   * @see \Drupal\mymodule\Plugin\Block\MyModuleFirstBlock
   */
  function testPages() {
    $page_title = 'My page title';
    $dynamic_page_title = 'New page title';
    $block_title = 'This is a test';
    $text_in_block = array(
      'General information goes here',
      'Colors',
      'Blue',
      'Materials',
      'Characteristic',
      'Steel',
      'Light',
    );

    // Several pages should not allow anonymous access. Test this.
    $paths = array('mymodule/mypath', 'admin/people/mymodule', 'admin/content/mycontent/delete/5', 'mymodule/autocomplete');
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertResponse('403', "Access is denied to $path while not logged in");
    }

    // Test the form while not logged in. It should be visible, but the
    // company field should not be visible.
    $this->drupalGet('mymodule/my_form_page');
    $this->assertTitleContains('Personal data form', 'Form page title is correct');
    $this->assertText('First name', 'Name field is shown');
    $this->assertNoText('Company', 'Company field is not shown to anonymous user');
    // Submit the form.
    $this->drupalPostForm(NULL, array('first_name' => 'Jennifer'), t('Submit'));
    $this->assertText('Thank you Jennifer', 'Submit message is shown');

    // Test alteration of the user register form.
    $this->drupalGet('user/register');
    $this->assertText('Company e-mail address', 'Email label has been altered');
    $this->drupalPostForm(NULL, array('name' => 'foo', 'mail' => 'test@example.com'), t('Create new account'));
    $this->assertText('You are not allowed to register', 'Validation message displayed');
    $this->assertNoText('Thank you for applying', 'Registration message not displayed');

    // Log in.
    $account = $this->drupalCreateUser(array('administer mymodule', 'access administration pages', 'administer users', 'use company field', 'delete mycontent items'));
    $this->drupalLogin($account);

    // Check title and text on page in mymodule.routing.yml file.
    $this->drupalGet('mymodule/mypath');
    $this->assertResponse('200', 'Access is allowed to page while logged in');
    $this->assertTitleContains($page_title, 'Page title is shown');
    for($i = 5; $i < 15; $i++) {
      $this->assertText($this->nodes[$i]->label(), "Node title $i appears on the page");
    }
    for($i = 0; $i < 5; $i++) {
      $this->assertNoText($this->nodes[$i]->label(), "Node title $i does not appear on the page");
    }

    // Go to second page of paged output and test.
    $this->drupalGet('mymodule/mypath', array('query' => array('page' => 1)));
    $this->assertTitleContains($page_title, 'Page title is correct');
    for($i = 5; $i < 15; $i++) {
      $this->assertNoText($this->nodes[$i]->label(), "Node title $i does not appear on the page");
    }
    for($i = 0; $i < 5; $i++) {
      $this->assertText($this->nodes[$i]->label(), "Node title $i appears on the page");
    }

    // Verify that the link from mymodule.links.menu.yml works.
    $this->drupalGet('admin/structure');
    $this->assertLink('Configure My Module', 0, 'Link is present on admin/structure');
    $this->assertText('Longer description goes here', 'Description is present on admin/structure');
    $this->clickLink('Configure My Module');
    $this->assertTitleContains($page_title, 'Link click went to the right page');
    for($i = 5; $i < 15; $i++) {
      $this->assertText($this->nodes[$i]->label(), "Node title $i appears on the page");
    }
    for($i = 0; $i < 5; $i++) {
      $this->assertNoText($this->nodes[$i]->label(), "Node title $i does not appear on the page");
    }

    // Check the MyModuleRouting class.
    // Normally the page title on admin/people is "People", but it's been
    // altered to be "User accounts".
    $this->drupalGet('admin/people');
    $this->assertTitleContains('User accounts', 'Title was altered');
    // Test the dynamic route.
    $this->drupalGet('admin/people/mymodule');
    $this->assertTitleContains($dynamic_page_title, 'admin/people/mymodule has right title');
    for($i = 5; $i < 15; $i++) {
      $this->assertText($this->nodes[$i]->label(), "Node title $i appears on the page");
    }
    for($i = 0; $i < 5; $i++) {
      $this->assertNoText($this->nodes[$i]->label(), "Node title $i does not appear on the page");
    }

    // Place the block and test it.
    $this->drupalPlaceBlock('mymodule_first_block', array(
        'region' => 'content',
        'label' => $block_title,
      ));
    $this->drupalGet('user');
    $this->assertText($block_title, 'Block title is shown on page');
    foreach ($text_in_block as $text) {
      $this->assertText($text, "Text $text appears on the page");
    }

    // Test the form.
    $this->drupalGet('mymodule/my_form_page');
    $this->assertTitleContains('Personal data form', 'Form page title is correct');
    $this->assertText('First name', 'Name field is shown');
    $this->assertText('Company', 'Company field is shown to user with permission');
    // Submit the form.
    $this->drupalPostForm(NULL, array('first_name' => 'Jennifer', 'company' => 'Poplar'), t('Submit'));
    $this->assertText('Thank you Jennifer from Poplar', 'Submit message is shown');
    // Test that JavaScript information is included.
    $this->assertRaw('mymodule.js', 'JavaScript file is referenced');
    $this->assertRaw('alert', 'In-line JavaScript is included');

    // Test the confirm delete form.
    $this->drupalGet('admin/content/mycontent/delete/5');
    $this->assertText('Are you sure you want to delete content item 5?', 'Question is on the confirm form page');
    // Try the cancel link. Should go to mymodule/mypath.
    $this->clickLink(t('Cancel'));
    $this->assertTitleContains($page_title, 'Cancel went to right place');
    // Try submitting the form. Should go to mymodule/my_form_page.
    $this->drupalPostForm('admin/content/mycontent/delete/5', array(), t('Confirm'));
    $this->assertTitleContains('Personal data form', 'Form submit redirected to right place');
    $this->assertText('Would have deleted 5', 'Submit message is displayed');

    // Test the autocomplete path.
    $this->drupalGet('mymodule/autocomplete', array('query' => array('q' => 'test')));
    $additions = array('add', 'choice', 'more', 'plus', 'something');
    foreach ($additions as $word) {
      $this->assertText('test' . $word, "Autocomplete choice for $word is in the output");
    }

  }
}
