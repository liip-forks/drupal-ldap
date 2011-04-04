<?php
// $Id: ldap_authorization.inc,v 1.3.2.3 2011/02/18 15:06:09 johnbarclay Exp $

/**
 * return all desired authorizations for a given user based on ldap authorization mapping and filtering rules
 *

 * @param object $user
 *
 * @param string $op =
 *   set -- grant authorizations and return authorizations
 *   test_query -- don't grant authorization, just query and return authorizations.  assume user is ldap authenticated and exists
 *   query -- don't grant authorization, just query and return authorizations
 *
 * @param string $_mapping_id
 *   if empty, will check all mappings
 *
 * @param string $context  'logon'
 *
 * @param boolean $query_only   only determine authorizations, don't apply them.
 *
 * @return
 *
 *   LDAP_AUTHORIZATION_NO_LDAP_SERVERS if no servers configured
 *   LDAP_AUTHORIZATION_LDAP_ERROR if ldap error
 *   TRUE if servers configured but no roles derived from ldap
 *   array of potential authorizations (user may or may not already have these)
 *
 *   by reference $user->data[<mapping id>][<authorization_id>] = array();
 *      e.g.   $var['drupal_role']['content_admin'] = array('rid' => 4)
 *      e.g.   $var['og_membership']['bakers club'] = array('expires' => '01/01/2012');
 *
 */


function _ldap_authorizations_user_authorizations(&$user, $op, $_mapping_id, $context) {
  $detailed_watchdog_log = variable_get('ldap_help_watchdog_detail', 0);
  $debug = array('function' => '_ldap_authorizations_user_authorizations', 'user' => $user, 'op' =>  $op, 'ldap_mapping_id' => $_mapping_id );
  $authorizations = array();
  $tokens = array('%username' => $user->name);

  if ($mappings = ldap_authorizations_mappings($_mapping_id)) {
    $consumers = ldap_authorization_get_consumers();
    if ($op == 'test_query' && @$user->ldap_test == TRUE) {
      $ldap_authenticated = $user->ldap_authenticated;  // property 'ldap_authenticated' only exists for fake user objects
    } else {
      $ldap_authenticated = (boolean)(module_exists('ldap_authentication') && ldap_authentication_ldap_authenticated($user));
    }
    $user_edit = array(); // all user data is stored here until user_save is called.
    $proposed_ldap_authorizations = array();
    $filtered_ldap_authorizations = array();

    foreach ($mappings as $mapping_id => $mapping) {
      $tokens['%mapping_id'] = $mapping_id;

      if (property_exists($user, 'uid') && $user->uid == 1) {
        $authorizations[$mapping_id] = LDAP_AUTHORIZATION_NOT_APPLY_USER_1;
        continue;
      }

      if ($detailed_watchdog_log) {
        watchdog('ldap_authorization', '%username : testing with mapping %mapping_id', $tokens, WATCHDOG_DEBUG);
      }
      $debug[$mapping_id] = $mapping;
      $proposed_ldap_authorizations[$mapping_id] = array();
      if ($context == 'logon' && !$mapping->synchOnLogon) {
        $authorizations[$mapping_id] = LDAP_AUTHORIZATION_MAP_NOT_CONF_FOR_LOGON;
        if ($detailed_watchdog_log) {
          watchdog('ldap_authorization', '%username : mapping %mapping_id no set to run on user logon.', $tokens, WATCHDOG_DEBUG);
        }
        continue;
      }
      if ($mapping->onlyApplyToLdapAuthenticated  && !$ldap_authenticated  && $op != 'test_query') {
        if ($detailed_watchdog_log) {
          watchdog('ldap_authorization', '%username : mapping %mapping_id not used because it is set to be applied only to ldap authenticated users.
              %username  is not ldap authenticated.', $tokens, WATCHDOG_DEBUG);
        }
        $authorizations[$mapping_id] = LDAP_AUTHORIZATION_USER_NOT_LDAP_AUTHENTICATED;
        continue;
      }
      elseif (! ($user_ldap_entry = ldap_servers_get_user_ldap_data($user, $mapping->sid))) {
        $authorizations[$mapping_id] = LDAP_AUTHORIZATION_USER_LDAP_NOT_FOUND;
        if ($detailed_watchdog_log) {
          watchdog('ldap_authorization', '%username : mapping %mapping_id ldap user not found.', $tokens, WATCHDOG_DEBUG);
        }
        continue;
      }
      $ldap_server = ldap_servers_get_servers($mapping->sid, 'enabled', TRUE);

      /**
       * 1. first just need to figure out what authz_ids are generated from this ldap auth mapping configuration
       *
       * goal here is simply to build an array of authorizations for this ldap authz mapping
       * $proposed_ldap_authorizations[<consumer_type>][<authorization id>] = properties associative array or empty array
       *  e.g.  $proposed_ldap_authorizations['drupal_role']['admin'] = array()
       * the authorization ids may represent drupal roles, organic groups, civicrm groups, etc.
       * these mappings are a function of:
       *   -  drupal user entry, $user
       *   -  a user ldap entry, $user_ldap_entry
       *   -  an ldap server configuration, $ldap_server
       *   -  a mapping configuration ($ldap_authz_map_config)
       */

      ldap_authorization_maps_alter_invoke($user, $user_ldap_entry, $ldap_server, $mapping, $proposed_ldap_authorizations[$mapping_id], 'query');
      $debug['proposed_ldap_authorizations'] = $proposed_ldap_authorizations[$mapping_id];

      /**
       * 2.  filter is both a whitelist and a mapping of an ldap results
       *     to an authorization id.
       */

      if ($mapping->useMappingsAsFilter) {
        $filtered_ldap_authorizations[$mapping_id] = array();
        foreach($mapping->mappings as $mapping_filter) {
          $map_from = $mapping_filter[0];
          $map_to = $mapping_filter[1];
          if(isset($proposed_ldap_authorizations[$mapping_id][$map_from]) || isset($proposed_ldap_authorizations[$mapping_id][strtolower($map_from)])) {
            $filtered_ldap_authorizations[$mapping_id][] = $map_to;
          }
        }
      } else {
        $filtered_ldap_authorizations[$mapping_id] = array_keys($proposed_ldap_authorizations[$mapping_id]);
      }
      $debug['filtered_ldap_authorizations'][$mapping_id] = $filtered_ldap_authorizations[$mapping_id];


      if ($op == 'set') {

        $consumer =  ldap_authorization_get_consumer_object(array('consumer_type' =>  $mapping->consumerType));

        /**
         * 3. third, grant any proposed authorizations not already granted
         */

        /**
         * 3.A.  Determine what authorizations have been granted in the past
         * if regrantLdapProvisionedDefault is false
         * and remove them
         *
         */
        $debug['consumer'] = $consumer;

        if (isset($user->data['ldap_authorizations'][$mapping->consumerType]) && is_array($user->data['ldap_authorizations'][$mapping->consumerType])) {
          $initial_existing_ldap_authorizations = array_keys($user->data['ldap_authorizations'][$mapping->consumerType]);
        }
        else {
          $initial_existing_ldap_authorizations = array();
        }

        if (isset($user->data['ldap_authorizations'][$mapping->consumerType]) && $mapping->regrantLdapProvisioned === FALSE) {
          $existing_ldap_authorizations = $initial_existing_ldap_authorizations;
          $creates = array_diff($filtered_ldap_authorizations, $existing_ldap_authorizations);
        } else {
          $existing_ldap_authorizations = array();
          $creates = $filtered_ldap_authorizations;

        }
        $debug['creates'] = $creates;
         /**
         * 3.C.  query or create existing authorization target ids (drupal roles, og groups etc)
         */
        $consumer_containers_existing = array();
        if ($mapping->createTargets) {
          $consumer->createTargets($creates);
        }
        $consumer_containers_existing = $consumer->getAvailableTargetIDs();

         /**
         * 3.D.  Only grant to authorization target ids that exist
         *  requerying getAvailableTargetIDs() in 3.C. accounts for
         *  failure in 3.C. createTargets() to create a targetm id
         */
        $grants = array_intersect($consumer_containers_existing, $creates); //
        $debug['grants'] = $grants;
        /**
         * 3.E. Do grants
         */

        $consumer->authorizationGrant($user, $user_edit, $grants, $ldap_entry, FALSE);
        $debug['user_edit after authorizationGrant'] = $user_edit;


        /**
         *  3.F take away any authorizations not in proposed authorization,
         *      but previously granted by ldap
         */
        $revokes_result = array();
        $debug["mapping-revokeLdapProvisioned"] = $mapping->revokeLdapProvisioned;
        $debug["filtered_ldap_authorizations"] =  $filtered_ldap_authorizations;
        $debug["existing_ldap_authorizations"] =  $initial_existing_ldap_authorizations;
        $debug["array diff"] =  array_diff($initial_existing_ldap_authorizations, $filtered_ldap_authorizations);

        if ($mapping->revokeLdapProvisioned) {
          $revokes = array_diff($initial_existing_ldap_authorizations, $filtered_ldap_authorizations);
          if (count($revokes)) {
            $consumer->authorizationRevoke($user, $user_edit, $revokes, $ldap_entry, FALSE);
          }
        }

        $debug['user_edit after authorizationRevoke'] = $user_edit;

        /**
         *  3.G  save user object and user data
         */

        $debug['user_edit to save'] = $user_edit;

        $user = user_save($user, $user_edit);


       // $consumer->authorizationUserDataSync($user, $ldap_entry);

      }
      else {
      }// end if query only
      $authorizations[$mapping_id] =  $filtered_ldap_authorizations;
    } //  end foreach ($ldap_authz_map_configs
    return $authorizations;
  } else {
    return LDAP_AUTHORIZATION_NO_LDAP_SERVERS;
  }

}



function _ldap_authorization_ldap_authorization_maps_alter(&$user, &$user_ldap_entry, &$ldap_server, &$mapping, &$authz_ids, $op) {

  $debug = array(
    'hook' => 'ldap_authorization_ldap_authorization_maps_alter',
    'user' => $user,
    'user_ldap_entry' => $user_ldap_entry,
    'ldap_server' => $ldap_server,
    'ldap_authz_map_config' => $mapping
  );

  // Strategy 1: group extracted from user's DN.
  $derive_from_dn_authorizations = array();
  if ($mapping->deriveFromDn) {
    $pairs = explode(',', $user_ldap_entry['dn']);
    foreach ($pairs as $p) {
      $pair = explode('=', $p);
      if (drupal_strtolower(trim($pair[0])) == drupal_strtolower($mapping->deriveFromDnAttr)) {
        $id =  trim($pair[1]);
        $derive_from_dn_authorizations[$id] = $id;
      }
    }

    $debug['Strategy group extracted from users DN'] = array(
        'pairs' => $pairs,
        'attribute' => $mapping->deriveFromDnAttr,
        'authz_ids' => $derive_from_dn_authorizations

      );
  }

  // Strategy 2: groups in user attributes
  $derive_from_attr_authorizations = array();
  if ($mapping->deriveFromAttr) {
    foreach ($mapping->deriveFromAttrAttr as $attribute) {
      if (isset($user_ldap_entry['attr'][strtolower($attribute)])) {
        // patch 1050944
        for($i = 0; $i < $user_ldap_entry['attr'][strtolower($attribute)]['count']; $i++) {
          $derive_from_attr_authorizations[$user_ldap_entry['attr'][strtolower($attribute)][$i]] = $user_ldap_entry['attr'][strtolower($attribute)][$i];
        }
      }
    //  $attributes = ldap_servers_retrieveMultiAttribute($user_ldap_entry['ldap_dn'], $attribute);
    //  $attrib_authorizations = array_combine($attrib_authorizations, $attrib_authorizations);
     $debug['Strategy groups in user attributes']  = array(
          'attributes' => $mapping->deriveFromAttrAttr,
          'user attributes' => $user_ldap_entry['attr'],
          'derive_from_attr_authorizations' => $derive_from_attr_authorizations
        );
    }
  }

  // Strategy 3: groups as entries.
  $derive_from_entry_authorizations = array();
 // $ldap_mapping->ldapgroups_entries_attribute
  if ($mapping->deriveFromEntry) {
    foreach ($mapping->deriveFromEntryEntries as $branch) {
      $entries = $ldap_server->search($mapping->deriveFromEntryAttr .'='. $user_ldap_entry['dn'], $branch, array('cn'));
      if (empty($entries) || $entries['count'] == 0) {
        $entries = $ldap_server->search($mapping->deriveFromEntryAttr .'='. $user->name, $branch, array('cn'));
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


}
