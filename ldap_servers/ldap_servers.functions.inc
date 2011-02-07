<?php
// $Id$

/**
 * @file
 * collection of functions that don't belong in server object
 */


function _ldap_servers_testldap($ldap, $sid) {
// given an ldap array with parameters, or class object, or $sid from ldap_servers,
// test bindings.  return array of passes and fails such as found server = true; bind = false

  return $test;
}

function _ldap_servers_baddn($dn, $dn_name) {
  $result = array();
  $valid_attr_name = '[a-zA-Z\d\s]';
  $valid_attr_values = '[a-zA-Z\d\s]';
  $regex = '/^(' . $valid_attr_name . '*\=' . $valid_attr_values . '*[,]{1})*(' . $valid_attr_name . '*\=' . $valid_attr_values . '*){1}$/';
  // $target = "CN=Schema A1, CN2 =Configuration,DC=ad,DC=uiuc,DC=edu";
  // $target = "ou=education,dc=ad,dc=uiuc,dc=edu";
  $match = (preg_match($regex, $dn)) ? TRUE : FALSE;
  $result['boolean'] = $match;
  if (!$match) {
    $result['text'] = "Invalid format for:<br/> <code><strong>" . htmlspecialchars($dn)
    . "</strong><code><br/> One cause may be editing $dn_name with a wysiwyg editor which leaves html.";

  }
  return $result;
}

function ldap_servers_get_ldap_defaults($type = NULL) {

  $ldap['default']['name'] = t('Default LDAP');
  $ldap['default']['description'] = ""; 
  $ldap["default"]['server']['port'] = 389;
  $ldap["default"]['server']['tls'] = 1;
  $ldap["default"]['server']['encrypted'] = 0;
  $ldap["default"]['user']['mail_attr'] = 'mail';
  
    // http://msdn.microsoft.com/en-us/library/aa705886(VS.85).aspx
  $ldap['ad']['name'] = t('Active Directory LDAP');
  $ldap['ad']['description'] = t('Microsoft Active Directory');
  $ldap["ad"]['server']['port'] = 389;
  $ldap["ad"]['server']['tls'] = 1;
  $ldap["ad"]['server']['encrypted'] = 0;
  $ldap["ad"]['user']['user_attr'] = 'sAMAccountName';
  $ldap["ad"]['user']['mail_attr'] = 'mail';
  $ldap['ad']['person_object_class'] = 'person';
  $ldap['ad']['fingerprint_attribute'] = 'mS-DS-CreatorSID';
  
  // http://www.novell.com/documentation/edir873/index.html?page=/documentation/edir873/edir873/data/h0000007.html
  $ldap['novell_edir']['name'] = t('Novell eDirectory LDAP');
  $ldap['novell_edir']['description'] = t('Novell eDirectory LDAP');
  $ldap["novell_edir"]['server']['port'] = 389;
  $ldap["novell_edir"]['server']['tls'] = 1;
  $ldap["novell_edir"]['server']['encrypted'] = 0;
  $ldap["novell_edir"]['user']['user_attr'] = 'uid';
  $ldap["novell_edir"]['user']['mail_attr'] = 'mail';
  $ldap['novell_edir']['person_object_class'] = 'person';
  
  $ldap['openldap']['name'] = t('openLDAP LDAP');
  $ldap['openldap']['description'] = t('openLDAP default configuration');
  $ldap["openldap"]['server']['port'] = 389;
  $ldap["openldap"]['server']['tls'] = 1;
  $ldap["openldap"]['server']['encrypted'] = 0;
  $ldap["openldap"]['user']['user_attr'] = 'cn';
  $ldap["openldap"]['user']['mail_attr'] = 'mail';
  $ldap['openldap']['person_object_class'] = 'person';
  
  return ($type) ? $ldap[$type] : $ldap;
}




