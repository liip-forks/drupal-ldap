// $Id:  $
This is a development version of an feeds fetcher and feeds parser for ldap.

----------------------------------
use cases
----------------------------------
Move data from an ldap query into nodes, users, or other drupal structures supported by feeds.
Feeds is a general architecture for moving data where an importer consists of a fetcher, parser, and processor.  Ldap Feeds supplies the fetcher and parser such that any processor can be used (node, user, taxonomy term, and any of the processors at: http://drupal.org/node/856644)

Examples:
-- Move course or faculty staff info into drupal nodes for directories.
---- FeedsLdapQueryFetcher for ldap query, FeedsLdapEntryParser for parsing it into feeds format, Node Processor for creating/synching nodes.

-- Synch ldap attributes with user profile data
---- FeedsDrupalUserLdapEntryFetcher for gettling ldap data, FeedsLdapEntryParser for parsing it into feeds format, User Processor for creating/synching with drupal users.

-- Provision Drupal Users with ldap query.
---- FeedsLdapQueryFetcher for ldap query, FeedsLdapEntryParser for parsing it into feeds format, User Processor for creating/synching users.


----------------------------------
functionality
----------------------------------
Includes 2 feeds fetchers:
- FeedsLdapQueryFetcher for fetching generic ldap queries, configured by admins
- FeedsDrupalUserLdapEntryFetcher for fetching ldap entries of drupal users who are ldap authenticated or otherwise ldap associated.

Includes 1 feeds parser:
- FeedsLdapEntryParser that converts ldap entries array returned from ldap_search() to standard feed parser result format.

------------------------------------------------
TODO:
------------------------------------------------
Multivalued attributes are not supported, only attributes that have single, scalar values can be fetched and parsed.
Multivalued design decisions:
 -- should configuration be per fields or all multivalued fields
 -- can repeating fields be supported via processor
    and what format should the parser support



------------------------------------------------
Testing steps.  need to convert to simpletest
------------------------------------------------
   0.  Make sure ldap_servers, ldap_feeds, feeds, and feeds admin ui are enabled and at least on ldap server is configured.
   1.  create content type 'ldap_user'
   2.  add fields 'sn' and 'mail' to content type
   3.  create content type 'ldap_user_feed_node' with "title field label" of "dn"
   4A.  create new feed importer (/admin/structure/feeds/create)
       - name: test ldap user entry to node
       - machine name: 'test_ldap_to_node'
       - description: 'testing ldap user entry to drupal node importer'
   4B. Basic Settings - admin/structure/feeds/edit/test_ldap_to_node/settings
       - attach to content type:  ldap_user_feed_node
       - periodic import: off
       - import on submission: not checked
       - process in background: not checked.
   34C. Fetcher: admin/structure/feeds/edit/test_ldap_to_node/fetcher
       - set to LDAP Query Fetcher.
   4.D. Fetcher Settings admin/structure/feeds/edit/test_ldap_to_node/settings/FeedsLdapQueryFetcher
      - there are none
   4.E. Parser admin/structure/feeds/edit/test_ldap_to_node/parser
      - select LFAP Entry Parser for Feeds
   4.F. Parser Settings aadmin/structure/feeds/edit/test_ldap_to_node/settings/FeedsLdapEntryParser
      - there are none
   4.G.  Select a Processor: admin/structure/feeds/edit/test_ldap_to_node/processor
      - select node processor
   4.F. Settings for node processor: admin/structure/feeds/edit/test_ldap_to_node/settings/FeedsNodeProcessor
      - replace existing nodes.  better for testing
      - test format: plain text
      - content type: Ldap User Entry
   4.G Mappings: admin/structure/feeds/edit/test_ldap_to_node/mapping
      - source, target, unique target
      - dn,     GUID  , x
      - dn,     Title,
      - mail,   Body,
      - sn,     sn
      - telephonenumber, Telephone

   5. Create Ldap User Feed Node (node/add/ldap-user-feed-node)
      this is where a single feed source is stored.
      Title: test feed
      Body: test feed
      Ldap Server: any configured ldap server
      Base DNs: any basedn where users exist.  e.g. dc=ad,dc=uiuc,dc=edu
      filter: objectclass=user
      attributes: "mail", "sn", "cn","dn","telephonenumber"
      size limit: 5

  6.  Hit the import tab on that node and hit the import button
      5 nodes should be created.


   *
   */
