<?php

namespace Drupal\entity_activity_tracker_group\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker_group\Plugin\CreditGroupBase;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_group_content_creation",
 *   label = @Translation("Credit Group by content creation"),
 *   entity_types = {
 *     "group_content",
 *   },
 *   credit_related = "group",
 *   related_plugin = "entity_create",
 *   summary = @Translation("Upon content creation, credit Group"),
 * )
 */
class CreditGroupContentCreation extends CreditGroupBase {


}
