<?php

/**
 * @file
 * Contains the metadata controller classes for the local task translatio
 * entity.
 */

/**
 * Metadata controller for the local task entity.
 */
class TMGMTLocalTaskMetadataController extends EntityDefaultMetadataController {

  /**
   * Overrides EntityDefaultMetadataController::entityPropertyInfo().
   */
  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
//    $info = _tmgmt_override_property_description($info, $this->type);
    $properties = &$info[$this->type]['properties'];

    // Make the created and changed property appear as date.
    $properties['changed']['type'] = $properties['created']['type'] = 'date';

    // Add the options list for the defined status constants.
    $properties['status']['options list'] = 'tmgmt_local_task_statuses';

    // Link the job property to the corresponding job entity.
    $properties['job'] = array(
      'label' => t('Translation Job'),
      'type' => 'tmgmt_job',
      'description' => t('Corresponding job entity of the translation local task.'),
      'setter callback' => 'entity_property_verbatim_set',
      'setter permission' => 'administer tmgmt',
      'required' => TRUE,
      'schema field' => 'tjid',
    );

    // Link the author property to the corresponding user entity.
    $properties['author'] = array(
      'label' => t('Author'),
      'type' => 'user',
      'description' => t('The author of the translation local task.'),
      'setter callback' => 'entity_property_verbatim_set',
      'setter permission' => 'administer tmgmt',
      'required' => TRUE,
      'schema field' => 'uid',
    );

    // Link the author property to the corresponding user entity.
    $properties['translator'] = array(
      'label' => t('Translator'),
      'type' => 'user',
      'description' => t('The assigned translator for translation local task.'),
      'setter callback' => 'entity_property_verbatim_set',
      'setter permission' => 'administer tmgmt',
      'required' => TRUE,
      'schema field' => 'tuid',
    );

    return $info;
  }

}