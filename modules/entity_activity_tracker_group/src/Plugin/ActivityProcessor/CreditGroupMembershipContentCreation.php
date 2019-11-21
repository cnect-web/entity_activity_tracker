<?php

namespace Drupal\entity_activity_tracker_group\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker_group\Plugin\CreditGroupMembershipBase;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_group_membership_content_creation",
 *   label = @Translation("Credit author group membership"),
 *   entity_types = {
 *     "group_content",
 *   },
 *   credit_related = "group_content",
 *   related_plugin = "entity_create",
 *   summary = @Translation("Upon group content creation, credit author membership"),
 * )
 */
class CreditGroupMembershipContentCreation extends CreditGroupMembershipBase {


}
