<?php
// $Id$
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LdapAuthenticationConf
 *
 * @author jbarclay
 */

class LdapAuthenticationConf {

  // no need for LdapAuthenticationConf id as only one instance will exist per drupal install

  public $sids = array();  // server configuration ids being used for authentication
  public $servers = array(); // ldap server object
  public $inDatabase = FALSE;
  public $allowLdapPasswordChange = FALSE;
  public $authenticationMode = LDAP_AUTHENTICATION_MIXED;
 
  public $logonFormHideResetPassword = NULL;
  public $logonFormHideCreateAccount = NULL;
  
  public $loginConflictResolve = LDAP_AUTHENTICATION_CONFLICT_LOG;
  public $emailOption = LDAP_AUTHENTICATION_EMAIL_FIELD_REMOVE;
  public $apiPrefs = array();

  /**
   * Advanced options.   whitelist / blacklist options
   *
   * these are on the fuzzy line between authentication and authorization
   * and determine if a user is allowed to authenticate with ldap
   *
   **/

  public $allowOnlyIfTextInDn = array(); // eg ou=education that must be met to allow ldap authentication
  public $excludeIfTextInDn = array();
  public $allowTestPhp = NULL; // code that returns boolean TRUE || FALSE for allowing ldap authentication
  public $ldapUsersRequireAdminApproval = FALSE;
  public $ldapUsersDontCreateAutomatically = FALSE;

  protected $saveable = array(
    'sids',
    'authenticationMode',
    'logonFormHideResetPassword',
    'logonFormHideCreateAccount',
    'loginConflictResolve',
    'emailOption',
    'allowOnlyIfTextInDn',
    'excludeIfTextInDn',
    'allowTestPhp',
    'ldapUsersRequireAdminApproval',
    'ldapUsersDontCreateAutomatically',
  );
  
 
  function __construct() {
    $this->load();
  }


  function load() {
  
    if ($saved = variable_get("ldap_authentication_conf", FALSE)) {
      $this->inDatabase = TRUE;
      foreach ($this->saveable as $property) {
        if (isset($saved[$property])) {
          $this->{$property} = $saved[$property];
        }
      }
      foreach ($this->sids as $sid) {
        $this->servers[$sid] = ldap_servers_get_servers($sid, 'enabled', TRUE);
        //print "<pre> $sid"; print_r( $this->servers[$sid]); die;
      }
    } else {
      $this->inDatabase = FALSE;
    }
    
    $this->apiPrefs['requireHttps'] = variable_get('ldap_servers_require_ssl_for_credentails', 1);
    $this->apiPrefs['encryption'] = variable_get('ldap_servers_encryption', NULL);
  }

  /**
   * Destructor Method
   */
  function __destruct() {


  }


 /**
   * decide if a username is excluded or not
   *
   * return boolean
   */
  public function allowUser($name, $ldap_user) {
    
     // print "<pre>"; print_r($ldap_user); die;

    /**
     * do one of the exclude attribute pairs match
     */
    $exclude = FALSE;
    foreach($this->excludeIfTextInDn as $test) {
      if (strpos($ldap_user['dn'], $test) !== FALSE) {
          print "found $test"; die;
        return FALSE;//  if a match, return FALSE;
      }
    }
    
    
    /**
     * evaluate php if it exists
     */
    if (module_exists('php') && $this->allowTestPhp) {
      $code = '<?php '.  $this->allowTestPhp    .' ?>';   
      $code_result = @php_eval($code);
      if ((boolean)($code_result) == FALSE) {
          print "code failed"; die;
        return FALSE;
      }
    }
    
    /**
     * do one of the allow attribute pairs match
     */
    if (count($this->allowOnlyIfTextInDn)) {
      foreach($this->allowOnlyIfTextInDn as $test) {
          print "<hr/>test = $test" . $ldap_user['dn'];
          print "pos=" . strpos($ldap_user['dn'], $test);
        if ( strpos($ldap_user['dn'], $test) !== FALSE) {
          return TRUE;
        }
      }
      return FALSE;  
    }

    /**
     * default to allowed
     */
    return TRUE;
  }
  

}

