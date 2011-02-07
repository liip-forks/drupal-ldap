<?php
// $Id$

/**
 * @file
 * Advanced Help callbacks for the ldap_api module.
 */

/**
 * Implements the help index.
 *
 * @return
 *   Themed help index.
 */
function ldap_help_index_page() {
 $output = theme('advanced_help_topic', 'ldap_api', 'about-php');
			   $output .= '&nbsp;' . t('Click the help icon to view some example help about the PHP programming language (from wikipedia.org). Be sure to run cron to update the index if you want to try out the search features.');
	return $output;
}

// vim:fenc=utf-8:ft=php:ai:si:ts=2:sw=2:et:
