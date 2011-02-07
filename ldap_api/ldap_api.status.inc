<?php

/**
 * implements hook_ldap_api_status
 */

function _ldap_api_ldap_api_status() {
  $statuses['version']['title'] = 'LDAP API Version';
  $statuses['version']['markup'] = ldap_api_version('html');

  $statuses['ldap_extension_summary']['title'] = 'This server\'s PHP LDAP extension';
  $statuses['ldap_extension_summary']['markup'] = ldap_api_ldap_extension_summary('html');
  
  $statuses['modules']['title'] = 'Modules Using LDAP API';
  $statuses['modules']['markup'] = ldap_api_modules('html');
  
  $statuses['drupal_dot_org']['title'] = 'LDAP API on drupal.org';
  $statuses['drupal_dot_org']['markup'] = ldap_api_drupal_dot_org('html');
  
  return $statuses;
}

function ldap_api_version($op) {
  $version_info = ldap_api_version_info();
  return "CVS TAG: ". $version_info['cvs_tag'] .  ", RELEASE DATE: ". $version_info['release_date'];
}


function _ldap_api_ldap_extension_summary($op) {
  
  include_once(drupal_get_path('module', 'system') .'/system.admin.inc');
  
  $data = _ldap_api_get_ldap_settings();
  foreach($data as $title => $value) {
    if (! is_array($value)) {
      if ($op  == 'text') {
        $text .= "${title}: ${value}\n";
      } else {
        $status[] = array('title' => $title, 'value' => $value);
      }
    }
  }
  
  switch ($op) {
    case 'html':
      return theme_status_report($status);
      
    case 'text':
      return $text;
    
    case 'data':
      return $status;
  }
}

function ldap_api_modules($op) {
  $potential_modules = module_invoke_all('ldap_api_permission');
  return theme_item_list($potential_modules);
}

function ldap_api_drupal_dot_org($op) {
  
  $project_tag = 'ldap';
  $add_issue_url = "http://drupal.org/node/add/project-issue/$project_tag";
  $issues_url = "http://drupal.org/project/issues/$project_tag";
  $project_page = "http://drupal.org/project/$project_tag";
  $issue_start_body_prepulate = urlencode("\n\n\nINSTALL INFO:\nLDAP API VERSION: ". $ldap_api_info ."\n". ldap_api_ldap_extension_summary('text'));
  
  
  // see url syntax at: http://drupal.org/files/issues/512824_drupalorg_project_get_fixup.patch
  $links[] = l('LDAP API Project Page', $project_page);
  $links[] = l('View Active Bug Reports', $issues_url . "?text=&status=Open&priorities=All&categories=bug&version=All&component=All");
  $links[] = l('View Active Support Requests', $issues_url . "?text=&status=Open&priorities=All&categories=support&version=All&component=All");
  $links[] = l('Start Bug Report Issue (prepopulated with helpful data)', $add_issue_url . "?categories=bug&version=${version}&body=${issue_start_body_prepulate}");
  $links[] = l('Start Support Request Issue (prepopulated with helpful data)', $add_issue_url . "?categories=support&version=${version}&body=${issue_start_body_prepulate}");
  return theme_item_list($links);
  
}

function _ldap_api_get_ldap_settings() {
  
  $php_settings = array();
  $phpinfo = _ldap_api_parsePHPModules();

  $php_settings['php_version'] = phpversion();
  $php_settings['drupal_version'] = VERSION;
  $php_settings['ldap_extension_loaded'] = extension_loaded('ldap');

  // set status array to be converted into html table.
  if ($phpinfo['Apache Environment']) {
    $php_settings['SERVER_SOFTWARE'] = $phpinfo['Apache Environment']['SERVER_SOFTWARE'];
  } elseif ( $_SERVER["SERVER_SOFTWARE"]) {
     $php_settings['SERVER_SOFTWARE'] = $_SERVER["SERVER_SOFTWARE"];
  }
  
  
  foreach ($phpinfo['ldap'] as $key => $value) {
    $php_settings[$key] = $value[0];
  }

  $modules = system_get_module_data();
  $ldapmodules = array('ldapauth', 'ldapgroups', 'ldapdata', 'ldaphelp','ldap_api','ldap_server','ldap_authn','ldap_authz');
  foreach ( $ldapmodules as $ldapmodule) {
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


