<?php
// $Id$

require_once('includes/ldap.functions.inc');

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


/**
 * LDAP servers list.
 *
 * @return
 *   The HTML table with the servers list.
 */
function ldap_api_servers_list() {
  
  $column_values = array();
  $column_headers = array("Name", "Type", "Enabled", "Server Address", "Last Modified","Actions");
  $ldap_servers_config = ldap_api_config_get();
  
  if ($ldap_servers_config['ldap_servers']) {
    foreach($ldap_servers_config['ldap_servers'] as $sid => $ldap_server ) {
      $column_values[$sid][] = $ldap_server['name'];
      $column_values[$sid][] = $ldap_server['type'];
      $column_values[$sid][] = ($ldap_server['status'] == 1) ? "Yes" : "No";
      $column_values[$sid][] = $ldap_server['server_addr'];
      $column_values[$sid][] = format_date($ldap_server['changed'], "medium");
      
      $action_list = array('edit','delete',($ldap_server['status'] == 1) ? "disable" : "enable");
      $actions = array();
      foreach ($action_list as $action) {
        $actions[] = l(t($action), LDAP_API_MENU_BASE_PATH ."/servers/$action/". $sid);
      }
      $column_values[$sid][] = join(" | ",$actions); 
    }
  }
  $html .= theme_table($column_headers, $column_values, array(), NULL);
      
  return $html;
}

/**
 * A simple summary of an ldap server.
 *
 * @return
 *   The HTML table with the server summary.
 */
function ldap_api_servers_display_server_summary($sid) {
  $ldap_server_config = ldap_api_config_get(FALSE, $sid);
//dpm($ldap_server_config);
  $column_headers = array("ID","Name", "Type", "Enabled", "Server Address", "Last Modified");
  $column_values[] = array(
      $ldap_server_config['sid'],
      $ldap_server_config['name'],
      $ldap_server_config['type'],
      (($ldap_server_config['status'] == 1) ? "Yes" : "No"),
      $ldap_server_config['server_addr'],
      format_date($ldap_server_config['changed'], "medium")
      );

  $html .= theme_table($column_headers, $column_values, array(), NULL);
      
  return $html;
}


/**
 * Implements the LDAP server edit page.
 *
 * @param $form_state
 *   A form state array.
 * @param $op
 *   An operatin - add or edit.
 * @param $sid
 *   A LDAP server ID.
 *
 * @return
 *   The form structure.
 */
function ldap_api_servers_admin_form(&$form_state, $op = NULL, $sid = NULL) {

  if ($op == "edit" && $sid) {
    $edit = ldap_api_config_get(TRUE, $sid);
   // print_r($edit); die;
    $form['sid'] = array(
      '#type' => 'hidden',
      '#value' => $sid,
    );
  }
  else {
    $defaults = ldap_api_get_ldap_defaults("default");
    $edit = array(
      'port' => $defaults['server']['port'],
      'tls' => $defaults['server']['tls'],
      'status' => 1,
    );
  }

$form['#prefix'] = <<<EOF
<p>Setup an LDAP server configuration to be used by other modules such as LDAP AuthZ, LDAP AuthN, LDAP Groups, etc.</p>
<p>More than one LDAP server configuration can exist for a physical LDAP server.
This is useful when you need different configuration options for different LDAP modules
such as different base dns for authentication and authorization.</p> 
EOF;



  $form['server'] = array(
    '#type' => 'fieldset',
    '#title' => t('LDAP Server settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['server']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $edit['name'],
    '#description' => t('Choose a <em><strong>unique</strong></em> name for this server configuration.'),
    '#size' => 50,
    '#maxlength' => 255,
    '#required' => TRUE,
  );
  
  $form['server']['status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enabled'),
    '#default_value' => $edit['status'],
    '#description' => t('Disable in order to keep configuration without having it active.'),
  );
  
  $form['server']['type'] = array(
    '#type' => 'select',
    '#options' =>  ldap_api_ldaps_option_array(),
    '#title' => t('LDAP Server Type'),
    '#default_value' => $edit['type'],
    '#description' => t('This field is informative.  It\'s purpose is to assist with default values and give validation warnings.'),
    '#required' => FALSE,
  ); 
  $form['server']['server_addr'] = array(
    '#type' => 'textfield',
    '#title' => t('LDAP server'),
    '#default_value' => $edit['server_addr'],
    '#size' => 50,
    '#maxlength' => 255,
    '#description' => t('The domain name or IP address of your LDAP Server.'),
    '#required' => TRUE,
  );
  $form['server']['port'] = array(
    '#type' => 'textfield',
    '#title' => t('LDAP port'),
    '#default_value' => $edit['port'],
    '#size' => 5,
    '#maxlength' => 5,
    '#description' => t('The TCP/IP port on the above server which accepts LDAP connections. Must be an integer.'),
  );
  $form['server']['tls'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use Start-TLS'),
    '#default_value' => $edit['tls'],
    '#description' => t('Secure the connection between the Drupal and the LDAP servers using TLS.<br /><em>Note: To use START-TLS, you must set the LDAP Port to 389.</em>'),
  );


  $form['allowed_modules'] = array(
    '#type' => 'fieldset',
    '#title' => t('Allowed Modules'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
 //print "<pre>";
 // print_r($edit['module_data']['allowed_modules']);
  $potential_modules = module_invoke_all('ldap_api_permission');
  $allowed_modules = (is_array($edit['module_data']['allowed_modules'])) ? $edit['module_data']['allowed_modules'] : array();
  $form['allowed_modules']['allowed_modules'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Permit the following modules access to this server configuration'),
    '#default_value' => $allowed_modules,
    '#options' => $potential_modules,
  );
  
 //$values['module_data']['allowed_modules']
  
  $form['binding'] = array(
    '#type' => 'fieldset',
    '#title' => t('Server Binding Credentials'),
    '#description' => t('<p>Some LDAP configurations (specially common in <strong>Active Directory</strong> setups) restrict anonymous searches.</p><p>If your LDAP setup does not allow anonymous searches, or these are restricted in such a way that login names for users cannot be retrieved as a result of them, then you have to specify here a DN//password pair that will be used for these searches.</p><p>For security reasons, this pair should belong to an LDAP account with stripped down permissions.</p>'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  
  $form['binding']['binddn'] =  array(
    '#type' => 'textfield',
    '#title' => t('DN for non-anonymous search'),
    '#default_value' => $edit['binddn'],
    '#size' => 50,
    '#maxlength' => 255,
  );
  
  $form['binding']['bindpw'] = array(
    '#type' => 'password',
    '#title' => t('Password for non-anonymous search'),
    '#size' => 20,
    '#maxlength' => 255
  );
  
  if ( $form['binding']['bindpw']) {
       $form['binding']['bindpw']['#description'] = t('<p>Leave emtpy to leave password unchanged.</p>');
    }
    
  $form['binding']['test'] = array(
      '#type'  => 'submit',
      '#value' => t('Test Server Binding Credentials'),
    );

  $form['users'] = array(
    '#type' => 'fieldset',
    '#title' => t('LDAP User to Drupal User Relationship'),
    '#description' => t('How are LDAP user entries found based on Drupal username or email?  And vice-versa?  Needed for LDAP AuthZ and LDAP AuthN functionality.'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  
  $form['users']['basedn'] = array(
    '#type' => 'textarea',
    '#title' => t('Base DNs for LDAP user entries'),
    '#default_value' => $edit['basedn'],
    '#cols' => 50,
    '#rows' => 6,
    '#description' => t('What DNs have user accounts relavant to this configuration? ') . "e.g. <code>ou=campus accounts,dc=ad,dc=uiuc,dc=edu</code>  " . t('Enter one per line in case if you need more than one.'),
  );
  
  $form['users']['user_attr'] = array(
    '#type' => 'textfield',
    '#title' => t('UserName attribute'),
    '#default_value' => $edit['user_attr'],
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('The attribute that holds the users\' login name. (eg. <em style="font-style: normal; padding: 1px 3px; border: 1px solid #8888CC; background-color: #DDDDFF">cn</em> for eDir or <em style="font-style: normal; padding: 1px 3px; border: 1px solid #8888CC; background-color: #DDDDFF">sAMAccountName</em> for Active Directory).'),
  );
  $form['users']['mail_attr'] = array(
    '#type' => 'textfield',
    '#title' => t('Email attribute'),
    '#default_value' => $edit['mail_attr'],
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('The attribute that holds the users\' email address. (eg. <em style="font-style: normal; padding: 1px 3px; border: 1px solid #8888CC; background-color: #DDDDFF">mail</em>).'),
  );
  $form['users']['ldap_to_drupal_user'] = array(
    '#type' => 'textarea',
    '#title' => t('PHP to transform login name from Drupal to LDAP'),
    '#default_value' => $edit['ldap_to_drupal_user'],
    '#cols' => 25,
    '#rows' => 5,
    '#description' => t('Enter PHP to transform Drupal username to the value of the UserName attribute.  Careful, bad PHP code here will break your site. If left empty, no name transformation will be done. Change following example code to enable transformation:<br /><code>return $name;</code>'),
  );
  
 $form['users']['testing_drupal_username'] = array(
    '#type' => 'textfield',
    '#title' => t('Testing Drupal Username'),
    '#default_value' => $edit['testing_drupal_username'],
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'),
  );

  $form['users']['test_user'] = array(
      '#type'  => 'submit',
      '#value' => t('Test LDAP User to Drupal User Relationship'),
    );
  
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
    '#weight' => 100,
  );

  return $form;
}

/**
 * Validate hook for the LDAP server form.
 */
function ldap_api_servers_admin_form_validate($form, &$form_state) {
  $values = $form_state['values'];

  if (!isset($values['sid'])) {
    $ldap_servers_config = ldap_api_config_get();
    if ($ldap_servers_config['ldap_servers']) {
      foreach ($ldap_servers_config['ldap_servers'] as $sid => $ldap_server) {
        if (array_intersect_assoc(array('name'=> $values['name']),$ldap_server)) {
          form_set_error('name', t('An LDAP server configuration with the  name %name already exists.', array('%name' => $values['name'])));
        }
      }
    }
  }
  if (!is_numeric($values['port'])) {
    form_set_error('port', t('The TCP/IP port must be an integer.'));
  }
}

/**
 * Submit hook for the LDAP server form.
 */
function ldap_api_servers_admin_form_submit($form, &$form_state) {
  
  /** 
   if (!$con = ldap_connect('ad.uiuc.edu', 389)) {
      drupal_set_message('LDAP Connect failure to '. 'ad.uiuc.edu' .':389', 'error');
   }
   print "<pre><br/>con=$con";
  ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($con, LDAP_OPT_REFERRALS, 0);
  ldap_get_option($con, LDAP_OPT_PROTOCOL_VERSION, $vers);
  $bind_ret = ldap_bind($con, 'cn=ed-public-ldap,ou=webs,ou=education,dc=ad,dc=uiuc,dc=edu', 'saf324adfaw1ccs3324fs3#2');
  print "<br/>bind_ret=$bind_ret";
  $x = ldap_search($con, 'ou=campus accounts,dc=ad,dc=uiuc,dc=edu' , 'samaccountname=jbarclay', array('mail','cn'), 0, 10);
  print "<br/>search ret=$x";
  if ($x && ldap_count_entries($con, $x)) {
      $ret = ldap_get_entries($con, $x);
    }
  print "<br/>ldap_get_entries ret="; print_r($ret);
    
 // print "<pre>"; print_r($ret); print_r($x);
  die;
      **/
  
  $values = $form_state['values'];
  //dpm($values);
  if($values['bindpw']) {
    list($values['bindpw'],$values['pwd_encryption']) = ldap_api_encrypt($values['bindpw']);
//    dpm('post encrypt values:'); dpm($values);
  }
  
  $values['module_data']['allowed_modules'] = $values['allowed_modules'];
  $op = $form_state['clicked_button']['#value'];
  $sid = $values['sid'];    
  $form_state['redirect'] = LDAP_API_MENU_BASE_PATH .'/servers';
 // $form_state['redirect'] .= (is_numeric($sid)) ? '/servers/edit/'. $sid : 
  $module_data = serialize($values['module_data']);
//  dpm('module data'); dpm($module_data);
  


  if (!isset($sid )) {
    $action = 'added';
    db_query("INSERT INTO {ldap_api_servers}
             (name,           type,            status,  server_addr,            port,            tls,            pwd_encryption,            binddn,            bindpw,             basedn,           user_attr,            mail_attr,            ldap_to_drupal_user,            testing_drupal_username,            module_data,   created, changed )
      VALUES ('%s',           '%s',            %d,      '%s',                   %d,              %d,             '%s',                      '%s',              '%s',              '%s',              '%s',                 '%s',                 '%s',                           '%s',                               '%s',           %d,      %d)",
             $values['name'], $values['type'], 1,       $values['server_addr'], $values['port'], $values['tls'], $values['pwd_encryption'], $values['binddn'], $values['bindpw'], $values['basedn'], $values['user_attr'], $values['mail_attr'], $values['ldap_to_drupal_user'], $values['testing_drupal_username'], $module_data,  time(),  time());
    // get sid after insert
    $result = db_fetch_object(db_query("SELECT sid FROM {ldap_api_servers} WHERE name = '%s'",$values['name']));
    $sid = $result->sid;
  }
  else {
    if ($values['bindpw']) { 
      db_query("UPDATE {ldap_api_servers} SET bindpw = '%s', pwd_encryption = '%s' WHERE sid = %d ", $values['bindpw'], $values['pwd_encryption'], $sid);
    }
    $action = 'updated';
    $params = array(
      $values['name'], $values['type'], $values['status'], $values['server_addr'], $values['port'], $values['tls'], $values['pwd_encryption'], $values['binddn'],  $values['bindpw'], $values['basedn'],
      $values['user_attr'], $values['mail_attr'], $values['ldap_to_drupal_user'], $values['testing_drupal_username'], $module_data, time(),     $sid
    );
    // should not log or update if no values have changed. makes logs deceptive.
    db_query("UPDATE {ldap_api_servers}
     SET name = '%s', type = '%s', status = %d,  server_addr = '%s', port = %d, tls = %d, pwd_encryption = '%s',  binddn = '%s', bindpw = '%s', basedn = '%s',
      user_attr = '%s',  mail_attr = '%s',  ldap_to_drupal_user = '%s', testing_drupal_username = '%s', module_data = '%s', changed  = %d
      WHERE sid = %d",
      $params);
   }
  $tokens = array('%name' => $values['name'], '!sid' => $sid, '!action' => $action);
  drupal_set_message(t('LDAP Server Configuration %name (server id = !sid) has been !action.', $tokens));
  watchdog('ldap', 'LDAP Server !action: %name (sid=!sid)', $tokens);


/**
 * invoke:
 * hook_ldap_api_server_module_data_alter(&$module_data, &$form_state, &$ldap_server_config, $action, $sid) {
 *
 *
 */
  
  // get server config now
  drupal_alter('ldap_api_server_module_data', $module_data, $form_state, $ldap_server_config, $action, $sid);
  // set module data in db
  // recache server data
  
  if ($op == t('Test Server Binding Credentials')) {
    $ldap_server_object = ldap_api_get_server_objects($sid);
    if (! $ldap_server_object->connect()) {
          drupal_set_message(t('Authentication with the LDAP server for the dn %dn failed.', array('%dn' => $dn)), 'error');
    }
    else {
      drupal_set_message(t('Authentication with the LDAP server for the dn %dn succeeded.', array('%dn' => $ldap_server_object->binddn)));
    }
    $form_state['redirect'] .= '/edit/'. $sid;
  }
  
  if ($op ==  t('Test LDAP User to Drupal User Relationship')) {
    if (! $values['testing_drupal_username']) {
      drupal_set_message(t('Cannot test LDAP User to Drupal User Relationship without a username.'), 'error');
    } else {
      $ldap_server_object = ldap_api_get_server_objects($sid);
      if (! $ldap_server_object->connect()) {
         drupal_set_message(t('Can\'t test LDAP User to Drupal User Relationship because binding to the server failed for the dn %dn failed.', array('%dn' => $dn)), 'error');
      }
      elseif (! $ldap_user = $ldap_server_object->get_ldap_user_entry_from_drupal_username(trim($values['testing_drupal_username'])) ) {
          drupal_set_message(t('Failed to find test user %username by searching on  %user_attr = %username.', array('%username' => $values['testing_drupal_username'],' %user_attr' => $ldap_server_object->user_attr )),'error');
      }
      else {
        $ldap_attributes = theme_item_list(
          array(
            "dn: ". $ldap_user['dn'],
            $ldap_server_object->mail_attr .": ".  $ldap_user[drupal_strtolower($ldap_server->mail_attr)][0],
            $ldap_server_object->user_attr .": ".  $ldap_user[drupal_strtolower($ldap_server->user_attr)][0],
            "cn: ".  $ldap_user['cn'][0],
          )
        );
        drupal_set_message(t('Found test user %username by searching on  %user_attr = %username.', array('%username' => $values['testing_drupal_username'],' %user_attr' => $ldap_server->user_attr )) . $ldap_attributes);
      }
    }
    $form_state['redirect'] .= '/edit/'. $sid;
  }
  
  ldap_api_config_cache_set();
}



/**
 * Implements the LDAP server delete page.
 *
 * @param $form_state
 *   A form state array.
 * @param $sid
 *   A LDAP server ID.
 *
 * @return
 *   The form structure.
 */
function ldap_api_servers_admin_delete(&$form_state, $sid) {
  
  
  if ($sid && ($ldap_server = ldap_api_config_get(FALSE, $sid))) {
    
    $form['#prefix'] = "<div>". ldap_api_servers_display_server_summary($sid) ."</div>";
    $form['sid'] = array(
      '#type' => 'hidden',
      '#value' => $sid,
    );
    $form['name'] = array(
      '#type' => 'hidden',
      '#value' => $ldap_server['name'],
    );
    return confirm_form(
      $form,
      'Delete Confirmation Form',
      LDAP_API_MENU_BASE_PATH .'/servers/list',
        '<p>'. t('Are you sure you want to delete the LDAP server named <em><strong>%name</strong></em>?', array('%name' => $ldap_server['name'])) . '</p><p>' .
        t('This action cannot be undone.') . '</p>',
      t('Delete'),
      t('Cancel')
    );
  }
  drupal_goto(LDAP_API_MENU_BASE_PATH .'/servers/list');
}

/**
 * Submit hook for the LDAP server delete page.
 */
function ldap_api_servers_admin_delete_submit($form, &$form_state) {
  $values = $form_state['values'];
  if ($values['confirm'] && $values['sid']) {
    db_query("DELETE FROM {ldap_api_servers} WHERE sid = %d", $values['sid']);
    $tokens = array('%name' => $values['name'], '!sid' => $values['sid']);
    drupal_set_message(t('LDAP Server %name (server id = !sid) has been deleted.', $tokens));
    watchdog('ldap', 'LDAP Server deleted: %name (sid = !sid) ', $tokens);
  }
  ldap_api_config_cache_set();
}


/**
 * Implements the LDAP server delete page.
 *
 * @param $form_state
 *   A form state array.
 * @param $sid
 *   A LDAP server ID.
 *
 * @return
 *   The form structure.
 */

  
function ldap_api_servers_admin_enable_disable(&$form_state, $action, $sid) {
 // $action = arg(4);
  if ($ldap_server = ldap_api_config_get(FALSE, $sid)) {
    
    $form['#prefix'] = "<div>". ldap_api_servers_display_server_summary($sid) ."</div>";
    
    $form['sid'] = array(
      '#type' => 'hidden',
      '#value' => $sid,
    );
    $form['name'] = array(
      '#type' => 'hidden',
      '#value' => $ldap_server['name'],
    );
     $form['action'] = array(
      '#type' => 'hidden',
      '#value' => $action,
    );   
    return confirm_form(
      $form,
      t('Are you sure you want to ') . t($action). t(' the LDAP server named <em><strong>%name</strong></em>?', array('%name' => $ldap_server['name'])),
      LDAP_API_MENU_BASE_PATH . '/servers/list',
      t('<p></p>'),
      t($action),
      t('Cancel')
    );
  }
  drupal_goto(LDAP_API_MENU_BASE_PATH . '/servers/list');
}

/**
 * Submit hook for the LDAP server delete page.
 */
function ldap_api_servers_admin_enable_disable_submit($form, &$form_state) {
  $values = $form_state['values'];
  $status = ($values['action'] == 'enable') ? 1 : 0;
  if ($values['confirm'] && $values['sid']) {
    db_query("UPDATE {ldap_api_servers} SET status = %d WHERE sid = %d", $status, $values['sid']);
    
    $tokens = array('%name' => $values['name'], '!sid' => $values['sid'], '!action' => t($values['action']) . 'd.');
    drupal_set_message(t('LDAP Server Configuration %name (server id = !sid) has been !action.', $tokens));
    watchdog('ldap', t('LDAP Server !action: %name (sid = !sid) ', $tokens));
    
    ldap_api_config_cache_set();
    drupal_goto(LDAP_API_MENU_BASE_PATH . '/servers');
  }
  
}

function ldap_api_ldaps_option_array() {
  $options = array();
  $options['none'] = "-- Select LDAP Type --";
  foreach (ldap_api_get_ldap_defaults() as $type => $defaults) {
    $options[$type] = $defaults['name'];
  }
  return $options;
}

// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
