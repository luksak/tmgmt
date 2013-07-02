<?php

/**
 * @file
 * Contains Drupal\tmgmt\TranslatorPluginBase.
 */

namespace Drupal\tmgmt;

use Drupal\Component\Plugin\PluginBase as ComponentPluginBase;
use Drupal\tmgmt\Plugin\Core\Entity\Job;
use Drupal\tmgmt\Plugin\Core\Entity\JobItem;
use Drupal\tmgmt\Plugin\Core\Entity\Translator;

/**
 * Default controller class for service plugins.
 *
 * @ingroup tmgmt_translator
 */
abstract class TranslatorPluginBase extends ComponentPluginBase implements TranslatorPluginInterface {

  protected $supportedRemoteLanguages = array();
  protected $remoteLanguagesMappings = array();

  /**
   * Implements TranslatorPluginControllerInterface::isAvailable().
   */
  public function isAvailable(Translator $translator) {
    // Assume that the translation service is always available.
    return TRUE;
  }

  /**
   * Implements TranslatorPluginControllerInterface::canTranslate().
   */
  public function canTranslate(Translator $translator, Job $job) {
    // The job is only translatable if the translator is available too.
    if ($this->isAvailable($translator) && array_key_exists($job->target_language, $translator->getSupportedTargetLanguages($job->source_language))) {
      // We can only translate this job if the target language of the job is in
      // one of the supported languages.
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Implements TranslatorPluginControllerInterface::cancelTranslation().
   */
  public function cancelTranslation(Job $job) {
    // Assume that we can cancel a translation job at any time.
    $job->setState(TMGMT_JOB_STATE_CANCELLED);
    return TRUE;
  }

  /**
   * Implements TranslatorPluginControllerInterface::getDefaultRemoteLanguagesMappings().
   */
  public function getDefaultRemoteLanguagesMappings() {
    return array();
  }

  /**
   * Implements TranslatorPluginControllerInterface::getSupportedLanguages().
   */
  public function getSupportedRemoteLanguages(Translator $translator) {
    return array();
  }

  /**
   * Implements TranslatorPluginControllerInterface::getRemoteLanguagesMappings().
   */
  public function getRemoteLanguagesMappings(Translator $translator) {
    if (!empty($this->remoteLanguagesMappings)) {
      return $this->remoteLanguagesMappings;
    }

    foreach (language_list() as $language => $info) {
      $this->remoteLanguagesMappings[$language] = $this->mapToRemoteLanguage($translator, $language);
    }

    return $this->remoteLanguagesMappings;
  }

  /**
   * Implements TranslatorPluginControllerInterface::mapToRemoteLanguage().
   */
  public function mapToRemoteLanguage(Translator $translator, $language) {
    if (!tmgmt_provide_remote_languages_mappings($translator)) {
      return $language;
    }

    if (isset($translator->settings['remote_languages_mappings'][$language])) {
      return $translator->settings['remote_languages_mappings'][$language];
    }

    $default_mappings = $this->getDefaultRemoteLanguagesMappings();

    if (isset($default_mappings[$language])) {
      return $default_mappings[$language];
    }

    return $language;
  }

  /**
   * Implements TranslatorPluginControllerInterface::mapToLocalLanguage().
   */
  public function mapToLocalLanguage(Translator $translator, $language) {
    if (!tmgmt_provide_remote_languages_mappings($translator)) {
      return $language;
    }

    if (isset($translator->settings['remote_languages_mappings']) && is_array($translator->settings['remote_languages_mappings'])) {
      $mappings = $translator->settings['remote_languages_mappings'];
    }
    else {
      $mappings = $this->getDefaultRemoteLanguagesMappings();
    }

    if ($remote_language = array_search($language, $mappings)) {
      return $remote_language;
    }

    return $language;
  }


  /**
   * Implements TranslatorPluginControllerInterface::getSupportedTargetLanguages().
   */
  public function getSupportedTargetLanguages(Translator $translator, $source_language) {
    $languages = entity_metadata_language_list();
    unset($languages[LANGUAGE_NONE], $languages[$source_language]);
    return drupal_map_assoc(array_keys($languages));
  }

  /**
   * Implements TMGMTTranslatorPluginControllerInterface::getSupportedLanguagePairs().
   *
   * Default implementation that gets target languages for each remote language.
   * This approach is ineffective and therefore it is advised that a plugin
   * should provide own implementation.
   */
  public function getSupportedLanguagePairs(Translator $translator) {
    $language_pairs = array();

    foreach ($this->getSupportedRemoteLanguages($translator) as $source_language) {
      foreach ($this->getSupportedTargetLanguages($translator, $source_language) as $target_language) {
        $language_pairs[] = array('source_language' => $source_language, 'target_language' => $target_language);
      }
    }

    return $language_pairs;
  }


  /**
   * Implements TranslatorPluginControllerInterface::getNotCanTranslateReason().
   */
  public function getNotCanTranslateReason(Job $job) {
    return t('@translator can not translate from @source to @target.', array('@translator' => $job->getTranslator()->label(), '@source' => tmgmt_language_label($job->source_language), '@target' => tmgmt_language_label($job->target_language)));
  }

  /**
   * Implements TranslatorPluginControllerInterface::getNotAvailableReason().
   */
  public function getNotAvailableReason(Translator $translator) {
    return t('@translator is not available. Make sure it is properly !configured.', array('@translator' => $this->pluginDefinition['label'], '!configured' => l(t('configured'), 'admin/config/regional/tmgmt/translators/manage/' . $translator->name)));
  }

  /**
   * Implements TranslatorPluginControllerInterface::defaultSettings().
   */
  public function defaultSettings() {
    $defaults = array('auto_accept' => FALSE);
    // Check if any default settings are defined in the plugin info.
    if (isset($this->pluginDefinition['default_settings'])) {
      return array_merge($defaults, $this->pluginDefinition['default_settings']);
    }
    return $defaults;
  }

  /**
   * Implements TranslatorPluginControllerInterface::checkoutInfo().
   */
  public function hasCheckoutSettings(Job $job) {
    return TRUE;
  }

  /**
   * Implements TranslatorPluginControllerInterface::acceptedDataItem().
   */
  public function acceptedDataItem(JobItem $job_item, array $key) {
    return TRUE;
  }
}

