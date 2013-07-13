<?php

/**
 * @file
 * Contains Drupal\tmgmt_ui\Tests\TmgmtUiTest.php
 */

namespace Drupal\tmgmt_ui\Tests;

use Drupal\tmgmt\Tests\TMGMTTestBase;

/**
 * Test the user interface of tmgmt, for example the checkout form.
 */
class TmgmtUiTest extends TMGMTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tmgmt_ui');

  static function getInfo() {
    return array(
      'name' => 'UI tests',
      'description' => 'Verifies basic functionality of the user interface',
      'group' => 'Translation Management',
    );
  }

  function setUp() {
    parent::setUp();
    $this->addLanguage('de');
    $this->addLanguage('es');
    $this->addLanguage('el');

    // Login as translator only with limited permissions to run these tests.
    $this->loginAsTranslator(array(
      'access administration pages',
      'create translation jobs',
      'submit translation jobs',
    ), TRUE);
  }

  /**
   * Test the page callbacks to create jobs and check them out.
   */
  function testCheckoutForm() {

    // Add a first item to the job. This will auto-create the job.
    $job = tmgmt_job_match_item('en', '');
    $job->addItem('test_source', 'test', 1);

    // Go to checkout form.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->drupalGet(reset($redirects));

    // Check checkout form.
    $this->assertText('test_source:test:1');

    // Add two more job items.
    $job->addItem('test_source', 'test', 2);
    $job->addItem('test_source', 'test', 3);

    // Go to checkout form.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->drupalGet(reset($redirects));

    // Check checkout form.
    $this->assertText('test_source:test:1');
    $this->assertText('test_source:test:2');
    $this->assertText('test_source:test:3');

    // @todo: Test ajax functionality.

    // Attempt to translate into greek.
    $edit = array(
      'target_language' => 'el',
      'settings[action]' => 'translate',
    );
    $this->drupalPost(NULL, $edit, t('Submit to translator'));
    $this->assertText(t('@translator can not translate from @source to @target.', array('@translator' => 'Test translator (auto created)', '@source' => 'English', '@target' => 'Greek')));

    // Job still needs to be in state new.
    $job = entity_load_unchanged('tmgmt_job', $job->tjid);
    $this->assertTrue($job->isUnprocessed());

    $edit = array(
      'target_language' => 'es',
      'settings[action]' => 'translate',
    );
    $this->drupalPost(NULL, $edit, t('Submit to translator'));

    // Job needs to be in state active.
    $job = entity_load_unchanged('tmgmt_job', $job->tjid);
    $this->assertTrue($job->isActive());
    foreach ($job->getItems() as $job_item) {
      /* @var $job_item JobItem */
      $this->assertTrue($job_item->isNeedsReview());
    }
    $this->assertText(t('Test translation created'));
    $this->assertNoText(t('Test translator called'));

    // Test redirection.
    $this->assertText(t('Job overview'));

    // Another job.
    $previous_tjid = $job->tjid;
    $job = tmgmt_job_match_item('en', '');
    $job->addItem('test_source', 'test', 1);
    $this->assertNotEqual($job->tjid, $previous_tjid);

    // Go to checkout form.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->drupalGet(reset($redirects));

     // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:1');

    $edit = array(
      'target_language' => 'es',
      'settings[action]' => 'submit',
    );
    $this->drupalPost(NULL, $edit, t('Submit to translator'));
    $this->assertText(t('Test submit'));
    $job = entity_load_unchanged('tmgmt_job', $job->tjid);
    $this->assertTrue($job->isActive());

    // Another job.
    $job = tmgmt_job_match_item('en', 'es');
    $job->addItem('test_source', 'test', 1);

    // Go to checkout form.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->drupalGet(reset($redirects));

     // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:1');

    $edit = array(
      'settings[action]' => 'reject',
    );
    $this->drupalPost(NULL, $edit, t('Submit to translator'));
    $this->assertText(t('This is not supported'));
    $job = entity_load_unchanged('tmgmt_job', $job->tjid);
    $this->assertTrue($job->isRejected());

    // Check displayed job messages.
    $args = array('@view' => 'view-tmgmt-ui-job-messages');
    $this->assertEqual(2, count($this->xpath('//div[contains(@class, @view)]//tbody/tr', $args)));

    // Check that the author for each is the current user.
    $message_authors = $this->xpath('////div[contains(@class, @view)]//td[contains(@class, @field)]/span', $args + array('@field' => 'views-field-name'));
    $this->assertEqual(2, count($message_authors));
    foreach ($message_authors as $message_author) {
      $this->assertEqual((string)$message_author, $this->translator_user->name);
    }

    // Make sure that rejected jobs can be re-submitted.
    $this->assertTrue($job->isSubmittable());
    $edit = array(
      'settings[action]' => 'translate',
    );
    $this->drupalPost(NULL, $edit, t('Submit to translator'));
    $this->assertText(t('Test translation created'));

    // Another job.
    $job = tmgmt_job_match_item('en', 'es');
    $job->addItem('test_source', 'test', 1);

    // Go to checkout form.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->drupalGet(reset($redirects));

     // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:1');

    $edit = array(
      'settings[action]' => 'fail',
    );
    $this->drupalPost(NULL, $edit, t('Submit to translator'));
    $this->assertText(t('Service not reachable'));
    $job = entity_load_unchanged('tmgmt_job', $job->tjid);
    $this->assertTrue($job->isUnprocessed());

    // Verify that we are still on the form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');

    // Another job.
    $job = tmgmt_job_match_item('en', 'es');
    $job->addItem('test_source', 'test', 1);

    // Go to checkout form.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->drupalGet(reset($redirects));

    // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:1');

    $edit = array(
      'settings[action]' => 'not_translatable',
    );
    $this->drupalPost(NULL, $edit, t('Submit to translator'));
    // @todo Update to correct failure message.
    $this->assertText(t('Fail'));
    $job = entity_load_unchanged('tmgmt_job', $job->tjid);
    $this->assertTrue($job->isUnprocessed());

    // Test default settings.
    $this->default_translator->settings['action'] = 'reject';
    $this->default_translator->save();
    $job = tmgmt_job_match_item('en', 'es');
    $job->addItem('test_source', 'test', 1);

    // Go to checkout form.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->drupalGet(reset($redirects));

     // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:1');

    // The action should now default to reject.
    $this->drupalPost(NULL, array(), t('Submit to translator'));
    $this->assertText(t('This is not supported.'));
    $job = entity_load_unchanged('tmgmt_job', $job->tjid);
    $this->assertTrue($job->isRejected());
  }

  /**
   * Tests the tmgmt_ui_job_checkout() function.
   */
  function testCheckoutFunction() {
    $job = $this->createJob();

    // Check out a job when only the test translator is available. That one has
    // settings, so a checkout is necessary.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $uri = $job->uri();
    $this->assertEqual($uri['path'], $redirects[0]);
    $this->assertTrue($job->isUnprocessed());
    $job->delete();

    // Hide settings on the test translator.
    $default_translator = tmgmt_translator_load('test_translator');
    $default_translator->settings = array(
      'expose_settings' => FALSE,
    );
    $job = $this->createJob();

    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->assertFalse($redirects);
    $this->assertTrue($job->isActive());

    // A job without target language needs to be checked out.
    $job = $this->createJob('en', '');
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $uri = $job->uri();
    $this->assertEqual($uri['path'], $redirects[0]);
    $this->assertTrue($job->isUnprocessed());

    // Create a second file translator. This should check
    // out immediately.
    $job = $this->createJob();

    $second_translator = $this->createTranslator();
    $second_translator->settings = array(
      'expose_settings' => FALSE,
    );
    $second_translator->save();

    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $uri = $job->uri();
    $this->assertEqual($uri['path'], $redirects[0]);
    $this->assertTrue($job->isUnprocessed());
  }

  /**
   * Tests of the job item review process.
   */
  public function testReview() {
    $job = $this->createJob();
    $job->translator = $this->default_translator->name;
    $job->settings = array();
    $job->save();
    $item = $job->addItem('test_source', 'test', 1);

    $data = tmgmt_flatten_data($item->getData());
    $keys = array_keys($data);
    $key = $keys[0];

    $this->drupalGet('admin/config/regional/tmgmt/items/' . $item->tjiid);
    // Testing the result of the
    // TMGMTTranslatorUIControllerInterface::reviewDataItemElement()
    $this->assertText(t('Testing output of review data item element @key from the testing translator.', array('@key' => $key)));

    // Test the review tool source textarea.
    $this->assertFieldByName('dummy|deep_nesting[source]', $data[$key]['#text']);

    // Save translation.
    $this->drupalPost(NULL, array('dummy|deep_nesting[translation]' => $data[$key]['#text'] . 'translated'), t('Save'));
    $this->drupalGet('admin/config/regional/tmgmt/items/' . $item->tjiid);
    // Check if translation has been saved.
    $this->assertFieldByName('dummy|deep_nesting[translation]', $data[$key]['#text'] . 'translated');
  }

  /**
   * Tests the UI of suggestions.
   */
  public function testSuggestions() {
    // Prepare a job and a node for testing.
    $job = $this->createJob();
    $job->addItem('test_source', 'test', 1);
    $job->addItem('test_source', 'test', 7);

    // Go to checkout form.
    $redirects = tmgmt_ui_job_checkout_multiple(array($job));
    $this->drupalGet(reset($redirects));

    $this->assertRaw('20');

    // Load all suggestions.
    $commands = $this->drupalPostAJAX(NULL, array(), array('op' => t('Load suggestions')));
    $this->assertEqual(count($commands), 3, 'Found 3 commands in AJAX-Request.');

    // Check each command for success.
    foreach ($commands as $command) {
      // No checks against the settings because we not use ajax to save.
      if ($command['command'] == 'settings') {
      }
      // Other commands must be from type "insert".
      else if ($command['command'] == 'insert') {
        // This should be the tableselect javascript file for the header.
        if (($command['method'] == 'prepend') && ($command['selector'] == 'head')) {
          $this->assertTrue(substr_count($command['data'], 'misc/tableselect.js'), 'Javascript for Tableselect found.');
        }
        // Check for the main content, the tableselect with the suggestions.
        else if (($command['method'] == NULL) && ($command['selector'] == NULL)) {
          $this->assertTrue(substr_count($command['data'], '</th>') == 5, 'Found five table header.');
          $this->assertTrue(substr_count($command['data'], '</tr>') == 3, 'Found two suggestion and one table header.');
          $this->assertTrue(substr_count($command['data'], '<td>11</td>') == 2, 'Found 10 words to translate per suggestion.');
          $this->assertTrue(substr_count($command['data'], 'value="Add suggestions"'), 'Found add button.');
        }
        else {
          $this->fail('Unknown method/selector combination.');
          debug($command);
        }
      }
      else {
        $this->fail('Unknown command.');
        debug($command);
      }
    }

    $this->assertText('test_source:test_suggestion:1');
    $this->assertText('test_source:test_suggestion:7');
    $this->assertText('Test suggestion for test source 1');
    $this->assertText('Test suggestion for test source 7');

    // Add the second suggestion.
    $edit = array('suggestions_table[2]' => TRUE);
    $this->drupalPost(NULL, $edit, t('Add suggestions'));

    // Total word count should now include the added job.
    $this->assertRaw('31');
    // The suggestion for 7 was added, so there should now be a suggestion
    // or the suggestion instead.
    $this->assertNoText('Test suggestion for test source 7');
    $this->assertText('test_source:test_suggestion_suggestion:7');

  }
}