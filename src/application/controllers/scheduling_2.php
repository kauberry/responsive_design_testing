<?php
//require_once('baseline_controller.php');

class Scheduling extends CI_Controller {

  function __construct() {
    parent::__construct();
    //$this->load->helper(array('inflector','url','opwhse_search','time','date','network','form','calendar'));
    //$this->load->library(array('table'));
    //$this->load->model('user_operations_model','user');
    //$this->load->model('eus_model','eus');
    //$this->load->model('scheduling_model', 'sched');
    //$this->eus_user_info = $this->sched->get_user_info($this->user_id);
    //$this->eus_user_id = $this->eus_user_info['eus_user_id'];
    //$this->scheduling_access_level = $this->sched->get_scheduling_privilege_level($this->eus_user_info['eus_user_id']);
    //$this->user_classifications = $this->sched->get_user_classifications();

  }

  public function index(){
    //$instrument_list = $this->sched->get_current_instrument_list();
    //$this->page_data['instrument_list'] = $instrument_list;

    //redirect('scheduling/calendar');
    $this->page_data['content'] = "<div>test content</div>";
    $this->load->view('equipment_list_report', $this->page_data);

  }


  public function make_reservation(){
    if($this->input->post()){
      $res_info = $this->input->post();
    }elseif($this->input->is_ajax_request() || file_get_contents('php://input')){
      $HTTP_RAW_POST_DATA = file_get_contents('php://input');
      $res_info = json_decode($HTTP_RAW_POST_DATA,true);
    }
    $results = $this->sched->process_reservation($res_info);

    if($results['status_code'] != 200){
      $response = $results['message'];
      $success = false;
      transmit_array_with_json_header($response,"Adding reservation failed", false);
    }else{
      transmit_array_with_json_header($results, $results['message'], TRUE);
    }

  }

  public function delete_reservation($reservation_id){
    // if($this->input->get() || $this->input->is_ajax_request()){
      $results = $this->sched->cancel_existing_reservation($reservation_id);
      set_status_header($results['status_code']);
      $success = $results['status_code'] < 300 ? true : false;
      transmit_array_with_json_header($results,$results['message'],$success,$results['status_code']);
    // }
  }


  public function my_reservations($eus_user_id = -1){
    if($eus_user_id < 0){
      $eus_user_id = $this->eus_user_id;
    }
    $res = $this->sched->get_upcoming_reservations($eus_user_id);
    $this->page_data['upcoming_reservations'] = $res;
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;
    $this->page_data['view_name'] = "scheduling/my_reservations_view.html";
    $this->page_data['css_uris'] = array(
      '/stylesheets/tickets.css'
    );
    $this->load->view('scheduling/view_wrapper',$this->page_data);
  }



  public function calendar($instrument_id = "", $include_date = ""){
    if(empty($instrument_id)){
      $instrument_id = 34105;
      //no instrument id, so show the list of available instruments
    }

    if(!is_numeric($instrument_id)){
      //using an abbreviation, look it up
      $instrument_id = $this->sched->get_instrument_id_from_lookup($instrument_id);
    }

    $ajax = FALSE;
    if($this->input->is_ajax_request()){
      $ajax = TRUE;
    }

    // $this->page_data['view_name'] = "scheduling/calendar_view.html";
    $this->page_data['view_name'] = "";
    $this->page_data['title'] = "Instrument Scheduling Calendar";
    $this->page_data['page_header'] = $this->page_data['title'];

    $current_date = new DateTime();

    $include_date = empty($include_date) ? $current_date->format('Ymd') : $include_date;
    $formatted_include_date = new DateTime($include_date);


    $instrument_list = $this->sched->get_current_instrument_list();

    $calendar_format = format_calendar($include_date);
    $start_date = $calendar_format[0]['date_info'];
    $end_date = $calendar_format[6]['date_info'];
    $week_start_date = new DateTime($start_date);
    $week_end_date = new DateTime($end_date);
    date_modify($week_end_date, "+1 day");
    $this->page_data['calendar_format'] = $calendar_format;

    $this->page_data['current_instrument_id'] = $instrument_id;
    $this->page_data['instrument_list'] = $instrument_list;
    $this->page_data['current_time'] = $current_date;
    $this->page_data['include_date'] = $include_date;
    $this->page_data['formatted_include_date'] = $formatted_include_date->format('m/d/Y');
    $this->page_data['week_start_date'] = $week_start_date;
    $this->page_data['week_end_date'] = $week_end_date;
    if($ajax == true){
      $reservations = $this->sched->get_reservations($instrument_id, $start_date, 7);
      $this->page_data['reservations'] = $reservations;
      $this->load->view("scheduling/calendar_view.html",$this->page_data);
    }else{
      $this->page_data['css_uris'] = array(
        '/scripts/bootstrap-datepicker/css/bootstrap-datepicker-smaller.standalone.css',
        '/stylesheets/calendar_view.css'
      );
      $this->page_data['script_uris'] = array(
        '/scripts/bootstrap-datepicker/js/bootstrap-datepicker.min.js',
        '/scripts/calendar_view.js'
      );
      $this->page_data['load_prototype'] = false;
      $this->page_data['load_jquery'] = true;
      $this->load->view('scheduling/view_wrapper_single_column', $this->page_data);
    }
  }



  public function get_instrument_info_ajax($eus_instrument_id, $format = "html"){
    if(!$this->input->is_ajax_request()){
      //send error since not a machine-based call
    }
    if($format == "json"){
      transmit_array_with_json_header($this->sched->get_instrument_info($eus_instrument_id));
    }else{
      //assume html block

    }
  }

  public function get_instrument_availability($eus_instrument_id, $eus_user_id, $start_date_string, $end_date_string, $token_override = 0){
    $date_array = $this->sched->get_availability($eus_instrument_id, $eus_user_id, $start_date_string, $end_date_string, $token_override);
    transmit_array_with_json_header($date_array);
  }

  public function reservation_overview($name_filter = ""){
    if($this->scheduling_access_level < 400){
      //not authorized, redirect to my_reservations
      redirect(('scheduling/my_reservations'));
    }

    $results = $this->sched->get_reservations_overview($name_filter);
    echo "<pre>";
    var_dump($results);
    echo "</pre>";
  }



  public function view_reservation($reservation_info){
    // $reservation_info = $this->sched->get_single_reservation($reservation_id);
    echo "<pre>";
    var_dump($reservation_info);
    echo "</pre>";

    $this->page_data['page_header'] = "View Reservation";
    $this->page_data['title'] = "View Reservation";
    $this->page_data['reservation_id'] = $reservation_info['reservation_id'];
    $this->page_data['view_name'] = 'scheduling/reservation_view_item_types/'. strtolower($reservation_info['reservation_type_id']) .'_entry_view.html';
    $eus_user_info = $this->eus->get_name_from_eus_id($reservation_info['eus_user_id']);
    $sched_by_info = $this->sched->get_user_info_from_cache($reservation_info['scheduled_by_id']);
    $reservation_info['eus_user_name'] = "{$eus_user_info['first_name']} {$eus_user_info['last_name']}";
    $reservation_info['scheduled_by_name'] = "{$sched_by_info['first_name']} {$sched_by_info['last_name']}";
    $instrument_info = $this->sched->get_instrument_info($reservation_info['eus_instrument_id']);

    $reservation_info['eus_instrument_name'] = $instrument_info['friendly_name'];
    $this->page_data['res'] = $reservation_info;
    $this->page_data['css_uris'] = array(
      '/stylesheets/reservations_edit_view.css',
      '/stylesheets/reservations_static_view.css',
      '/stylesheets/tickets.css'
    );
    // $this->page_data['script_uris']

    $ordered_list = array(
      'user_information' => array(
        'eus_user_name' => "Scheduled For",
        'scheduled_by_name' => "Scheduled By"
      ),
      'general_info' => array(
        'reservation_id' => 'Reservation ID',
        'reservation_type_name' => 'Reservation Class',
        'eus_instrument_name' => "Instrument"
      ),
      'timing' => array(
        'start_time' => 'Starting Time',
        'end_time' => 'Ending Time',
        'duration_in_minutes' => 'Duration'
      ),
      'modification_info' => array(
        'created' => "Entered",
        'modified' => "Last Modified",
        'reservation_status_name' => "Current Status"
      )
    );

    $this->page_data['ordering_list'] = $ordered_list;

    $this->load->view('scheduling/view_wrapper',$this->page_data);


  }


  //code to make / edit / delete a reservation
  public function reservation($mode = 'new',$reservation_id = ""){
    $modes = array('new','edit','view','delete');
    if(!in_array($mode,$modes)){
      $mode = 'new';
    }

    //get the list of available instrumentation, leave a placeholder on the generated page
    // for related proposals via AJAX
    $instrument_list = $this->sched->get_current_instrument_list();

    if($this->scheduling_access_level > 400){
      $this->page_data['current_user_list'] = $this->sched->get_current_user_list();
      $this->page_data['reservation_type_list'] = $this->sched->get_reservation_types();
    }else{
      $this->page_data['current_user_list'] = $this->sched->get_current_user_list($this->user_id);
      $this->page_data['reservation_type_list'] = $this->sched->get_reservation_types("REG_USER");
    }
    $proposal_info = array();

    $reservation_identifier = "";

    if(!empty($reservation_id)){
      $match_pattern = '/(?P<instrument_id>\w+)[-_](?P<eus_user_id>.+)[-_](?P<start_time>(?P<start_year>\d{4})(?P<start_month>\d{2})(?P<start_date>\d{2})(?P<start_hour>\d{2})(?P<start_minute>\d{2})(?P<start_second>\d{0,2}))/i';
      if(preg_match($match_pattern,$reservation_id,$m)){
        $inst_id = $m['instrument_id'];
        $user_id = $m['eus_user_id'];
        $date_string = $m['start_year'].$m['start_month'].$m['start_date'];
        $time_string = $m['start_hour'].$m['start_minute'].$m['start_second'];
        $reservation_info = $this->sched->get_single_reservation_lookup($inst_id,$user_id,"{$date_string}{$time_string}");
        $reservation_parsed = array('eus_instrument_id' => $inst_id, 'date' => $date_string, 'time' => $time_string);
      }else if(is_numeric($reservation_id)){
        //looks like a reservation_id
        $reservation_info = $this->sched->get_single_reservation($reservation_id);
        $inst_id = $reservation_info['eus_instrument_id'];
        $user_id = $reservation_info['eus_user_id'];
        $start_time_obj = new DateTime($reservation_info['start_time']);
        $time_string = $start_time_obj->format('Hi');
        $date_string = $start_time_obj->format('Ymd');
      }
    }
    if($mode == 'delete'){
      $this->delete_reservation($reservation_id);
      return;
    }

    if($mode != 'new'){
      //deconstruct reservation_id

      //not a new request, so grab reservation info

      //make sure we actually got a reservation back from this combination
      if(!$reservation_info){
        //redirect to search page?
        redirect(('scheduling/reservation'));
      }

      if($mode == 'view'){
        $this->view_reservation($reservation_info);
        return;
      }

      //does this person own this request (or are they an admin)?
      // if not, redirect them to the view page
      if($this->eus_user_id != $reservation_info['eus_user_id'] && $this->eus_user_id != $reservation_info['scheduled_by_id'] && $this->scheduling_access_level < 400){
        redirect("scheduling/reservation/view/{$id}");
      }

      $proposal_info = $this->eus->get_proposals_for_instrument($reservation_info['eus_instrument_id']);

      $user_info = $this->sched->get_user_info_from_cache($reservation_info['eus_user_id']);
      $inst_info = $this->sched->get_instrument_info($reservation_info['eus_instrument_id']);
      $this->page_data['title'] = ucwords($mode)." Reservation";
      $this->page_data['reservation_identifier'] = "{$inst_id}-{$user_id}-{$date_string}{$time_string}";
    }else{
      if(!empty($reservation_parsed)){
        //somebody's trying to make a click-through reservation
        $reservation_info = array(
          'eus_instrument_id' => $reservation_parsed['eus_instrument_id'],
          'start_time' => "{$reservation_parsed['date']}{$reservation_parsed['time']}",
          'reservation_id' => -1
        );
      }else{
        $reservation_info = array(
          'eus_instrument_id' => -1, 'reservation_id' => -1
        );
      }
      // if($this->scheduling_access_level < 400){
        // $available_slots = $this->sched->get_available_tokens_info($this->eus_user_id);
        // if($available_slots <= 0){
          // //show existing reservations
          // $this->page_data['title'] = "No Reservation Slots Remaining";
          // $this->page_data['page_header'] = "Scheduling Information";
          // $this->page_data['upcoming_reservations'] = $this->sched->get_upcoming_reservations($this->eus_user_id);
          // $this->page_data['user_classifications'] = $this->user_classifications;
          // $this->page_data['my_user_info'] = $this->eus_user_info;
          // $this->page_data['privilege_level'] = $this->scheduling_access_level;
          // $this->page_data['eus_user_id'] = $this->eus_user_id;
          // $this->page_data['css_uris'] = array(
            // '/stylesheets/tickets.css'
          // );
          // //get the next available slot
          // $this->load->view('scheduling/insufficient_tokens_view', $this->page_data);
          // return;
        // }
      // }

      $user_info = $this->eus_user_info;
      // $user_info = $this->sched->get_user_info($this->user_id);
      // $reservation_info = array('eus_instrument_id' => -1);
      $this->page_data['title'] = "Make a New Reservation";
      $inst_info = array();
      $inst_id = FALSE;
      $this->page_data['reservation_identifier'] = "new";
    }
    $this->page_data['css_uris'] = array(
      '/scripts/select2/select2.css',
      '/stylesheets/reservations_edit_view.css',
      '/stylesheets/tickets.css',
      '/scripts/jquery-timepicker/jquery.timepicker.css',
      '/scripts/bootstrap-datepicker/css/bootstrap-datepicker.standalone.css'
    );
    $this->page_data['script_uris'] = array(
      '/scripts/reservations.js',
      '/scripts/select2/select2.js',
      '/scripts/jquery-timepicker/jquery.timepicker.min.js',
      '/scripts/bootstrap-datepicker/js/bootstrap-datepicker.min.js',
      '/scripts/datepair/datepair.js',
      '/scripts/datepair/jquery.datepair.js'
    );

    $this->page_data['page_mode'] = $mode;
    $this->page_data['proposal_info'] = $proposal_info;
    $this->page_data['instrument_list'] = $instrument_list;
    $this->page_data['inst_id'] = $inst_id;
    $this->page_data['reservation_info'] = $reservation_info;
    $this->page_data['instrument_info'] = $inst_info;
    $this->page_data['user_info'] = $user_info;
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;
    $this->page_data['view_name'] = "scheduling/reservation_edit_view.html";
    $this->page_data['page_header'] = $this->page_data['title'];

    $this->load->view('scheduling/view_wrapper',$this->page_data);

  }


  public function get_time_slot_types($eus_instrument_id){
    $time_slots = $this->sched->get_time_slots($eus_instrument_id);
    transmit_array_with_json_header($time_slots);
  }



  public function instrument_config($mode = 'list'){
    $categorized_list = $this->eus->get_ers_instruments_list(false, "");
    $this->page_data['instrument_list'] = $categorized_list;
    $this->page_data['css_uris'] = array(
      '/stylesheets/equipment_list.css'
    );
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;
    $this->load->view('scheduling/instrument_selection_list', $this->page_data);
  }


  function get_daily_timeslots($eus_instrument_id,$eus_user_id, $datestring, $mode = "new", $reservation_id = false){
    $available_slots = $this->sched->get_available_tokens_info($eus_user_id, $eus_instrument_id);
    $token_override = $mode == 'edit' ? 1 : 0;
    $avail = $this->sched->get_daily_availability($eus_instrument_id,$eus_user_id,$datestring,$mode);
    $associations_desc = "No Proposal Restrictions";
    $show_warning = false;
    $my_date = new DateTime($datestring);
    $datestring = $my_date->format('Y-m-d');
    $end_date = clone $my_date;
    $end_date->modify('+1 month');
    $end_date_string = $end_date->format('Y-m-d');
    $contingent = $this->sched->get_availability($eus_instrument_id,$eus_user_id,$datestring,$end_date_string, $token_override);
    // var_dump($contingent);
    $this->page_data['reservation_id'] = $reservation_id ? $reservation_id : -1;
    if($this->scheduling_access_level < 400 && $available_slots <= 0 && !$contingent['contingent_available']){
      //show existing reservations
      $this->page_data['title'] = "No Reservation Slots Remaining";
      $this->page_data['page_header'] = "Scheduling Information";
      $this->page_data['upcoming_reservations'] = $this->sched->get_upcoming_reservations($eus_user_id,$eus_instrument_id);
      $this->page_data['user_classifications'] = $this->user_classifications;
      $this->page_data['my_user_info'] = $this->eus_user_info;
      $this->page_data['privilege_level'] = $this->scheduling_access_level;
      $this->page_data['eus_user_id'] = $this->eus_user_id;
      $this->page_data['css_uris'] = array(
        '/stylesheets/tickets.css'
      );
      //get the next available slot
      $response_text = $this->load->view('scheduling/insufficient_tokens_insert.html', $this->page_data, true);
      $status = "denied";
    }else{
      // if(!empty($avail['association_list']) && !$contingent['contingent_available']){
      if(!empty($avail['association_list']) && !in_array($datestring, $contingent['contingent_available'])){
        $associations = implode('/', $avail['association_list']);
        $associations_desc = "Instrument reservations limited to<br />{$associations}-aligned proposals only";
        $show_warning = true;
      }
      // var_dump($avail);
      $response_text = $this->load->view('scheduling/reservation_slots_insert.html', $avail,true);
      $status = "authorized";
    }
    transmit_array_with_json_header(
      array(
        'html' => $response_text,
        'status' => $status,
        'associations_desc' => $associations_desc,
        'show_warning_indicator' => $show_warning
      )
    );
  }



  public function user_list(){
    $user_list = $this->sched->get_current_user_list();
    $this->page_data['title'] = "Current User List";
    $this->page_data['page_header'] = "Current User List";
    $this->page_data['user_list'] = $user_list;
    $this->load->view('scheduling/user_listing_view.php', $this->page_data);
  }




  public function advanced_datepicker_load(){
    $this->load->view('scheduling/alternate_datetime_picker_insert_view.html');
  }



  public function instrument_list($filter = ""){
    $inst_list = $this->sched->get_current_instrument_list($filter);
    $this->page_data['title'] = "Current Instrument List";
  }




  public function test_overlap($eus_instrument_id,$start_time,$end_time){
    $st = new DateTime($start_time);
    $et = new DateTime($end_time);

    $overlaps = $this->sched->check_reservation_overlap_info($eus_instrument_id,$st,$et);
    echo "<br /><br /><pre>";
    var_dump($overlaps);
    echo "</pre>";
  }




  public function get_reservation_overlap($eus_instrument_id,$start_time,$end_time){
    $st = new DateTime($start_time);
    $et = new DateTime($end_time);

    $overlaps = $this->sched->check_reservation_overlap_info($eus_instrument_id,$st,$et);
    $overlap_info = array('overlap_count' => sizeof($overlaps), 'overlap_info' => $overlaps);
    if(sizeof($overlaps) > 0){
      $this->load->view('scheduling/reservation_overlap_insert.html',$overlap_info);
    }
    print("");
  }




  public function test_get_user_info($eus_user_id){
    var_dump($this->sched->get_user_info_from_cache($eus_user_id));
  }

  public function test_get_tokens($eus_user_id = -1){
    if($eus_user_id == -1){
      $eus_user_id = $this->eus_user_id;
    }
    //get instrument list
    $inst_list = $this->sched->get_current_instrument_list();

    foreach($inst_list as $inst_id => $inst_info){
      $tokens = $this->sched->get_available_tokens_info($eus_user_id,$inst_id);
      echo "{$inst_info['friendly_name']} tokens => {$tokens}<br />";
    }
  }

  public function test_get_preferred($instrument_id){
    var_dump($this->eus->get_preferred_status($instrument_id));
  }

  public function test_get_upcoming($eus_user_id = -1){
    if($eus_user_id == -1){
      $eus_user_id = $this->eus_user_id;
    }
    echo "<pre>";
    var_dump($this->sched->get_upcoming_reservations($eus_user_id));
    echo "</pre>";
  }

  public function job_status($job_id = -1){
    $raw_post = file_get_contents("php://input");
    echo "raw post => {$raw_post}";
    $values = json_decode($raw_post,true);
    // if(!$values && $job_id > 0){
      // //must not have a list of values, so just check the one
      // $values = array($job_id);
    // }
    // $results = $this->status->get_job_status($values, $this->status_list);
    // transmit_array_with_json_header($results);
  }




}
?>
