<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the ldap_api module.
 */

function ldap_api_info() {
  drupal_add_css(drupal_get_path('module', 'system') .'/system.css', 'module', 'all', FALSE);
  include_once(drupal_get_path('module', 'system') .'/system.admin.inc');

  $data = _ldap_api_get_ldap_settings();

  foreach($data as $title => $value) {
    if (! is_array($value)) {
      $status[] = array('title' => $title, 'value' => $value);
    }
  }
  return theme_status_report($status);
}

/**
 * Returns an array of valid encryption types.
 *
 *  *Most of the code here is from phpLDAPadmin.
 */
function _ldap_api_get_ldap_settings() {

  $php_settings = array();
  $phpinfo = _ldap_api_parsePHPModules();

  $php_settings['PHP Version'] = phpversion();
  $php_settings['Drupal Version'] = VERSION;
  $php_settings['LDAP Extension Loaded'] = extension_loaded('ldap') ? 'Yes' : 'No';

  // set status array to be converted into html table.
  if ($phpinfo['Apache Environment']) {
    $php_settings['SERVER_SOFTWARE'] = $phpinfo['Apache Environment']['SERVER_SOFTWARE'];
  } elseif ( $_SERVER["SERVER_SOFTWARE"]) {
     $php_settings['SERVER_SOFTWARE'] = $_SERVER["SERVER_SOFTWARE"];
  }
  
  foreach ($phpinfo['ldap'] as $key => $value) {
    $php_settings[$key] = $value[0];
  }

  $modules = module_rebuild_cache();
  $ldapmodules = array('ldap_api', 'ldap_authn', 'ldap_authz', 'ldap_authentication', 'ldap_authorization',);
  foreach ($ldapmodules as $ldapmodule) {
    $php_settings[$ldapmodule]['status'] = $modules[$ldapmodule]->status;
    $php_settings[$ldapmodule]['schema_version'] = $modules[$ldapmodule]->schema_version;
    $php_settings[$ldapmodule]['version'] = $modules[$ldapmodule]->info['version'];
  }

  return $php_settings;
}

function _ldap_api_parsePHPModules() {
  ob_start();
  phpinfo();
  $s = ob_get_contents();
  ob_end_clean();

  $s = strip_tags($s, '<h2><th><td>');
  $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', "<info>\\1</info>", $s);
  $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', "<info>\\1</info>", $s);
  $vtmp = preg_split('/(<h2>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
  $vmodules = array();
  for ($i=1; $i<count($vtmp); $i++) {
    if (preg_match('/<h2>([^<]+)<\/h2>/', $vtmp[$i], $vmat)) {
      $vname = trim($vmat[1]);
      $vtmp2 = explode("\n", $vtmp[$i+1]);
      foreach ($vtmp2 AS $vone) {
        $vpat = '<info>([^<]+)<\/info>';
        $vpat3 = "/$vPat\s*$vpat\s*$vpat/";
        $vpat2 = "/$vPat\s*$vpat/";
        if (preg_match($vpat3, $vone, $vmat)) { // 3cols
          $vmodules[$vname][trim($vmat[1])] = array(trim($vmat[2]), trim($vmat[3]));
        }
        elseif (preg_match($vpat2, $vone, $vmat)) { // 2cols
          $vmodules[$vname][trim($vmat[1])] = trim($vmat[2]);
        }
      }
    }
  }
  return $vmodules;
}


// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
