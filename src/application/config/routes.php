<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/
$site_id = isset($_SERVER['SITE_ID']) ? $_SERVER['SITE_ID'] : $_SERVER['REDIRECT_SITE_ID'];

if($site_id == 1){
  $route['default_controller'] = "equipment";
  
}elseif($site_id == 2){
  $route['default_controller'] = "scheduling";
}else{
  $route['default_controller'] = "equipment";
}
//$route['404_override'] = '';
//ajax-y calls
$route['ajax/get_user_details/(:any)'] = "samples/get_user_details/$1";

$route['ticket/view/(:any)'] = "tickets/ticket_details/$1/view";
$route['ticket/edit/(:any)'] = "tickets/ticket_details/$1/edit";
$route['ticket/delete/(:any)'] = "ticket_create_edit/delete_ticket/$1";
$route['ticket/new'] = "tickets/ticket_details/-1/new";
$route['ticket'] = "tickets/search";
$route['ticket/(:any)'] = "tickets/ticket_details/$1/view";
$route['ajax/ticket/edit/(:any)'] = "ticket_create_edit/edit_existing_ticket/$1";
$route['ajax/ticket/new'] = "ticket_create_edit/create_new_ticket";
$route['ajax/time_tracking/add/(:any)'] = "ticket_create_edit/add_time_tracking_entry/$1";
$route['ajax/time_tracking/update/(:any)'] = "ticket_create_edit/update_time_tracking_entry/$1";
$route['ajax/time_tracking/entries/(:any)'] = "ticket_create_edit/get_time_tracking_entries/$1";
$route['ajax/update_staff_status'] = "admin/update_staff_status";
$route['ajax/update_ticket_status/(:any)'] = "ticket_create_edit/update_ticket_status_info/$1";
$route['ajax/(:any)'] = "ticket_create_edit/$1";
$route['ajax/sample_list'] = "samples/sample_list";
$route['tickets/mine'] = "tickets/by_user";
//$route['tickets/by_status/(:any)'] = "tickets/tickets_by_status/$1";
//$route['tickets/by_priority/(:any)'] = "tickets/tickets_by_priority/$1";
//$route['tickets/(:any)'] = "tickets/tickets_by_priority/$1";

$route['equipment/(:any)/(:any)'] = "equipment/details/$1/$2";
$route['equipment/(:any)'] = "equipment/details/$1";
$route['admin/add_new_equipment/(:any)'] = "admin/edit_equipment/$1/0";
//$route['equipment/(:any)/(:any)'] = "equipment/filtered_list/$1/$2";

// $route['equipment/(magnet|probe|software)/(:num)'] = "equipment/$1_details/$2"; 
//$route['equipment/([magnets|probes|software])/(:num)'] = "equipment/$1_details/$2";
//$route['equipment/(:any)/(:any)'] = "equipment/filtered_list/$1/$2";

$route['sample/new'] = "samples/sample_handler/edit/-1";
$route['sample/(:num)'] = "samples/sample_handler/view/$1";
$route['sample/edit/(:num)'] = "samples/sample_handler/edit/$1";
$route['sample/(:num)/edit'] = "samples/sample_handler/edit/$1";




/* End of file routes.php */
/* Location: ./application/config/routes.php */