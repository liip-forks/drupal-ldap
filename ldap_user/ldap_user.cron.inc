<?php

/**
 * @file
 * cron relate functions
 *
 */

function _ldap_user_orphans($ldap_user_conf) {
  
  return; // this is unfinished code
  $action = $ldap_user_conf->orphanedDrupalAcctBehavior;
  $qty = ((int)($ldap_user_conf->orphanedCheckQty) > 0) ? $ldap_user_conf->orphanedCheckQty : 50;

  // query $qty ldap associated drupal accounts (ldap_user_current_dn not null) ordered by ldap_user_last_checked
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'user')
  ->fieldCondition('ldap_user_current_dn', 'value', 'NULL', '!=')
  ->propertyOrderBy('ldap_user_last_checked', 'ASC')
  ->range(0, $qty)
  ->addMetaData('account', user_load(1)); // run the query as user 1
  $result = $query->execute();
  
  
  // loop through all $qty and generate single ldap query for each 50 entries only returning dn
  $ldap_entries = array();
  $drupal_users = array();
  $orphans = array();
  if (isset($result['user']) && count($result['user']) > 0) {
    $batch = 1;
    $uids = array_keys($result['user']);
    $batches = (int)(count($uids) / 50) + 1;
    for ($batch=1; $batch <= $batches; $batch++) {
      $filters = array();
      for ($i = ($batch - 1)* 50; $i < ($batch)* 50; $i++ ) {
        $uid = $uids[$i];
        $user = $result['user'][$uid];
        $sid = $user['ldap_user_puid_sid'][0]['values'];
        $puid = $user['ldap_user_puid'][0]['values'];
        $puid_attr = $user['ldap_user_puid_property'][0]['values'];
        if ($sid && $puid && $puid_attr) {
          $filters[$sid][$puid_attr] = "($puid_attr=$puid)";
          $drupal_users[$sid][$puid_attr][$puid] = TRUE;
        }
      }
      foreach ($filters as $sid => $puid_attrs) {
        foreach ($puid_attrs as $puid_attr => $ORs) {
          $query = "|" . join('', $ORs);
         // $ldap_entries = $ldap_servers[$sid]->query(...)
         // if no error, loop through existing ones and populate {
        //    $puid = entry attr [$puid_attr][0]
         //   $ldap_entries[$sid][$puid_attr][$puid] = TRUE;
        //  }
        }
      }
    }
    // mark user records as checked in ldap_user_last_checked
    // compare $ldap_entries and $drupal_users and add any discrepencies to $orphans
    // generate email and perform mass action.  see uer module for mass functions
  } 

  
  
  
}

/**

 *0 (String, 29 characters ) ldap_user_orphan_do_not_check
1 (String, 22 characters ) ldap_user_orphan_email
2 (String, 17 characters ) user_cancel_block
3 (String, 27 characters ) user_cancel_block_unpublish
4 (String, 20 characters ) user_cancel_reassign
5 (String, 18 characters ) user_cancel_delete\
**/
