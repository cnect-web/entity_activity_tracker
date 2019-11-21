<?php

namespace Drupal\entity_activity_tracker_group\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker_group\Plugin\CreditGroupBase;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_group_comment_creation",
 *   label = @Translation("Credit Group by comment creation"),
 *   entity_types = {
 *     "comment",
 *   },
 *   credit_related = "group",
 *   related_plugin = "entity_create",
 *   summary = @Translation("Upon comment creation, credit Group"),
 * )
 */
class CreditGroupCommentCreation extends CreditGroupBase {


}
