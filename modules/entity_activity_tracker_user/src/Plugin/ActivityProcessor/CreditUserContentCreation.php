<?php

namespace Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker_user\Plugin\CreditUserBase;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_user_content_creation",
 *   label = @Translation("Credit User by content creation"),
 *   entity_types = {
 *     "node",
 *   },
 *   credit_related = "user",
 *   related_plugin = "user_create",
 *   summary = @Translation("Upon content creation, credit author"),
 * )
 */
class CreditUserContentCreation extends CreditUserBase {


}
