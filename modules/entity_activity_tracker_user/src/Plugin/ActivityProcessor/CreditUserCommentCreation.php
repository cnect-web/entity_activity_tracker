<?php

namespace Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker_user\Plugin\CreditUserBase;


/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_user_comment_creation",
 *   label = @Translation("Credit User by comment creation"),
 *   entity_types = {
 *     "comment",
 *   },
 * )
 */
class CreditUserCommentCreation extends CreditUserBase {


}
