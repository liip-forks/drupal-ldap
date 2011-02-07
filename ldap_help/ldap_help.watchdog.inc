<?php
// $Id$

function ldap_help_watchdog() {

  /* watchdog table */
  drupal_add_css(drupal_get_path('module', 'ldap_help') .'/ldap_help.css', 'module', 'all', FALSE);
  $path =  drupal_get_path("module", "ldap_help");
  $_content = "";
  if (module_exists('dblog')) {
    include_once(drupal_get_path('module', 'dblog') .'/dblog.admin.inc');
  $_SESSION['dblog_overview_filter']['type'] = Array('ldap' => 'ldap');
  //  if (! ($_SESSION['dblog_overview_filter'] || $_SESSION['dblog_overview_filter']['severity'])) {
  //    $_SESSION['dblog_overview_filter']['severity'] = array();
  // }

    $_content .= "<h3>". t('LDAP Watchdog Errors and Notifications') ."</h3>";
    $_content .= render(dblog_overview());
    $_content .= l('...more watchdog', 'admin/reports/dblog');
  }
  else {
    $_content .= "<h3>". t('LDAP Help Watchdog Errors and Notifications') ."</h3>";
    $_content .= 'This feature requires <code>Database logging</code> module to be turned on. ';
    $_content .= l('Module enable page', 'admin/build/modules');
  }


  return $_content;
}
