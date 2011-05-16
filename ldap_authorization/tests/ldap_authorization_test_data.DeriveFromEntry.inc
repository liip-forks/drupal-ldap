<?php



  $test_data['ldap_authorization_conf']['consumer_conf']['sid'] = 'ldapauthor1';

  $test_data['ldap_authorization_conf']['consumer_conf']['consumerType'] = 'drupal_role';  // change as desired
  $test_data['ldap_authorization_conf']['consumer_conf']['consumerModule'] = 'ldap_authorization_drupal_role';

  $test_data['ldap_authorization_conf']['consumer_conf']['description'] = 'UIUC AD';
  $test_data['ldap_authorization_conf']['consumer_conf']['status'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['onlyApplyToLdapAuthenticated'] = 1;

  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromDn'] = 0;
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromDnAttr'] = NULL;

  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromAttr'] = 0;
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromAttrAttr'] = array();

// see http://drupal.org/node/1066608
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromEntry'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromEntryEntries'] = array('ou=groups,dc=ad,dc=myuniveristy,dc=edu');
  $test_data['ldap_authorization_conf']['consumer_conf']['deriveFromEntryAttr'] = 'member';

  $test_data['ldap_authorization_conf']['consumer_conf']['mappings'][] = array('ou=content editors,ou=groups,dc=ad,dc=myuniveristy,dc=edu', 'content editors');
  $test_data['ldap_authorization_conf']['consumer_conf']['mappings'][] = array('ou=content approvers,ou=groups,dc=ad,dc=myuniveristy,dc=edu', 'content approvers');


  $test_data['ldap_authorization_conf']['consumer_conf']['useMappingsAsFilter'] = 1;

  $test_data['ldap_authorization_conf']['consumer_conf']['synchOnLogon'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['synchManually'] = 1;

  $test_data['ldap_authorization_conf']['consumer_conf']['revokeLdapProvisioned'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['createConsumers'] = 1;
  $test_data['ldap_authorization_conf']['consumer_conf']['regrantLdapProvisioned'] = 1;
