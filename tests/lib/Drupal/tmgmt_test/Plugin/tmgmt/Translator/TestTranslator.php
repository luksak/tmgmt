<?php

/**
 * @file
 * Contains Drupal\tmgmt_test\Plugin\tmgmt\Translator\TestSource.
 */

namespace Drupal\tmgmt_test\Plugin\tmgmt\Translator;

use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\Plugin\Core\Entity\Job;
use Drupal\tmgmt\Plugin\Core\Entity\JobItem;
use Drupal\tmgmt\Plugin\Core\Entity\Translator;
use Drupal\tmgmt\TranslatorRejectDataInterface;
use Drupal\tmgmt\Annotation\TranslatorPlugin;
use Drupal\Core\Annotation\Translation;

/**
 * Test source plugin implementation.
 *
 * @TranslatorPlugin(
 *   id = "test_translator",
 *   label = @Translation("Test translator"),
 *   description = @Translation("Simple translator for testing purposes."),
 *   default_settings = {
 *     "expose_settings" = TRUE,
 *   }
 * )
 */
class TestTranslator extends TranslatorPluginBase implements TranslatorRejectDataInterface {

 /**
   * Overrides TMGMTDefaultTranslatorPluginController::getDefaultRemoteLanguagesMappings().
   */
  public function getDefaultRemoteLanguagesMappings() {
    return array(
      'en' => 'en-us',
      'de' => 'de-ch',
    );
  }

  /**
   * Overrides DefaultTranslatorPluginController::hasCheckoutSettings().
   */
  public function hasCheckoutSettings(Job $job) {
    return $job->getTranslator()->getSetting('expose_settings');
  }

  /**
   * Overrides DefaultTranslatorPluginController::requestTranslation().
   */
  function requestTranslation(Job $job) {
    // Add a debug message.
    $job->addMessage('Test translator called.', array(), 'debug');

    // Do something different based on the action, if defined.
    $action = isset($job->settings['action']) ? $job->settings['action'] : '';
    switch ($action) {
      case 'submit':
        $job->submitted('Test submit.');
        break;

      case 'reject':
        $job->rejected('This is not supported.');
        break;

      case 'fail':
        // Target not reachable.
        $job->addMessage('Service not reachable.', array(), 'error');
        break;

      case 'translate':
      default:
        // The dummy translation prefixes strings with the target language.
        $data = array_filter(tmgmt_flatten_data($job->getData()), '_tmgmt_filter_data');
        $tdata = array();
        foreach ($data as $key => $value) {
          $tdata[$key]['#text'] = $job->target_language . '_' . $value['#text'];
        }
        $job->submitted('Test translation created.');
        $job->addTranslatedData(tmgmt_unflatten_data($tdata));
        break;
    }
  }

  /**
   * Overrides TMGMTDefaultTranslatorPluginController::canTranslate().
   */
  function canTranslate(Translator $translator, Job $job) {
    if (isset($job->settings['action']) && $job->settings['action'] == 'not_translatable') {
      return FALSE;
    }
    return parent::canTranslate($translator, $job);
  }

  /**
   * Overrides DefaultTranslatorPluginController::getSupportedTargetLanguages().
   */
  public function getSupportedTargetLanguages(Translator $translator, $source_language) {
    $languages = drupal_map_assoc(array('en', 'de', 'es', 'it', 'zh-hans'));
    unset($languages[$source_language]);
    return $languages;
  }

  /**
   * Overrides TranslatorRejectDataItem::rejectDataItem().
   */
  public function rejectDataItem(JobItem $job_item, array $key, array $values = NULL) {
    $key = '[' . implode('][', $key) . ']';
    $job_item->addMessage('Rejected data item @key for job item @item in job @job.', array('@key' => $key, '@item' => $job_item->tjiid, '@job' => $job_item->tjid));
    return TRUE;
  }

  /**
   * Implements TMGMTTranslatorRejectDataItem::rejectForm().
   */
  public function rejectForm($form, &$form_state) {
    return $form;
  }

}
