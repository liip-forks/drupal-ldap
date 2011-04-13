<?php
// $Id: ldap_authorization.inc,v 1.3.2.3 2011/02/18 15:06:09 johnbarclay Exp $

/**
 * @file
 *  bulk of authorization code executed to determine a users authorizations
 */


/**
 * return all desired authorizations for a given user
 *

 * @param object $user
 *
 * @param string $op =
 *   set -- grant authorizations and return authorizations
 *   test_query -- don't grant authorization, just query and return authorizations.  assume user is ldap authenticated and exists
 *   query -- don't grant authorization, just query and return authorizations
 *
 *
 * @param string $context  'logon'
 *
 * @return
 *
 *   LDAP_AUTHORIZATION_NO_LDAP_SERVERS if no servers configured
 *   LDAP_AUTHORIZATION_LDAP_ERROR if ldap error
 *   TRUE if servers configured but no roles derived from ldap
 *   array of potential authorizations (user may or may not already have these)
 *
 *   by reference $user->data[<consumer_type>][<authorization_id>] = array();
 *      e.g.   $var['drupal_role']['content_admin'] = array('rid' => 4)
 *      e.g.   $var['og_membership']['bakers club'] = array('expires' => '01/01/2012');
 *
 */


function _ldap_authorizations_user_authorizations(&$user, $op, $consumer_type, $context) {

  $detailed_watchdog_log = variable_get('ldap_help_watchdog_detail', 0);
  $authorizations = array();
  $notifications = array();
  $watchdog_tokens = array('%username' => $user->name);
  $consumers = ldap_authorization_get_consumers($consumer_type);
  $servers = ldap_servers_get_servers( NULL, 'enabled', TRUE);

  /**
   * user 1 not used in ldap authorization.  this is a design decision.
   */
  if (property_exists($user, 'uid') && $user->uid == 1) {
    if ($detailed_watchdog_log) {
      watchdog('ldap_authorization', '%username : ldap_authorization not applied to user 1', $watchdog_tokens, WATCHDOG_DEBUG);
    }
    $notifications['all'] = LDAP_AUTHORIZATION_NOT_APPLY_USER_1;
    return array($authorizations, $notifications);
  }

  /**
   * determine if user is ldap authenticated
   */
  if ($op == 'test_query' && @$user->ldap_test == TRUE) {
    $ldap_authenticated = $user->ldap_authenticated;  // property 'ldap_authenticated' only exists for fake user objects
  }
  else {
    $ldap_authenticated = (boolean)(module_exists('ldap_authentication') && ldap_authentication_ldap_authenticated($user));
  }
  $watchdog_tokens['%ldap_authenticated'] = ($ldap_authenticated) ? 'yes' : 'no';



  foreach ($consumers as $consumer_type => $consumer) {

    /**
    * each consumer type has only one consumer conf and each consumer conf has only one ldap server id (sid)
    * so there is a one-to-one-to-one relationship between:
    *   - consumer object ($consumer),
    *   - server object ($ldap_server),
    *   - and consumer conf object.
    *
    *  @todo 7.x-2.x so this insides of this loop probably belong in the consumer mapping abstract class
    *   as well as the function _ldap_authorizations_user_authorizations_set
    *
    */

    $consumer = ldap_authorization_get_consumer_object($consumer_type);
    $proposed_ldap_authorizations = array();
    $watchdog_tokens['%consumer_type'] = $consumer_type;
    $watchdog_tokens['%sid'] = $consumer->consumerConf->sid;

    if (! is_object($consumer->consumerConf)) {
      if ($detailed_watchdog_log) {
        watchdog('ldap_authorization', '%username : consumer type  %consumer_type has no
          configuration set.', $watchdog_tokens, WATCHDOG_DEBUG);
      }
      continue;
    }

    if ($detailed_watchdog_log) {
      watchdog('ldap_authorization', '%username : testing with
         consumer type %consumer_type. ldap authenticated=%ldap_authenticated', $watchdog_tokens, WATCHDOG_DEBUG);
    }

    if ($context == 'logon' && !$consumer->consumerConf->synchOnLogon) {
      $notifications[$consumer_type][] = LDAP_AUTHORIZATION_MAP_NOT_CONF_FOR_LOGON;
      if ($detailed_watchdog_log) {
        watchdog('ldap_authorization', '%username : %consumer_type no set to run on user logon.', $watchdog_tokens, WATCHDOG_DEBUG);
      }
      continue;
    }

    if ($consumer->consumerConf->onlyApplyToLdapAuthenticated  && !$ldap_authenticated  && $op != 'test_query') {
      if ($detailed_watchdog_log) {
        watchdog('ldap_authorization', '%username : not used because it is set to be applied only to ldap authenticated users.
            %username  is not ldap authenticated.', $watchdog_tokens, WATCHDOG_DEBUG);
      }
      $notifications[$consumer_type][] = LDAP_AUTHORIZATION_USER_NOT_LDAP_AUTHENTICATED;
      continue;
    }

    if (! ($user_ldap_entry = ldap_servers_get_user_ldap_data($user, $consumer->consumerConf->sid))) {
      $notifications[$consumer_type][] = LDAP_AUTHORIZATION_USER_LDAP_NOT_FOUND;
      if ($detailed_watchdog_log) {
        watchdog('ldap_authorization', '%username : %consumer_type ldap user not found.', $watchdog_tokens, WATCHDOG_DEBUG);
      }
      continue;
    }

    if (! isset($servers[$consumer->consumerConf->sid])) {
      $notifications[$consumer_type][] = LDAP_AUTHORIZATION_SERVER_CONFIG_NOT_FOUND;
      if ($detailed_watchdog_log) {
        watchdog('ldap_authorization', '%username : %consumer_type ldap server %sid not enabled or found.', $watchdog_tokens, WATCHDOG_DEBUG);
      }
      continue;
    }

    $ldap_server = $servers[$consumer->consumerConf->sid];

    /**
     * 1. first just need to figure out what authz_ids are generated for this consumer type/mapping configuration
     *
     * goal here is simply to build an array of authorizations for this ldap authz mapping
     * $proposed_ldap_authorizations[<authorization id>] = properties associative array or empty array
     *  e.g.  $proposed_ldap_authorizations['admin'] = array()
     *
     * the authorization ids may represent drupal roles, organic groups, civicrm groups, etc.
     * these mappings are a function of:
     *   -  drupal user entry, $user
     *   -  a user ldap entry, $user_ldap_entry
     *   -  an ldap server configuration, $ldap_server
     *   -  a mapping configuration ($consumer_conf)
     */

    ldap_authorization_maps_alter_invoke($user, $user_ldap_entry, $ldap_server, $consumer->consumerConf, $proposed_ldap_authorizations, 'query');
    if ($detailed_watchdog_log) {
      $watchdog_tokens['%proposed_authorizations'] = join(', ', $proposed_ldap_authorizations);
      watchdog('ldap_authorization', '%username : initial proposed authorization for %consumer_type: %proposed_authorizations.',
        $watchdog_tokens, WATCHDOG_DEBUG);
    }

    /**
     * 2.  filter is both a whitelist and a mapping of an ldap results to an authorization id.
     * goal of this step is to generate $filtered_ldap_authorizations[$consumer_type]
     * an array of filtered and mapped authorization ids
     */

    $filtered_ldap_authorizations = array();
    if ($consumer->consumerConf->useMappingsAsFilter) {
      foreach ($consumer->consumerConf->mappings as $mapping_filter) {
        $map_from = $mapping_filter[0];
        $map_to = $mapping_filter[1];
        if (isset($proposed_ldap_authorizations[$map_from]) || isset($proposed_ldap_authorizations[strtolower($map_from)])) {
          $filtered_ldap_authorizations[] = $map_to;
        }
      }
    }
    else {
      $filtered_ldap_authorizations = array_keys($proposed_ldap_authorizations);
    }
    if ($detailed_watchdog_log) {
      $watchdog_tokens['%filtered_ldap_authorizations'] = join(', ', $filtered_ldap_authorizations);
      watchdog('ldap_authorization', '%username : filtered authorization for %consumer_type: %filtered_ldap_authorizations.',
        $watchdog_tokens, WATCHDOG_DEBUG);
    }

    /**
     * 3. third, grant any proposed authorizations not already granted
     */
    if ($op == 'set') {
      _ldap_authorizations_user_authorizations_set($user, $consumer, $filtered_ldap_authorizations, $ldap_entry, $watchdog_tokens);
    }

    $authorizations[$consumer_type] = $filtered_ldap_authorizations;
  } //  end foreach $consumer type
  return array($authorizations, $notifications);

}

function _ldap_authorizations_user_authorizations_set(&$user, $consumer, $filtered_ldap_authorizations, &$ldap_entry, $watchdog_tokens) {
  $detailed_watchdog_log = variable_get('ldap_help_watchdog_detail', 0);
  $user_edit = array(); // all user data is stored here until user_save is called.

  /**
   * A.  Determine what authorizations have been granted in the past by ldap authorization
   */

  if (isset($user->data['ldap_authorizations'][$consumer->consumerType]) && is_array($user->data['ldap_authorizations'][$consumer->consumerType])) {
    $initial_existing_ldap_authorizations = array_keys($user->data['ldap_authorizations'][$consumer->consumerType]);
  }
  else {
    $initial_existing_ldap_authorizations = array();
  }

  /**
   * B. if regrantLdapProvisioned is false, $creates array should only be new authorizations
   */

  if (isset($user->data['ldap_authorizations'][$consumer->consumerType]) && $consumer->consumerConf->regrantLdapProvisioned === FALSE) {
    $existing_ldap_authorizations = $initial_existing_ldap_authorizations;
    $creates = array_diff($filtered_ldap_authorizations, $existing_ldap_authorizations);
  }
  else {
    $existing_ldap_authorizations = array();
    $creates = $filtered_ldap_authorizations;
  }
  $watchdog_tokens['%creates'] = join(', ', $creates);


   /**
   * C.  query or create existing authorization consumer ids (drupal roles, og groups etc.)
   *       doesn't matter if an authorization ids exists if the container constructs don't exist.
   */
  $consumer_containers_existing = $consumer->availableConsumerIDs();
  $containers_needed = array_diff($creates, $consumer_containers_existing);
  $watchdog_tokens['%consumer_containers_initial'] = join(', ', $consumer_containers_existing);
  if ($consumer->consumerConf->createConsumers && count($containers_needed) > 0) {
    $watchdog_tokens['%consumer_containers_needed'] = join(', ', $containers_needed);
    $consumer->createConsumers($containers_needed);
    $consumer_containers_existing = $consumer->availableConsumerIDs();  // requery in case of failure
  }
  $watchdog_tokens['%consumer_containers_final'] = join(', ', $consumer_containers_existing);

   /**
   * D.  Only grant authorization consumer ids that exist
   */
  $grants = array_intersect($consumer_containers_existing, $creates);

  /**
   * E. Do grants
   */

  $consumer->authorizationGrant($user, $user_edit, $grants, $ldap_entry, FALSE);
  $watchdog_tokens['%grants'] = join(', ', $grants);



  /**
   *  3.F take away any authorizations not in proposed authorization,
   *      but previously granted by ldap
   */
  if ($consumer->consumerConf->revokeLdapProvisioned) {
    $revokes = array_diff($initial_existing_ldap_authorizations, $filtered_ldap_authorizations);
    if (count($revokes)) {
      $consumer->authorizationRevoke($user, $user_edit, $revokes, $ldap_entry, FALSE);
    }
    $watchdog_tokens['%revokes'] = join(', ', $revokes);
  }

  if ($detailed_watchdog_log) {
    watchdog('ldap_authorization', '%username : user_authorizations_set results for %consumer_type:
      <hr/>authorization ids to add: %creates
      <hr/>revokes: %revokes
      <hr/>grants: %grants
      <hr/>consumer containers initially existing: %consumer_containers_initial
      <hr/>consumer containers needed to create: %consumer_containers_needed
      <hr/>consumer containers existing after create call: %consumer_containers_final
      ',
      $watchdog_tokens, WATCHDOG_DEBUG);
  }

  /**
   *  3.G  save user object and user data
   */

  $user = user_save($user, $user_edit);
}

function _ldap_authorization_ldap_authorization_maps_alter(&$user, &$user_ldap_entry, &$ldap_server, &$consumer_conf, &$authz_ids, $op) {

  $detailed_watchdog_log = variable_get('ldap_help_watchdog_detail', 0);
  $watchdog_tokens = array();

  // Strategy 1: group extracted from user's DN.
  $derive_from_dn_authorizations = array();
  if ($consumer_conf->deriveFromDn) {
    $pairs = explode(',', $user_ldap_entry['dn']);
    foreach ($pairs as $p) {
      $pair = explode('=', $p);
      if (drupal_strtolower(trim($pair[0])) == drupal_strtolower($consumer_conf->deriveFromDnAttr)) {
        $id =  trim($pair[1]);
        $derive_from_dn_authorizations[$id] = $id;
      }
    }
  }

  // Strategy 2: groups in user attributes
  $derive_from_attr_authorizations = array();
  if ($consumer_conf->deriveFromAttr) {
    foreach ($consumer_conf->deriveFromAttrAttr as $attribute) {
      if (isset($user_ldap_entry['attr'][strtolower($attribute)])) {
        // patch 1050944
        for ($i = 0; $i < $user_ldap_entry['attr'][strtolower($attribute)]['count']; $i++) {
          $derive_from_attr_authorizations[$user_ldap_entry['attr'][strtolower($attribute)][$i]] = $user_ldap_entry['attr'][strtolower($attribute)][$i];
        }
      }
    //  $attributes = ldap_servers_retrieveMultiAttribute($user_ldap_entry['ldap_dn'], $attribute);
    //  $attrib_authorizations = array_combine($attrib_authorizations, $attrib_authorizations);

    }


  }

  // Strategy 3: groups as entries.
  $derive_from_entry_authorizations = array();
 // $ldap_mapping->ldapgroups_entries_attribute
  if ($consumer_conf->deriveFromEntry) {
    foreach ($consumer_conf->deriveFromEntryEntries as $branch) {
      $entries = $ldap_server->search($consumer_conf->deriveFromEntryAttr . '=' . $user_ldap_entry['dn'], $branch, array('cn'));
      if (empty($entries) || $entries['count'] == 0) {
        $entries = $ldap_server->search($consumer_conf->deriveFromEntryAttr . '=' . $user->name, $branch, array('cn'));
        foreach ($entries as $entry) {
          if (isset($entry['cn'])) {
            $derive_from_entry_authorizations[$entry['cn'][0]] = $entry['cn'][0];
          }
          elseif (isset($entry['dn'])) {
            $derive_from_entry_authorizations[$entry['dn']] = $entry['dn'];
          }
        }
      }
    }

  }

  $authz_ids = array_merge($derive_from_dn_authorizations, $derive_from_attr_authorizations, $derive_from_entry_authorizations);

  if ($detailed_watchdog_log) {

    $watchdog_tokens['%username'] = $user->name;
    $watchdog_tokens['%ldap_server'] = $ldap_server->sid;
    $watchdog_tokens['%deriveFromDn'] = join(', ', array_keys($derive_from_dn_authorizations));
    $watchdog_tokens['%deriveFromAttr'] = join(', ', array_keys($derive_from_attr_authorizations));
    $watchdog_tokens['%deriveFromEntry'] = 'authorizations: ' . join(', ', array_keys($derive_from_entry_authorizations));
    $watchdog_tokens['%authz_ids'] =  join(', ', array_keys($authz_ids));

    watchdog('ldap_authorization', '%username :_ldap_authorization_ldap_authorization_maps_alter:
      <hr/>deriveFromDn authorization ids: %deriveFromDn
      <hr/>deriveFromAttr authorization ids: %deriveFromAttr
      <hr/>deriveFromEntry authorization ids: %deriveFromEntry
      <hr/>merged authz_ids authorization ids: %authz_ids
      ',
      $watchdog_tokens, WATCHDOG_DEBUG);
  }

}
