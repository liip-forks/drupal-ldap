<?php
// $Id$

/**
 * @file
 * LDAP Server Admin Class
 *
 */


require_once('LdapServer.class.php');

class LdapServerAdmin extends LdapServer {

  public $bindpw_new = FALSE;
  public $bindpw_clear = FALSE;
  public static function getLdapServerObjects($sid = NULL, $type = NULL, $class = 'LdapServer') {
  $select = db_select('ldap_servers', 'ldap_servers');
  $select->fields('ldap_servers');
  //if ($type != 'all') {
  //   $select->condition('ldap_servers.status', 1);
 // }
  //if ($sid) {
  //   $select->condition('ldap_servers.sid', $sid);
 // }

  try {
    $servers = $select->execute()->fetchAllAssoc('sid',  PDO::FETCH_ASSOC);

  }
  catch (Exception $e) {
    drupal_set_message(t('server index query failed. Message = %message, query= %query',
      array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
    return array();
  }
  foreach ($servers as $sid => $server) {
    $servers[$sid] = ($class == 'LdapServer') ? new LdapServer($sid) : new LdapServerAdmin($sid);
  }
  
  return $servers;
  
}
  function __construct($sid) {
    parent::__construct($sid);
  }

  protected function populateFromDrupalForm($op, $values) {
    $this->inDatabase = ($op == 'update');
    $this->sid = trim($values['sid']);
    $this->name = trim($values['name']);
    $this->status = ($values['status']) ? 1 : 0;
    $this->type = trim($values['type']);
    $this->address = trim($values['address']);
    $this->port = trim($values['port']);
    $this->tls = trim($values['tls']);
    $this->binddn = trim($values['binddn']);
    if (trim($values['bindpw'])) {
      $this->bindpw_new = trim($values['bindpw']);
    }
    $this->tls = trim($values['tls']);
    $this->basedn = $this->linesToArray(trim($values['basedn']));
    $this->user_attr = trim($values['user_attr']);
    $this->mail_attr = trim($values['mail_attr']);
    $this->ldapToDrupalUserPhp = $values['ldap_to_drupal_user'];
    $this->testingDrupalUsername = trim($values['testing_drupal_username']);

  }

  public function save($op) {
  
    foreach ($this->field_to_properties_map() as $field_name => $property_name) {
      $entry[$field_name] = $this->{$property_name};
    }
    if ($this->bindpw_new) {
      $entry['bindpw'] =  ldap_servers_encrypt($this->bindpw_new);
    } 
    elseif ($this->bindpw_clear) {
      $entry['bindpw'] = NULL;
    }

    
    $entry['basedn'] = serialize($entry['basedn']); 
    $entry['tls'] = (int)$entry['tls'];
    if ($op == 'update') {

      try {
        $count = db_update('ldap_servers')
        ->fields($entry)
        ->condition('sid', $entry['sid'])
        ->execute();
      }
      catch (Exception $e) {
        drupal_set_message(t('db update failed. Message = %message, query= %query',
        array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
      }
    }
    else {
      try {
        $ret = db_insert('ldap_servers')
        ->fields($entry)
        ->execute();
      }
      catch (Exception $e) {
        drupal_set_message(t('db insert failed. Message = %message, query= %query',
          array('%message' => $e->getMessage(), '%query' => $e->query_string)), 'error');
      }
      
      $this->inDatabase = TRUE;
    }
    

  }
  
  public function delete($sid) {
    if ($sid == $this->sid) {
      $this->inDatabase = FALSE;
      return db_delete('ldap_servers')->condition('sid', $sid)->execute();
    } 
    else {
      return FALSE;
    }
  }
  public function drupalForm($op) {
    
    $form['#prefix'] = <<<EOF
<p>Setup an LDAP server configuration to be used by other modules such as LDAP AuthZ, LDAP AuthN, LDAP Groups, etc.</p>
<p>More than one LDAP server configuration can exist for a physical LDAP server.
This is useful when you need different configuration options for different LDAP modules
such as different base dns for authentication and authorization.</p> 
EOF;

$form['#prefix'] = t($form['#prefix']);

  $form['server'] = array(
    '#type' => 'fieldset',
    '#title' => t('LDAP Server settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['server']['sid'] = array(
      '#type' => 'textfield',
      '#title' => t('Machine name for this server configuration.'),
      '#default_value' => $this->sid,
      '#size' => 20,
      '#maxlength' => 20,
      '#disabled' => ($op == 'edit'),
      '#description' => t('May only contain alphanumeric characters (a-z, A-Z, 0-9, and _)' ),
    );

  $form['server']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $this->name,
    '#description' => t('Choose a <em><strong>unique</strong></em> name for this server configuration.'),
    '#size' => 50,
    '#maxlength' => 255,
    '#required' => TRUE,
  );
  
  $form['server']['status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enabled'),
    '#default_value' => $this->status,
    '#description' => t('Disable in order to keep configuration without having it active.'),
  );
  
  $form['server']['type'] = array(
    '#type' => 'select',
    '#options' =>  ldap_servers_ldaps_option_array(),
    '#title' => t('LDAP Server Type'),
    '#default_value' => $this->type,
    '#description' => t('This field is informative.  It\'s purpose is to assist with default values and give validation warnings.'),
    '#required' => FALSE,
  ); 
  $form['server']['address'] = array(
    '#type' => 'textfield',
    '#title' => t('LDAP server'),
    '#default_value' => $this->address,
    '#size' => 50,
    '#maxlength' => 255,
    '#description' => t('The domain name or IP address of your LDAP Server such as "ad.unm.edu". For SSL
        use the form ldaps://DOMAIN such as "ldaps://ad.unm.edu"'),
    '#required' => TRUE,
  );
  $form['server']['port'] = array(
    '#type' => 'textfield',
    '#title' => t('LDAP port'),
    '#default_value' => $this->port,
    '#size' => 5,
    '#maxlength' => 5,
    '#description' => t('The TCP/IP port on the above server which accepts LDAP connections. Must be an integer.'),
  );
  $form['server']['tls'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use Start-TLS'),
    '#default_value' => $this->tls,
    '#description' => t('Secure the connection between the Drupal and the LDAP servers using TLS.<br /><em>Note: To use START-TLS, you must set the LDAP Port to 389.</em>'),
  );

  $form['binding'] = array(
    '#type' => 'fieldset',
    '#title' => t('Server Binding Credentials (or Service Account)'),
    '#description' => t('<p>Some LDAP configurations (specially common in <strong>Active Directory</strong> setups) restrict anonymous searches.</p><p>If your LDAP setup does not allow anonymous searches, or these are restricted in such a way that login names for users cannot be retrieved as a result of them, then you have to specify here a DN//password pair that will be used for these searches.</p><p>For security reasons, this pair should belong to an LDAP account with stripped down permissions.</p>'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  
  $form['binding']['binddn'] =  array(
    '#type' => 'textfield',
    '#title' => t('DN for non-anonymous search'),
    '#default_value' => $this->binddn,
    '#size' => 80,
    '#maxlength' => 255,
  );
  
  $form['binding']['bindpw'] = array(
    '#type' => 'password',
    '#title' => t('Password for non-anonymous search'),
    '#size' => 20,
    '#maxlength' => 255,
    '#default_value' => "",
  );

  $form['binding']['clear_bindpw'] = array(
    '#type' => 'checkbox',
    '#title' => t('Clear existing password from database'),
    '#default_value' => 0,
  );

  if ( $form['binding']['bindpw']) {
    $form['binding']['bindpw']['#description'] = t('<p>Leave emtpy to leave password unchanged.</p>');
  }

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
    '#default_value' => $this->arrayToLines($this->basedn),
    '#cols' => 50,
    '#rows' => 6,
    '#description' => t('What DNs have user accounts relavant to this configuration?') . " e.g. <code>ou=campus accounts,dc=ad,dc=uiuc,dc=edu</code>  " . t('Enter one per line in case if you need more than one.'),
  );
  
  $form['users']['user_attr'] = array(
    '#type' => 'textfield',
    '#title' => t('UserName attribute'),
    '#default_value' => $this->user_attr,
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('The attribute that holds the users\' login name. (eg. <em style="font-style: normal; padding: 1px 3px; border: 1px solid #8888CC; background-color: #DDDDFF">cn</em> for eDir or <em style="font-style: normal; padding: 1px 3px; border: 1px solid #8888CC; background-color: #DDDDFF">sAMAccountName</em> for Active Directory).'),
  );
  $form['users']['mail_attr'] = array(
    '#type' => 'textfield',
    '#title' => t('Email attribute'),
    '#default_value' => $this->mail_attr,
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('The attribute that holds the users\' email address. (eg. <em style="font-style: normal; padding: 1px 3px; border: 1px solid #8888CC; background-color: #DDDDFF">mail</em>).'),
  );
  $form['users']['ldap_to_drupal_user'] = array(
    '#type' => 'textarea',
    '#title' => t('PHP to transform login name from Drupal to LDAP'),
    '#default_value' => $this->ldapToDrupalUserPhp, 
    '#cols' => 25,
    '#rows' => 5,
    '#description' => t('Enter PHP to transform Drupal username to the value of the UserName attribute.  Careful, bad PHP code here will break your site. If left empty, no name transformation will be done. Change following example code to enable transformation:<br /><code>return $name;</code>'),
  );
  
  $form['users']['testing_drupal_username'] = array(
    '#type' => 'textfield',
    '#title' => t('Testing Drupal Username'),
    '#default_value' => $this->testingDrupalUsername,
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  
  $action = ($op == 'add') ? 'Add' : 'Update';
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => $action,
    '#weight' => 100,
  );


  return $form;
    
  }
  
  
  public function drupalFormValidate($op, $values)  {
    $errors = array();

    if ($op == 'delete') {
      if (!$this->sid) {
        $errors['server_id_missing'] = 'Server id missing from delete form.';
      }
    } 
    else {
      $this->populateFromDrupalForm($op, $values);
      $errors = $this->validate($op);
    }
    return $errors;
  }
  
  protected function validate($op) {
    $errors = array();
    if ($op == 'add') {
      $ldap_servers = $this->getLdapServerObjects(NULL, 'all');
      if (count($ldap_servers)) {
        foreach ($ldap_servers as $sid => $ldap_server) {
          if ($this->name == $ldap_server->name) {
            $errors['name'] = t('An LDAP server configuration with the  name %name already exists.', array('%name' => $this->name));
          } 
          elseif ($this->sid == $ldap_server->sid) {
            $errors['sid'] = t('An LDAP server configuration with the  id %sid  already exists.', array('%sid' => $this->sid));
          }
        }
      }
      
    }

  
    if (!is_numeric($this->port)) {
      $errors['port'] =  t('The TCP/IP port must be an integer.');
    }
    return $errors;
  }
  
public function drupalFormSubmit($op, $values) {

  $this->populateFromDrupalForm($op, $values);

  if ($values['clear_bindpw']) {
    $this->bindpw_clear = NULL;
  }

  if ($op == 'delete') {
    $this->delete($this);
  } 
  else { // add or update
    try {
      $save_result = $this->save($op);
    }
    catch (Exception $e) {
      $this->errorName = 'Save Error';
      $this->errorMsg = t('Failed to save object.  Your form data was not saved.');
      $this->hasError = TRUE;
    }
  }
} 



  protected function arrayToLines($array) {
    $lines = "";
    if (is_array($array)) {
      $lines = join("\n", $array);
    }  
    elseif (is_array(@unserialize($array))) {
      $lines = join("\n", unserialize($array));
    }
    return $lines;
  }

  protected function linesToArray($lines) {
    $lines = trim($lines);

    if ($lines) {
      $array = explode("\n", $lines);
    }
    else {
      $array = array();
    }

    return $array;
  }

}


