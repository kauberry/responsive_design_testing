<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7.
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'ticket_tracker';
$db['default']['password'] = 'tix4real';
$db['default']['database'] = 'emsl_ticket_tracker_development';
$db['default']['dbdriver'] = 'mysql';
$db['default']['dbprefix'] = '';
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;

$db['sample_tracking']['hostname'] = 'localhost';
$db['sample_tracking']['username'] = 'ticket_tracker';
$db['sample_tracking']['password'] = 'tix4real';
$db['sample_tracking']['database'] = 'emsl_ticket_tracker_development';
$db['sample_tracking']['dbdriver'] = 'mysql';
$db['sample_tracking']['dbprefix'] = '';
$db['sample_tracking']['pconnect'] = TRUE;
$db['sample_tracking']['db_debug'] = TRUE;
$db['sample_tracking']['cache_on'] = FALSE;
$db['sample_tracking']['cachedir'] = '';
$db['sample_tracking']['swap_pre'] = '';
$db['sample_tracking']['autoinit'] = TRUE;
$db['sample_tracking']['stricton'] = FALSE;

$db['opwhse']['hostname'] = "130.20.249.198:915";
// $db['opwhse']['port'] = 915;
$db['opwhse']['username'] = "PRISM";
$db['opwhse']['password'] = "5GigYawn";
$db['opwhse']['database'] = "opwhse";
$db['opwhse']['dbdriver'] = "mssql";
$db['opwhse']['dbprefix'] = "";
$db['opwhse']['active_r'] = TRUE;
$db['opwhse']['pconnect'] = FALSE;
$db['opwhse']['db_debug'] = FALSE;
$db['opwhse']['cache_on'] = FALSE;
$db['opwhse']['cachedir'] = "";


$db['ws_info']['hostname'] = "localhost";
$db['ws_info']['username'] = "ticket_tracker";
$db['ws_info']['password'] = "tix4real";
$db['ws_info']['database'] = "magres_ticket_tracker_site_info";
$db['ws_info']['dbdriver'] = "mysql";
$db['ws_info']['dbprefix'] = "";
$db['ws_info']['pconnect'] = TRUE;
$db['ws_info']['db_debug'] = TRUE;
$db['ws_info']['cache_on'] = FALSE;
$db['ws_info']['cachedir'] = "";

$db['eus_for_myemsl']['hostname'] = "eusi.emsl.pnl.gov";
$db['eus_for_myemsl']['username'] = "myemsl";
$db['eus_for_myemsl']['password'] = "Gr7vakon";
$db['eus_for_myemsl']['database'] = "ERSUP";
$db['eus_for_myemsl']['dbdriver'] = "mysql";
$db['eus_for_myemsl']['dbprefix'] = "";
$db['eus_for_myemsl']['pconnect'] = TRUE;
$db['eus_for_myemsl']['db_debug'] = TRUE;
$db['eus_for_myemsl']['cache_on'] = FALSE;
$db['eus_for_myemsl']['cachedir'] = "";

$db['ers']['hostname'] = "eusi.emsl.pnl.gov";
$db['ers']['username'] = "auberry_user";
$db['ers']['password'] = "l0Ve2getEUSd3ta";
$db['ers']['database'] = "Auberry";
$db['ers']['dbdriver'] = "mysql";
$db['ers']['dbprefix'] = "";
$db['ers']['active_r'] = TRUE;
$db['ers']['pconnect'] = FALSE;
$db['ers']['db_debug'] = FALSE;
$db['ers']['cache_on'] = FALSE;
$db['ers']['cachedir'] = "";


/* End of file database.php */
/* Location: ./application/config/database.php */
