<?php

namespace Drupal\entity_activity_tracker_group\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker_group\Plugin\CreditGroupMembershipBase;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_group_membership_comment_creation",
 *   label = @Translation("Credit author group membership"),
 *   entity_types = {
 *     "comment",
 *   },
 *   credit_related = "group_content",
 *   related_plugin = "entity_create",
 *   summary = @Translation("Upon comment creation, credit author membership"),
 * )
 */
class CreditGroupMembershipCommentCreation extends CreditGroupMembershipBase {


}
