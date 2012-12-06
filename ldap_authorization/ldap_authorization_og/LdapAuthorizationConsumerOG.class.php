<?php

/**
 * @file
 * class for ldap authorization of organic groups
 *
 * @see LdapAuthorizationConsumerAbstract for property
 *
 */

module_load_include('php', 'ldap_authorization', 'LdapAuthorizationConsumerAbstract.class');

class LdapAuthorizationConsumerOG extends LdapAuthorizationConsumerAbstract {

  public $consumerType = 'og_group';
  public $allowConsumerObjectCreation = FALSE;
  public $ogVersion = NULL; // 1, 2, etc.

  public $defaultConsumerConfProperties = array(
      'onlyApplyToLdapAuthenticated' => TRUE,
      'useMappingsAsFilter' => TRUE,
      'synchOnLogon' => TRUE,
      'revokeLdapProvisioned' => TRUE,
      'regrantLdapProvisioned' => TRUE,
      'createConsumers' => TRUE,
      );

  function __construct($consumer_type) {
    $this->ogVersion = ldap_authorization_og_og_version();
    $params = ldap_authorization_og_ldap_authorization_consumer();
    parent::__construct('og_group', $params['og_group']);
  }

  /**
   * @see LdapAuthorizationConsumerAbstract::createConsumer
   */

  public function createConsumer($consumer_id, $consumer) {

    list($entity_type, $group_name, $rid) = explode(':', $consumer_id);

    $group = @ldap_authorization_og2_get_group_from_name($entity_type, $group_name);
    if ($group) {
      return FALSE;
    }

    // create og group with name of $group_name of entity type $entity_type
    $entity_info = entity_get_info($entity_type);

    $new_group_created = FALSE;

    /**
     *
     * @todo
     * need to create new entity with title of $group_name here
     *
     */

    if ($new_group_created === FALSE) {
      // if role is not created, remove from array to user object doesn't have it stored as granted
      watchdog('user', 'failed to create og group %group_name in ldap_authorizations module', array('%group_name' => $group_name));
      return FALSE;
    }
    else {
      watchdog('user', 'created  og group %group_name in ldap_authorizations module', array('%group_name' => $group_name));
    }
    return TRUE;
  }

  /**
   * @see LdapAuthorizationConsumerAbstract::normalizeMappings
   */
  public function normalizeMappings($mappings) {

    $new_mappings = array();
    if ($this->ogVersion == 2) {
      $group_entity_types = og_get_all_group_bundle();
      foreach ($mappings as $i => $mapping) {

        $from = $mapping[0];
        $to = $mapping[1];
        $to_parts = explode('(raw: ', $to);

        $user_entered = $to_parts[0];
        $new_mapping = array(
          'from' => $from,
          'user_entered' => $user_entered,
          'valid' => TRUE,
          'error_message' => '',
        );

        if (count($to_parts) == 2) { // has simplified and normalized part in (). update normalized part as validation
          $to_normalized = trim($to_parts[1], ')');
          /**
           * users (node:35:1)
           * node:students (node:21:1)
           * faculty (node:33:2)
           * node:35:1 (node:35:1)
           * node:35 (node:35:1)
           */

          $to_simplified = $to_parts[0];
          $to_simplified_parts = explode(':', trim($to_simplified));
          $entity_type = (count($to_simplified_parts) == 1) ? 'node' : $to_simplified_parts[0];
          $role = (count($to_simplified_parts) < 3) ? OG_AUTHENTICATED_ROLE : $to_simplified_parts[2];
          $group_name = (count($to_simplified_parts) == 1) ? $to_simplified_parts[0] :  $to_simplified_parts[1];
          list($group_entity, $group_entity_id) = ldap_authorization_og2_get_group_from_name($entity_type, $group_name);
          $to_simplified = join(':', array($entity_type, $group_name));
        }
        else { // may be simplified or normalized, but not both
          /**
           * users
           * node:students
           * faculty
           * node:35:1
           * node:35
           */
          $to_parts = explode(':', trim($to));
          $entity_type = (count($to_parts) == 1) ? 'node' : $to_parts[0];
          $role = (count($to_parts) < 3) ? OG_AUTHENTICATED_ROLE : $to_parts[2];
          $group_name_or_entity_id = (count($to_parts) == 1) ? $to_parts[0] :  $to_parts[1];
          list($group_entity, $group_entity_id) = ldap_authorization_og2_get_group_from_name($entity_type, $group_name_or_entity_id);
          if ($group_entity) { // if load by name works, $group_name_or_entity_id is group title
            $to_simplified = join(':', array($entity_type, $group_name_or_entity_id));
          }
          else {
            $to_simplified = FALSE;
          }
          $simplified = (boolean)($group_entity);
          if (!$group_entity && ($group_entity = @entity_load_single($entity_type, $group_name_or_entity_id))) {
            $group_entity_id = $group_name_or_entity_id;
          }
        }
        if (!$group_entity) {
          $new_mapping['normalized'] = FALSE;
          $new_mapping['simplified'] = FALSE;
          $new_mapping['valid'] = FALSE;
          $new_mapping['error_message'] = t("cannot find matching group: !to", array('!to' => $to));
        }
        else {
          $role_id = is_numeric($role) ? $role : ldap_authorization_og2_rid_from_role_name($entity_type, $group_entity->type, $group_entity_id, $role);
          $roles = og_roles($entity_type,  $group_entity->type, 0, FALSE, TRUE);
          $role_name = is_numeric($role) ? $roles[$role] : $role;
          $to_normalized = join(':', array($entity_type, $group_entity_id, $role_id));
          $to_simplified = ($to_simplified) ? $to_simplified . ':' . $role_name : $to_normalized;
          $new_mapping['normalized'] = $to_normalized;
          $new_mapping['simplified'] = $to_simplified;
          if ($to == $to_normalized) {
            /**  if not using simplified notation, do not convert to simplified.
              this would create a situation where an og group
              can change its title and the authorizations change when the
              admin specified the group by entity id
            */
            $new_mapping['user_entered'] = $to;
          }
          else {
            $new_mapping['user_entered'] = $to_simplified . ' (raw: ' . $to_normalized . ')';
          }


        }
       // dpm("convert $to, to: $to_simplified ($to_normalized)");

        $new_mappings[] = $new_mapping;
      }
    //  dpm($new_mappings);
    }
    else { // og 1
      foreach ($mappings as $i => $mapping) {
        $new_mapping = array(
          'from' => $mapping[0],
          'user_entered' => $mapping[1],
          'normalized' => NULL,
          'simplified' => NULL,
          'valid' => TRUE,
          'error_message' => '',
        );

        $gid = NULL;
        $rid = NULL;
        $correct_syntax = "gid=43,rid=2 or group-name=students,role-name=member or node.title=students,role-name=member";
        $incorrect_syntax = t('Incorrect mapping syntax.  Correct examples are:') . $correct_syntax;
        $targets = explode(',', $mapping[1]);
        if (count($targets) != 2) {
          $new_mapping['valid'] = FALSE;
          $new_mapping['error_message'] = $incorrect_syntax;
          continue;
        }

        $group_target_and_value =  explode('=', $targets[0]);
        if (count($group_target_and_value) != 2) {
          $new_mapping['valid'] = FALSE;
          $new_mapping['error_message'] = $incorrect_syntax;
          continue;
        }
        $new_mapping['simplified'] = $group_target_and_value;
        list($group_target, $group_target_value) = $group_target_and_value;

        $role_target_and_value = explode('=', $targets[1]);
        if (count($role_target_and_value) != 2) {
          $new_mapping['valid'] = FALSE;
          $new_mapping['error_message'] = $incorrect_syntax;
          continue;
        }
        list($role_target, $role_target_value) = $role_target_and_value;

        if ($group_target == 'gid') {
          $gid = $group_target_value;
        }
        elseif ($group_target == 'group-name') {
          list($og_group, $og_node) = ldap_authorization_og1_get_group($group_target_value, 'group_name', 'object');
          if (is_object($og_group) && property_exists($og_group, 'gid') && $og_group->gid) {
            $gid = $og_group->gid;
          }
        }
        else {
          $entity_type_and_field = explode('.', $group_target);
          if (count($entity_type_and_field) != 2) {
            $new_mapping['valid'] = FALSE;
            $new_mapping['error_message'] = $incorrect_syntax;
            continue;
          }
          list($entity_type, $field) = $entity_type_and_field;

          $query = new EntityFieldQuery();
          $query->entityCondition('entity_type', $entity_type)
            ->fieldCondition($field, 'value', $group_target_value, '=')
            ->addMetaData('account', user_load(1)); // run the query as user 1

          $result = $query->execute();
          if (is_array($result) && isset($result[$entity_type]) && count($result[$entity_type]) == 1) {
            $entities = array_keys($result[$entity_type]);
            $gid = ldap_authorization_og1_entity_id_to_gid($entities[0]);
          }
        }

        if ($role_target == 'rid') {
          $rid = $role_target_value;
        }
        elseif ($role_target == 'role-name') {
          $rid = ldap_authorization_og_rid_from_role_name($role_target_value);
        }

        if ($gid && $rid) {
          $new_mapping['normalized'] = ldap_authorization_og_authorization_id($gid, $rid);
        }
        else {
          $new_mappings['normalized'] = FALSE;
        }
        $new_mappings[] = $new_mapping;
      }
    }

    return $new_mappings;
  }

/**
 * in organic groups 7.x-1.x, consumer ids are in form gid-rid such as 3-2, 3-3.  We want highest authorization available granted.
 * But, granting member role (2), revokes other roles such as admin in OG.  So for granting we want the order:
 * 3-1, 3-2, 3-3 such that 3-3 is retained.  For revoking, the order should not matter, but reverse sorting makes
 * intuitive sense.
 */

  public function sortConsumerIds($op, &$consumers) {
    if ($op == 'revoke') {
      arsort($consumers, SORT_STRING);
    }
    else {
      asort($consumers, SORT_STRING);
    }
  }

  /**
   * @see LdapAuthorizationConsumerAbstract::populateConsumersFromConsumerIds
   */

  public function populateConsumersFromConsumerIds(&$consumers, $create_missing_consumers = FALSE) {
    //debug('populateConsumersFromConsumerIds'); debug($consumers);
    // generate a query for all og groups of interest
    $gids = array();
    foreach ($consumers as $consumer_id => $consumer) {
      if (ldap_authorization_og_og_version() == 1) {
        list($gid, $rid) = explode('-', $consumer_id);
        $gids[] = $gid;
      }
      else  {
        //debug("populateConsumersFromConsumerIds.consumer_id=$consumer_id");
        list($entity_type, $gid, $rid) = explode(':', $consumer_id);
      }
      $gids[$entity_type][] = $gid;
    }
    if (ldap_authorization_og_og_version() == 1) {
      $og_group_entities = og_load_multiple($gids);
    }
    else {
      foreach ($gids as $entity_type => $gid_x_entity) {
        $og_group_entities[$entity_type] = @entity_load($entity_type, $gid_x_entity);
      }
    }

    foreach ($consumers as $consumer_id => $consumer) {
      if (ldap_authorization_og_og_version() == 1) {
        list($gid, $rid) = explode('-', $consumer_id);
        $consumer['exists'] = isset($og_group_entities[$gid]);
        if ($consumer['exists']) {
          $consumer['value'] = $og_group_entities[$gid];
          if (empty($consumer['name']) && property_exists($og_group_entities[$gid], 'title')) {
            $consumer['name'] = $og_group_entities[$gid]->title;
          }
          $consumer['name'] =  $consumer_id;
        }
        else {
          $consumer['value'] = NULL;
          $consumer['name'] = NULL;
        }

        $consumer['map_to_string'] = $consumer_id;
      }
      else  {
        list($entity_type, $gid, $rid) = explode(':', $consumer_id);
        $consumer['exists'] = isset($og_group_entities[$entity_type][$gid]);
        $consumer['value'] = ($consumer['exists']) ? $og_group_entities[$entity_type][$gid] : NULL;
        $consumer['map_to_string'] = $consumer_id;
        if (
          empty($consumer['name']) &&
          !empty($og_group_entities[$entity_type][$gid]) &&
          property_exists($og_group_entities[$entity_type][$gid], 'title')
        ) {
          $consumer['name'] = $og_group_entities[$entity_type][$gid]->title;
        }
      }

      if (!$consumer['exists'] && $create_missing_consumers) {
         // @todo if creation of og groups were implemented, function would be called here
         // this would mean mapping would need to have enough info to configure a group,
         // or settings would need to include a default group type to create (entity type,
         // bundle, etc.)
      }
      $consumers[$consumer_id] = $consumer;
    }
  }




  public function hasAuthorization(&$user, $consumer_id) {

    if ($this->ogVersion == 1) {
      list($gid, $rid) = @explode('-', $consumer_id);
      $roles = og_get_user_roles($gid, $uid);
      $result = (!empty($roles[$rid]));
    }
    else {
      $result = ldap_authorization_og2_has_consumer_id($consumer_id, $user->uid);
    }
    return $result;
  }

  public function flushRelatedCaches($consumers = NULL) {
    if ($this->ogVersion == 1) { // og 7.x-1.x
      og_invalidate_cache();
    }
    else { // og 7.x-2.x
      og_invalidate_cache(); //gids could be passed in here, but not implemented within og
    }
  }

/**
  * revoke an authorization
  *
  * @see ldapAuthorizationConsumerAbstract::revokeSingleAuthorization()
  *
  */

  public function revokeSingleAuthorization(&$user, $consumer_id, $consumer, &$user_auth_data, $reset = FALSE) {
    if (!$this->hasAuthorization($user, $consumer_id)) {
      og_invalidate_cache(); // if trying to revoke, but thinks not granted, flush cache
      if (!$this->hasAuthorization($user, $consumer_id)) {
        return TRUE;
      }
    }

    $watchdog_tokens =  array('%consumer_id' => $consumer_id, '%username' => $user->name,
      '%ogversion' => $this->ogVersion, '%function' => 'LdapAuthorizationConsumerOG.revokeSingleAuthorization()');

    if ($this->ogVersion == 1) {
      list($gid, $rid) = @explode('-', $consumer_id);
    }
    else {
      list($group_entity_type, $gid, $rid) = @explode(':', $consumer_id);
    }
    // make sure group exists, since og doesn't do much error catching.
    if (!empty($consumer['value'])) {
      $og_group = $consumer['value'];
    }
    else {
      $og_group = @entity_load_single($group_entity_type, $gid);
      if (!$og_group) {
        return FALSE; // group cannot be found
      }
    }

    if ($this->ogVersion == 1) { // og 7.x-1.x
      $users_group_roles = og_get_user_roles($gid, $user->uid);
    }
    else { // og 7.x-2.x
      $users_group_roles = og_get_user_roles($group_entity_type, $gid, $user->uid);
    }
    // CASE: revoke
    if (count($users_group_roles) == 1) {  // ungroup if only single role left
      if ($this->ogVersion == 1) { // og 7.x-1.x
        $entity = og_ungroup($gid, 'user', $user->uid, TRUE);
        if ($reset) {
          og_invalidate_cache();
        }
      }
      else { // og 7.x-2.x
        $entity = og_ungroup($group_entity_type, $gid, 'user', $user->uid);
        if ($reset) {
          og_invalidate_cache(array($gid));
        }
      }
      $result = (boolean)($entity);
      $watchdog_tokens['%action'] = 'og_ungroup';
    }
    else { // if more than one role left, just revoke single role.
      if ($this->ogVersion == 1) { // og 7.x-1.x
        og_role_revoke($gid, $user->uid, $rid);
        if ($reset) {
          og_invalidate_cache();
        }
      }
      else { // og 7.x-2.x
        og_role_revoke($group_entity_type, $gid, $user->uid, $rid);
        if ($reset) {
          og_invalidate_cache(array($gid));
        }
      }
      $watchdog_tokens['%action'] = 'og_role_revoke';
      $result = TRUE;
    }
    $watchdog_tokens['%result'] = '$result';
    if ($this->detailedWatchdogLog) {
      watchdog('ldap_authorization_og', '%function revoked: result=%result, gid=%gid, rid=%rid, action=%action for username=%username',
        $watchdog_tokens, WATCHDOG_DEBUG);
    }

    return $result;

  }

  /**
   * grant single authorization
   *
   * @see ldapAuthorizationConsumerAbstract::grantSingleAuthorization()
   *
   */
  public function grantSingleAuthorization(&$user, $consumer_id, $consumer, &$user_auth_data, $reset = FALSE) {
    $watchdog_tokens =  array(
      '%consumer_id' => $consumer_id,
      '%username' => $user->name,
      '%ogversion' => $this->ogVersion,
      '%function' => 'LdapAuthorizationConsumerOG.grantSingleAuthorization()'
    );

    if ($this->hasAuthorization($user, $consumer_id)) {
      og_invalidate_cache(); // if trying to grant, but things already granted, flush cache
      if ($this->hasAuthorization($user, $consumer_id)) {
        return TRUE;
      }
    }

    if (empty($consumer['exists'])) {
      if ($this->detailedWatchdogLog) {
        watchdog('ldap_auth_og', '%function consumer_id %consumer_id does not exist', $watchdog_tokens, WATCHDOG_DEBUG);
      }
      return FALSE;
    }

    if ($this->ogVersion == 1) {
      list($gid, $rid) = @explode('-', $consumer_id);
    }
    else {
      list($group_entity_type, $gid, $rid) = @explode(':', $consumer_id);
      $watchdog_tokens['%entity_type'] = $group_entity_type;
    }
    $watchdog_tokens['%gid'] = $gid;
    $watchdog_tokens['%rid'] = $rid;
    $watchdog_tokens['%uid'] = $user->uid;
    $watchdog_tokens['%entity_type'] = $group_entity_type;

    // CASE:  grant role
    if ($this->detailedWatchdogLog) {
      watchdog('ldap_auth_og', '%function calling og_role_grant(%entity_type, %gid, %uid, %rid). og version=%ogversion',
        $watchdog_tokens, WATCHDOG_DEBUG);
    }

    if ($this->ogVersion == 1) {
      $values = array(
        'entity type' => 'user',
        'entity' => $user,
        'state' => OG_STATE_ACTIVE,
        'membership type' => OG_MEMBERSHIP_TYPE_DEFAULT,
      );
      $user_entity = og_group($gid, $values);
      og_role_grant($gid, $user->uid, $rid);
      if ($reset) {
        og_invalidate_cache();
      }
    }
    else {
      $values = array(
        'entity_type' => 'user',
        'entity' => $user->uid,
        'field_name' => FALSE,
        'state' => OG_STATE_ACTIVE,
      );
      $og_membership = og_group($group_entity_type, $gid, $values);
      og_role_grant($group_entity_type, $gid, $user->uid, $rid);
      if ($reset) {
        og_invalidate_cache(array($gid));
      }
    }


    if ($this->detailedWatchdogLog) {
      watchdog('ldap_auth_og', '%function <hr />granted: entity_type=%entity_type gid=%gid, rid=%rid for username=%username',
      $watchdog_tokens, WATCHDOG_DEBUG);
    }
    return TRUE;

  }

  /**
   * @see ldapAuthorizationConsumerAbstract::usersAuthorizations
   */

  public function usersAuthorizations(&$user) {
    $authorizations = array();
    if ($this->ogVersion == 1) {
      $groups = og_load_multiple(og_get_all_group());
      $authorizations = array();
      if (is_object($user) && is_array($groups)) {
        foreach ($groups as $gid => $discard) {
          $roles = og_get_user_roles($gid, $user->uid);
          foreach ($roles as $rid => $discard) {
            $authorizations[] = ldap_authorization_og_authorization_id($gid, $rid);
          }
        }
      }
    }
    else { // og 7.x-2.x
      $user_entities = entity_load('user', array($user->uid));
      $memberships = og_get_entity_groups('user', $user_entities[$user->uid]);
      foreach ($memberships as $entity_type => $entity_memberships) {
        foreach ($entity_memberships as $og_membership_id => $gid) {
          $roles = og_get_user_roles($entity_type, $gid, $user->uid);
          foreach ($roles as $rid => $discard) {
            $authorizations[] =  ldap_authorization_og_authorization_id($gid, $rid, $entity_type);
          }
        }
      }
    }
    return $authorizations;
  }

  /**
   * @see ldapAuthorizationConsumerAbstract::convertToFriendlyAuthorizationIds
   */
  public function convertToFriendlyAuthorizationIds($authorizations) {
    $authorization_ids_friendly = array();
    foreach ($authorizations as $authorization_id => $authorization) {
      $authorization_ids_friendly[] = $authorization['name'] . '  (' . $authorization_id . ')';
    }
    return $authorization_ids_friendly;
  }

  /**
   * @see ldapAuthorizationConsumerAbstract::validateAuthorizationMappingTarget
   */
  public function validateAuthorizationMappingTarget($mapping, $form_values = NULL, $clear_cache = FALSE) {
    // these mappings have already been through the normalizeMappings() method, so no real querying needed here.

    $has_form_values = is_array($form_values);
    $message_type = NULL;
    $message_text = NULL;
    $pass = !empty($mapping['valid']) && $mapping['valid'] === TRUE;

    /**
     * @todo need to look this over
     *
     */
    if (!$pass) {
      $tokens = array(
        '!from' => $mapping['from'],
        '!user_entered' => $mapping['user_entered'],
        '!error' => $mapping['error_message'],
        );
      $message_text = '<code>"' . t('!map_to|!user_entered', $tokens) . '"</code> ' . t('has the following error: !error.', $tokens);
    }
    return array($message_type, $message_text);
  }

  /**
   * Get list of mappings based on existing Organic Groups and roles
   *
   * @param associative array $tokens of tokens and replacement values
   * @return html examples of mapping values
   */

  public function mappingExamples($tokens) {

    if ($this->ogVersion == 1) {
      $groups = og_get_all_group();
      $ogEntities = og_load_multiple($groups);
      $OGroles = og_roles(0);

      $rows = array();
      foreach ($ogEntities as $group) {
        foreach ($OGroles as $rid => $role) {
          $example =   "<code>ou=IT,dc=myorg,dc=mytld,dc=edu|gid=" . $group->gid . ',rid=' . $rid . '</code><br/>' .
            '<code>ou=IT,dc=myorg,dc=mytld,dc=edu|group-name=' . $group->label . ',role-name=' . $role . '</code>';
          $rows[] = array(
            $group->label,
            $group->gid,
            $role,
            $example,
          );
        }
      }

      $variables = array(
      'header' => array('Group Name', 'OG Group ID', 'OG Membership Type', 'example'),
      'rows' => $rows,
      'attributes' => array(),
      );
    }
    else {

      /**
       * OG 7.x-2.x mappings:
       * $entity_type = $group_type,
       * $bundle = $group_bundle
       * $etid = $gid where edid is nid, uid, etc.
       *
       * og group is: entity_type (eg node) x entity_id ($gid) eg. node:17
       * group identifier = group_type:gid; aka entity_type:etid e.g. node:17
       *
       * membership identifier is:  group_type:gid:entity_type:etid
       * in our case: group_type:gid:user:uid aka entity_type:etid:user:uid e.g. node:17:user:2
       *
       * roles are simply rids ((1,2,3) and names (non-member, member, and administrator member) in og_role table
       * og_users_roles is simply uid x rid x gid
       *
       * .. so authorization mappings should look like:
       *    <ldap group>|group_type:gid:rid such as staff|node:17:2
       */

      $og_fields = field_info_field(OG_GROUP_FIELD);
      $rows = array();
      $role_name = OG_AUTHENTICATED_ROLE;

      if (!empty($og_fields['bundles'])) {
        foreach ($og_fields['bundles'] as $entity_type => $bundles) {

          foreach ($bundles as $i => $bundle) {

            $query = new EntityFieldQuery();
            $query->entityCondition('entity_type', $entity_type)
              ->entityCondition('bundle', $bundle)
              ->range(0, 5)
              ->addMetaData('account', user_load(1)); // run the query as user 1
            $result = $query->execute();
            $entities = entity_load($entity_type, array_keys($result[$entity_type]));
            $i=0;
            foreach ($entities as $entity_id => $entity) {
              $i++;
              $rid = ldap_authorization_og2_rid_from_role_name($entity_type, $bundle, $entity_id, OG_AUTHENTICATED_ROLE);
              $title = (is_object($entity) && property_exists($entity, 'title')) ? $entity->title : '';
              $middle = ($title && $i < 3) ? $title : $entity_id;
              $group_role_identifier = ldap_authorization_og_authorization_id($middle, $rid, $entity_type);
              $example = "<code>ou=IT,dc=myorg,dc=mytld,dc=edu|$group_role_identifier</code>";
              $rows[] = array("$entity_type $title - $role_name", $example);

            }

          }
        }
      }

      $variables = array(
        'header' => array('Group Entity - Group Title - OG Membership Type', 'example'),
        'rows' => $rows,
        'attributes' => array(),
      );
    }

    $table = theme('table', $variables);
    $link = l(t('admin/config/people/ldap/authorization/test/og_group'), 'admin/config/people/ldap/authorization/test/og_group');

$examples =
<<<EOT

<br/>
Examples for some (or all) existing OG Group IDs can be found in the table below.
This is complex.  To test what is going to happen, uncheck "When a user logs on" in IV.B.
and use $link to see what memberships sample users would receive.

$table

EOT;
    $examples = t($examples, $tokens);
    return $examples;
  }

}
