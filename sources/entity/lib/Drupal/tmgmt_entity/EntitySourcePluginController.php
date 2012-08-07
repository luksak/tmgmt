<?php

/**
 * @file
 * Contains Drupal\tmgmt_entity\EntitySourcePluginController.
 */

namespace Drupal\tmgmt_entity;

use Drupal\tmgmt\DefaultSourcePluginController;
use Drupal\tmgmt\Plugin\Core\Entity\JobItem;
use Drupal\tmgmt\TMGMTException;

/**
 * Entity source plugin controller.
 */
class EntitySourcePluginController extends DefaultSourcePluginController {

  public function getLabel(JobItem $job_item) {
    if ($entity = entity_load($job_item->item_type, $job_item->item_id)) {
      return $entity->labe();
    }
  }

  public function getUri(JobItem $job_item) {
    if ($entity = entity_load($job_item->item_type, $job_item->item_id)) {
      return $entity->uri();
    }
  }

  /**
   * Implements TMGMTEntitySourcePluginController::getData().
   *
   * Returns the data from the fields as a structure that can be processed by
   * the Translation Management system.
   */
  public function getData(JobItem $job_item) {
    $entity = entity_load($job_item->item_type, $job_item->item_id);
    if (!$entity) {
      throw new TMGMTException(t('Unable to load entity %type with id %id', array('%type' => $job_item->item_type, $job_item->item_id)));
    }
    $properties = $entity->getPropertyDefinitions();
    $translatable_properties = array_filter($properties, function ($definition) {
      return !empty($definition['translatable']);
    });

    $data = array();
    $translation = $entity->getTranslation($job_item->getJob()->source_language);
    foreach ($translatable_properties as $key => $property_definition) {
      $property = $translation->get($key);
      $data[$key]['#label'] = $property_definition['label'];
      foreach ($property as $index => $property_item) {
        $data[$key][$index]['#label'] = t('Delta #@delta', array('@delta' => $index));
        $item_definitions = $property_item->getPropertyDefinitions();
        foreach ($item_definitions as $item_key => $item_definition) {
          // Ignore computed values.
          if (!empty($item_definition['computed'])) {
            continue;
          }

          $translate = TRUE;
          // @todo: Ignore properties with limited allowed values.
          if ($item_key == 'format' || !in_array($item_definition['type'], array('string'))) {
            $translate = FALSE;
          }
          $data[$key][$index][$item_key] = array(
            '#label' =>$item_definition['label'],
            '#text' => $property[$index]->$item_key,
            '#translate' => $translate,
          );
        }
      }
    }
    return $data;
  }

  /**
   * Implements TMGMTEntitySourcePluginController::saveTranslation().
   */
  public function saveTranslation(JobItem $job_item) {
    $entity = entity_load($job_item->item_type, $job_item->item_id);
    $job = tmgmt_job_load($job_item->tjid);

    tmgmt_field_populate_entity($entity, $job->target_language, $job_item->getData());
    $entity->save();
    $job_item->accepted();
  }

  /**
   * Implements TMGMTEntitySourcePluginControllerInterface::getType().
   */
  public function getType(JobItem $job_item) {
    if ($entity = entity_load($job_item->item_type, $job_item->item_id)) {
      $bundles = tmgmt_entity_get_translatable_bundles($job_item->item_type);
      $info = entity_get_bundles($job_item->item_type);
      $bundle = $entity->bundle();
      // Display entity type and label if we have one and the bundle isn't
      // the same as the entity type.
      if (isset($bundles[$bundle]) && $bundle != $job_item->item_type) {
        return t('@type (@bundle)', array('@type' => $info['label'], '@bundle' => $bundles[$bundle]));
      }
      // Otherwise just display the entity type label.
      elseif (isset($info['label'])) {
        return $info['label'];
      }
      return parent::getType($job_item);
    }
  }


}
