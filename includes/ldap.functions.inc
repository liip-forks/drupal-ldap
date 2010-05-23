<?php

/***
 *
 *  these are functions that don't belong in server object
 *
 **/



function _ldap_api_get_server_objects($server, $module = NULL, $reset = FALSE) {
  
  return array(
    7 => array(
      'name' => 'UIUC Active Directory',
      'server_addr' => 'ad.uiuc.edu',
      'type' => 'Active Directory',
      'status' => 1,
    ),
    9 => array(
      'name' => 'UIUC Active Directory TextBed',
      'server_addr' => 'test.ad.uiuc.edu',
      'type' => 'Active Directory',
      'status' => 1,
    )
  );
  

  static $ldap_server_objects;
  
  if (is_numeric($server)) {  // $server is sid
    // $server param is sid of ldap_servers record
    if ($reset || ! isset($ldap_server_objects[$server]) ) {
      $ldap_server_object = new ldap_api_server($server);
    } else {
      $ldap_server_object = $ldap_server_objects[$server];
    }
    if ( (! $module) ||  in_array($module, $ldap_server_object->server_modules)) {
      return $ldap_server_object;
    }
  }
  elseif (is_array($server)) { // $server is array of params
    $ldap_server_object =  new ldap_api_server($server);
    $ldap_server_objects[$ldap_server_object->sid] = $ldap_server_object;
  }
  elseif (is_object($server) && get_class($server) == 'ldap_api_server' ) { // server is already object. why call?
    if ( (! $module) ||  in_array($module, $server->server_modules)) {
      $ldap_server_objects[$server->sid] = $server;
    }
  }
  elseif ($server === NULL) {  // no server param means get all servers
   
    $ldap_api_config = ldap_api_config_get();
    $ldap_server_configs = $ldap_api_config['ldap_servers'];
    if ($module) {  // filter off all the ldap servers that aren't used by a module
     foreach ($ldap_server_configs as $sid => $ldap_server_config) {
       if ( $ldap_server_config['module_data'][$module]['status'] == 1) {
         unset($ldap_server_configs[$sid]);
       } 
     }
    }
    foreach ($ldap_server_configs as $sid => $ldap_server_config) {
      $ldap_server_objects[$sid] =  new ldap_api_server($sid);  // ldap_api_server($sid);
    }
  }
  else {
    drupal_set_message('LDAP API function ldap_api_get_server_objects called with improper arguments.','warning');
  }
  
  return $ldap_server_objects;
}



function _ldap_api_encrypt($text) {
  
  $encryption_module = variable_get("ldap_api_encryption","none");
  //dpm($encryption_module ."=". $encryption_module );
    if ($encryption_module == "aes") {
      $encrypted = aes_encrypt($text);
      $encryption = "aes|". variable_get("aes_cipher", "");
      
    } elseif ($encryption_module == "encrypt") {
      $encrypted = encrypt($text);
      $encryption = "encrypt|". variable_get('encrypt_default_method', ENCRYPT_DEFAULT_METHOD);
      
    } else {
      $encrypted = $text;
      $encryption = "none";
    }
    
    return array($encrypted, $encryption );
  }


function _ldap_api_decrypt($encrypted, $encryption) {
  
  if ($encryption == "none" || $encryption == "") {
    return $encrypted;
  }
  
  list($encryption_module, $encryption_type) = explode("|",$encryption);
  if (! module_exists($encryption_module)) {
     watchdog('ldap', 'LDAP Server bind password was encrypted with %module_name, but %module_name is not enabled.', array('%name' => $encryption_module));
     return FALSE;
  } else {
    if ($encryption_module == "encrypt") {
      return decrypt($encrypted);
    }
    if ($encryption_module == "aes") {
      return aes_decrypt($encrypted);
    }
    
  }

}
  

function _ldap_api_testldap($ldap, $sid) {
// given an ldap array with parameters, or class object, or $sid from ldap_servers,
// test bindings.  return array of passes and fails such as found server = true; bind = false

  return $test;
}

function _ldap_api_baddn($dn, $dn_name) {
  $result = array();
  $valid_attr_name = '[a-zA-Z\d\s]';
  $valid_attr_values = '[a-zA-Z\d\s]';
  $regex = '/^('. $valid_attr_name .'*\='. $valid_attr_values .'*[,]{1})*('. $valid_attr_name .'*\='. $valid_attr_values .'*){1}$/';
  // $target = "CN=Schema A1, CN2 =Configuration,DC=ad,DC=uiuc,DC=edu";
  // $target = "ou=education,dc=ad,dc=uiuc,dc=edu";
  $match = (preg_match($regex, $dn)) ? TRUE : FALSE;
  $result['boolean'] = $match;
  if (!$match) {
    $result['text'] = "Invalid format for:<br/> <code><strong>". htmlspecialchars($dn)
    ."</strong><code><br/> One cause may be editing $dn_name with a wysiwyg editor which leaves html.";

  }
  return $result;
}

function ldap_api_get_ldap_defaults($type = NULL) {
 
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




