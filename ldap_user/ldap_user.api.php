<?php

/**
 * hook_ldap_user_targets_alter().
 *
 * alter list of available drupal user targets (fields, properties, etc.)
 *   for ldap_user provisioning mapping form (admin/config/people/ldap/user)
 *
 * return array with elements of the form:
 * <field_type>.<field_name> => array(
 *   'name' => <name>,
 *   'field_type' => <field_type>,
 *   'field_name' => <field_name>,
 *   'configurable' => 0 | 1,
 *   'configurable_text' => <why population cant be configured>
 *   )
 *
 * where
 *
 * 'field_name' machine name of property, field, profile2 field, or data associative array key
 * 'field_type' is one of the following:
 *   'property' (user property such as mail, picture, timezone that is not a field)
 *   'field' (any field attached to the user such as field_user_lname)
 *   'profile2' (profile2 fields)
 *   'data' ($user->data array.  field_name will be used as key such as $user->data[<field_name>] = mapped value
 * 'name' is the user friendly name for the UI
 * 'configurable' is this configurable in the ldap_user provisioning/synching interface?
 * 'configuratble_text' explanation of why not configurable for UI
 *
 */

function hook_ldap_user_targets_list_alter(&$available_user_targets, &$ldap_server) {

  $available_user_targets['property.name'] = array(
    'name' => 'Username',
    'configurable' => 0,
    'configurable_text' => 'Username is derived based on ldap server configuration.',
  );

  $available_user_targets['property.mail'] = array(
    'name' => 'Email',
    'configurable' => 0,
    'configurable_text' => 'Email is derived based on ldap server configuration.',
  );

  $available_user_targets['property.timezone'] = array(
    'name' => 'User Timezone',
    'configurable' => 1,
    'configurable_text' => NULL,
  );

  $available_user_targets['field.field_user_lname'] = array(
    'name' => 'Last Name',
    'configurable' => 1,
    'configurable_text' => NULL,
  );

  $available_user_targets['data.blah'] = array(
    'name' => 'Blah stored in user->data array',
    'configurable' => 1,
    'configurable_text' => NULL,
  );

  $available_user_targets['profile2.field_favorite_restaurant'] = array(
    'name' => 'Profile Favorite Restaurant',
    'configurable' => 1,
    'configurable_text' => NULL,
  );

}

/**
 * alter synch mapping provided in ldap_user module
 * before syching of ldap to drupal user fields
 */
function hook_ldap_user_synch_mapping_alter(&$synch_mapping) {
  


}
