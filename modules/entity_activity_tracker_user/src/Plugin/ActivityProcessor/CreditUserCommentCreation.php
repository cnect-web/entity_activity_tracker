<?php

namespace Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker_user\Plugin\CreditUserBase;

/**
 * Sets setting for comments and preforms the activity process for comments.
 *
 * @ActivityProcessor (
 *   id = "credit_user_comment_creation",
 *   label = @Translation("Credit User by comment creation"),
 *   entity_types = {
 *     "comment",
 *   },
 *   credit_related = "user",
 *   related_plugin = "user_create",
 *   summary = @Translation("Upon comment creation, credit author"),
 * )
 */
class CreditUserCommentCreation extends CreditUserBase {


}
