<?php

function ldap_test_ldap_authentication_data() {

$conf['default'] = array(
    'sids' => array('ldapauthen1' => 'ldapauthen1'),
    'authenticationMode' => LDAP_AUTHENTICATION_MIXED,
    'loginConflictResolve' => LDAP_AUTHENTICATION_CONFLICT_LOG,
    'acctCreation' => LDAP_AUTHENTICATION_ACCT_CREATION_LDAP_BEHAVIOR,
    'ldapUserHelpLinkUrl' => '',
    'ldapUserHelpLinkText' => '',
    'emailOption' => LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE,
    'emailUpdate' => LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DISABLE,
    'allowOnlyIfTextInDn' => NULL,
    'excludeIfTextInDn' => NULL,
    'allowTestPhp' => NULL,
    'excludeIfNoAuthorizations' => LDAP_AUTHENTICATION_EXCL_IF_NO_AUTHZ_DEFAULT,
  );


$conf['MixedModeUserLogon'] = array(
    'sids' => array('ldapauthen1' => 'ldapauthen1'),
    'authenticationMode' => LDAP_AUTHENTICATION_MIXED,
    'loginConflictResolve' => LDAP_AUTHENTICATION_CONFLICT_LOG,
    'acctCreation' => LDAP_AUTHENTICATION_ACCT_CREATION_LDAP_BEHAVIOR,
    'ldapUserHelpLinkUrl' => '',
    'ldapUserHelpLinkText' => '',
    'emailOption' => LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE,
    'emailUpdate' => LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DISABLE,
    'allowOnlyIfTextInDn' => NULL,
    'excludeIfTextInDn' => NULL,
    'allowTestPhp' => NULL,
    'excludeIfNoAuthorizations' => LDAP_AUTHENTICATION_EXCL_IF_NO_AUTHZ_DEFAULT,
  );

$conf['ExclusiveModeUserLogon'] = array(
    'sids' => array('ldapauthen1' => 'ldapauthen1'),
    'authenticationMode' => LDAP_AUTHENTICATION_EXCLUSIVE,
    'loginConflictResolve' => LDAP_AUTHENTICATION_CONFLICT_LOG,
    'acctCreation' => LDAP_AUTHENTICATION_ACCT_CREATION_LDAP_BEHAVIOR,
    'ldapUserHelpLinkUrl' => '',
    'ldapUserHelpLinkText' => '',
    'emailOption' => LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE,
    'emailUpdate' => LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DISABLE,
    'allowOnlyIfTextInDn' => NULL,
    'excludeIfTextInDn' => NULL,
    'allowTestPhp' => NULL,
    'excludeIfNoAuthorizations' => LDAP_AUTHENTICATION_EXCL_IF_NO_AUTHZ_DEFAULT,
  );

$conf['WL1'] = array(
    'sids' => array('ldapauthen1' => 'ldapauthen1'),
    'authenticationMode' => LDAP_AUTHENTICATION_EXCLUSIVE,
    'loginConflictResolve' => LDAP_AUTHENTICATION_CONFLICT_LOG,
    'acctCreation' => LDAP_AUTHENTICATION_ACCT_CREATION_LDAP_BEHAVIOR,
    'ldapUserHelpLinkUrl' => '',
    'ldapUserHelpLinkText' => '',
    'emailOption' => LDAP_AUTHENTICATION_EMAIL_FIELD_DISABLE,
    'emailUpdate' => LDAP_AUTHENTICATION_EMAIL_UPDATE_ON_LDAP_CHANGE_DISABLE,
    'allowOnlyIfTextInDn' => NULL,
    'excludeIfTextInDn' => NULL,
    'allowTestPhp' => NULL,
    'excludeIfNoAuthorizations' => 0,
  );

// single sign on tests
$conf['MixedModeUserLogonSSO'] = $conf['MixedModeUserLogon'];
$conf['MixedModeUserLogonSSO']['ssoRemoteUserStripDomainName'] = FALSE;
$conf['MixedModeUserLogonSSO']['seamlessLogin'] = TRUE;
$conf['MixedModeUserLogonSSO']['ldapImplementation'] = 'mod_auth_sspi';
$conf['MixedModeUserLogonSSO']['cookieExpire'] = 3600;

$conf['ExclusiveModeUserLogonSSO'] = $conf['ExclusiveModeUserLogon'];
$conf['ExclusiveModeUserLogonSSO']['ssoRemoteUserStripDomainName'] = FALSE;
$conf['ExclusiveModeUserLogonSSO']['seamlessLogin'] = FALSE;
$conf['ExclusiveModeUserLogonSSO']['ldapImplementation'] = 'mod_auth_sspi';
$conf['ExclusiveModeUserLogonSSO']['cookieExpire'] = 3600;

return $conf;
}
