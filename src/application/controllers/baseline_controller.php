<?php
class Baseline_controller extends CI_Controller {

  function __construct() {
    date_default_timezone_set('America/Los_Angeles');
    parent::__construct();
    $this->load->helper(array('user','url','html'));
    //$this->user_id = strtolower(get_user());
    $this->user_id = "d3k857";
    $this->site_id = $this->config->item('site_id');

    // echo $this->user_id;
    // $this->user_id = "mehd477";
    $this->page_address = implode('/',$this->uri->rsegments);
    //$this->load->model('User_operations_model', 'user_model');
    //$this->load->model('Navigation_info_model', 'nav_info_model');
    //$this->site_info = $this->nav_info_model->get_site_identifier($this->site_id);
    //$this->user_model->refresh_user_info($this->user_id,$this->site_id);
    $this->site_info = array(
      "name" => "microscopy", "description" => "Microscopy Group User Support"
    );
    //$user_info = get_user_details_opw($this->user_id);
    $user_info = array(
      'id' => 'd3k857', 'first_name' => "Ken", 'middle_initial' => "J",
      'last_name' => "Auberry", 'title' => "Scientist, TL",
      'department' => "Instrument Development Lab",
      'telephone' => "509-371-6442", 'mail' => "kenneth.auberry@pnnl.gov",
      'group_list' => array("EMSL-Ticket-Tracker.magres.admins","EMSL-Ticket-Tracker.microscopy.admins")
    );
    $user_group_list = array_key_exists('group_list', $user_info) ? $user_info['group_list'] : array();
    //$this->admin_access_level = $this->user_model->get_user_permissions_level($user_group_list,$this->site_id);
    $this->admin_access_level = 999;
    // echo "admin access level {$this->admin_access_level}";
    if($this->user_id == 'd3k857'){
      // $this->admin_access_level = 100;
    }
   // $this->admin_access_level = 400;
//    echo $this->admin_access_level;
    $first_name = array_key_exists('first_name',$user_info) && $user_info['first_name'] != null ? $user_info['first_name'] : "Anonymous";
    $last_name = array_key_exists('last_name',$user_info) && $user_info['last_name'] != null ? $user_info['last_name'] : "Stranger";
    $this->username = $first_name;
    $this->fullname = $this->username." ".$last_name;
    $this->site_color = $this->config->item('site_color');


    $user_info['full_name'] = $this->fullname;
    $user_info['network_id'] = $this->user_id;
    $this->lookupname = isset($user_info['middle_initial']) ?
      $last_name.", ".$first_name." ".$user_info['middle_initial']."." :
      $last_name.", ".$first_name;
    $current_path_info = isset($_SERVER['PATH_INFO']) ? ltrim($_SERVER['PATH_INFO'],'/') : "./";
    //$this->nav_info = $this->nav_info_model->generate_navigation_entries($current_path_info);
    $this->nav_info = json_decode('{"categories":{"category_100":{"name":"Instrument Scheduling","entries":{"entry_100.50":{"name":"My Upcoming Reservations","uri":"scheduling\/my_reservations","alt_text":"My Upcoming Reservations"},"entry_100.100":{"name":"Instrument List","uri":"scheduling","alt_text":"Instruments Available"},"entry_100.101":{"name":"&nbsp;—&nbsp;&nbsp;Scanning TEM (STEM)","uri":"scheduling\/calendar\/stem","alt_text":""},"entry_100.102":{"name":"&nbsp;—&nbsp;&nbsp;Liquid Helium Cryo-TEM","uri":"scheduling\/calendar\/lhecryotem","alt_text":""},"entry_100.103":{"name":"&nbsp;—&nbsp;&nbsp;Environmental TEM (ETEM)","uri":"scheduling\/calendar\/etem","alt_text":""},"entry_100.200":{"name":"Instrument Calendar","uri":"scheduling\/calendar","alt_text":"Instrument Scheduling Calendar View"},"entry_100.300":{"name":"Make a New Reservation","uri":"scheduling\/reservation","alt_text":"Make a New Instrument Scheduling Reservation"}}},"category_150":{"name":"General","entries":{"entry_150.150":{"name":"Search Tickets","uri":"search","alt_text":null}}},"category_175":{"name":"User Support","entries":{"entry_175.50":{"name":"New Ticket Entry","uri":"ticket\/new","alt_text":"Make a new ticket"},"entry_175.100":{"name":"My Tickets","uri":"tickets\/mine","alt_text":null},"entry_175.150":{"name":"Most Recent Tickets","uri":"tickets\/most_recent","alt_text":null},"entry_175.200":{"name":"Current Tickets by Status","uri":"tickets\/by_status","alt_text":null},"entry_175.300":{"name":"List Current Tickets by Priority","uri":"tickets","alt_text":null}}},"category_200":{"name":"Equipment \/ Software","entries":{"entry_200.100":{"name":"Available equipment by location","uri":"equipment","alt_text":null},"entry_200.101":{"name":"List of Accessories [0]","uri":"equipment\/accessory","alt_text":""},"entry_200.102":{"name":"List of Instruments [3]","uri":"equipment\/instrument","alt_text":""},"entry_200.103":{"name":"List of Manipulators [0]","uri":"equipment\/manipulator","alt_text":""}}},"category_900":{"name":"Administrative Tasks","entries":{"entry_900.100":{"name":"Current User List","uri":"admin\/users","alt_text":null},"entry_900.200":{"name":"Add New Equipment Items","uri":"admin\/add_equipment","alt_text":"Add New Equipment Items"}}}},"current_page_info":{"name":"Undefined Page","uri":"scheduling\/calendar\/stem"}}',TRUE);
    //$perm_description = $this->user_model->get_permission_level_info($this->admin_access_level);
    $perm_description = "Administrator";
    $this->nav_info['current_page_info']['logged_in_user'] = "{$this->fullname}<br /><span class='tiny'>{$perm_description}</span>";


    $this->page_data = array();
    $this->page_data['navData'] = $this->nav_info;
    $this->page_data['infoData'] = array('current_credentials' => $this->user_id,'full_name' => $this->fullname);
    $this->page_data['username'] = $this->username;
    $this->page_data['fullname'] = $this->fullname;
    $this->page_data['title'] = $this->nav_info['current_page_info']['name'];
    $this->page_data['page_header'] = $this->page_data['title'];
    $this->page_data['load_prototype'] = true;
    $this->page_data['load_jquery'] = false;
    $this->controller_name = $this->uri->rsegment(1);
    // $data_array = array(
      // 'user_id' => $this->user_id
    // );
//    var_dump($user_info);
    //$this->session->set_userdata($data_array);
  }



}
?>
