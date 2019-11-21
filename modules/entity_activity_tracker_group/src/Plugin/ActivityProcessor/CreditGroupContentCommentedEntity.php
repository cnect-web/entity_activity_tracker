<?php

namespace Drupal\entity_activity_tracker_group\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker_group\Plugin\CreditGroupBase;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_group_content_commented_entity",
 *   label = @Translation("Credit Group Content Commented Entity"),
 *   entity_types = {
 *     "comment",
 *   },
 *   credit_related = "group_content",
 *   related_plugin = "entity_create",
 *   summary = @Translation("Upon comment creation, credit Group Content"),
 * )
 */
class CreditGroupContentCommentedEntity extends CreditGroupBase {


}
