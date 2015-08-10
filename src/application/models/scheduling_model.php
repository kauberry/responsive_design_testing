<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Scheduling_model                                                        */
/*                                                                             */
/*             functionality for getting ticket info                           */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class Scheduling_model extends CI_Model {
  function __construct(){
    parent::__construct();
    define("USER_CACHE","v_scheduling_eus_user_cache");
    define("INST_CACHE","scheduling_instrument_list");
    define("SLOT_TABLE","scheduling_time_slot_configurations");
    define("RES_TABLE", "v_scheduling_reservations");
    define("RES_INSERT", "scheduling_reservations");
    define("UPCOMING_RES", "v_upcoming_reservations");
    define("RES_TYPES", "scheduling_reservation_types");
    define("PRIV_TABLE", "scheduling_user_class_mapping");
    define("CLASS_INFO", "v_scheduling_user_class_info");
    $this->load->helper(array('color_conversion'));
    //$this->load->model('eus_model','eus');
  }

  function get_user_classifications($simple = false){
    $DB_sched = $this->load->database('default',TRUE);
    $query = $DB_sched->get('scheduling_user_classes');
    $results = array();
    foreach($query->result_array() as $row){
      if($simple){
        $results[$row['user_class_id']] = $row['description'];
      }else{
        $results[$row['user_class_id']] = $row;
      }
    }
    return $results;
  }

  function get_reservations_overview($name_filter){
    //get user list
    $user_list = array();
    $DB_sched = $this->load->database('default',TRUE);

    $DB_sched->select(array(
      'eus_user_id','first_name','last_name','pnnl_network_id',
      'user_class_name as user_class','description as user_class_name'
    ));
    if(!empty($name_filter)){
      $DB_sched->like('first_name', $name_filter)->or_like('last_name', $name_filter)->or_like('pnnl_network_id',$name_filter);
    }
    $query = $DB_sched->get(USER_CACHE);

    if($query && $query->num_rows()>0){
      foreach($query->result_array() as $row){
        $user_list[$row['eus_user_id']] = $row;
      }
    }

    $results_list = array();
    //now get the reservations for each user
    foreach($user_list as $id => $user_info){
      $res_info = $this->get_my_upcoming_reservations($id);
      $user_class = $user_info['user_class_name'];
      $display_name = "{$user_info['first_name']} {$user_info['last_name']} ({$id})";
      if(!array_key_exists($user_class,$results_list)){
        $results_list[$user_class] = array();
      }
      if(!empty($res_info)){
        $results_list[$user_class][$display_name] = array(
          'user_info' => $user_info, 'reservations' => $res_info
        );
      }
    }
    return $results_list;
  }

  function get_current_user_list($network_id_filter = ""){
    $DB_sched = $this->load->database('default',TRUE);

    $select_array = array(
      'eus_user_id', 'first_name', 'middle_initial', 'last_name', 'eus_user_name',
      'primary_email_address', 'alternate_email_address', 'user_classification_level',
      'contact_phone_number', 'pnnl_network_id', 'host_eus_id','description as user_classification'
    );

    $proxy_list = $this->get_user_available_proxy_list($this->eus_user_id);
    $available_users = array_merge($proxy_list, array(intval($this->eus_user_id)));
    if(!empty($network_id_filter)){
      $DB_sched->where_in('eus_user_id',$available_users);
    }
    $query = $DB_sched->select($select_array)->order_by('last_name, first_name')->get(USER_CACHE);
    $results = array();

    $host_cache = array(0 => "None");


    if($query && $query->num_rows()>0){
      foreach($query->result_array() as $row){
        if(!empty($row['host_eus_id']) || $row['host_eus_id'] == 0){
          if(array_key_exists($row['host_eus_id'], $host_cache)){
            $row['eus_host_name'] = $host_cache[$row['host_eus_id']];
          }elseif($row['eus_user_id'] == $row['host_eus_id']){
            $row['eus_host_name'] = "Self";
          }else{
            $host_info = $this->eus->get_name_from_eus_id($row['host_eus_id']);
            $host_name = "{$host_info['first_name']} {$host_info['last_name']}";
            $row['eus_host_name'] = mailto($host_info['email_address'], $host_name);
            $host_cache[$row['host_eus_id']] = $row['eus_host_name'];
          }
        }else{
          $row['eus_host_name'] = "None";
        }
        // $row['eus_user_name'] = "{$row['first_name']} {$row['last_name']}";
        $results[$row['eus_user_id']] = $row;
      }
    }
    return $results;
  }

  function get_my_upcoming_reservations($eus_user_id){
    $DB_sched = $this->load->database('default',TRUE);
    $select_array = array(
      'reservation_id','eus_instrument_id','eus_user_id','eus_user_name',
      'scheduled_by_id','scheduled_by_name','start_time','end_time','duration',
      'reservation_type_id','reservation_type_name','reservation_status_name',
      'eus_proposal_id','created','modified','is_last_minute','view_url','edit_url'
    );
    $DB_sched->where('eus_user_id',$eus_user_id);
    $DB_sched->or_where('scheduled_by_id', $eus_user_id);
    $query = $DB_sched->get(UPCOMING_RES);
    $results = array();
    foreach($query->result_array() as $row){
      $category = $row->eus_user_id == $eus_user_id ? "My Upcoming Reservations" : "Reservations Made By Proxy";
      $results[$category][$row['reservation_id']] = $row;
    }
    return $results;
  }

  function get_my_upcoming_reservations_old($eus_user_id){
    $DB_sched = $this->load->database('default',TRUE);
    $query = $DB_sched->get_where(UPCOMING_RES,array('eus_user_id' => $eus_user_id));
    $results = array();
    foreach($query->result_array() as $row){
      $results[$row['reservation_id']] = $row;
    }
    return $results;
  }

  function get_current_instrument_list($filter = ""){
    $DB_sched = $this->load->database('default',TRUE);
    $result = array();
    $select_array = array(
      'eus_instrument_id','friendly_name','location_id','is_active'
    );
    $DB_sched->where('is_active',1);
    $query = $DB_sched->select($select_array)->get_where(INST_CACHE, array('site_id' => $this->site_id));
    if($query && $query->num_rows()>0){
      foreach($query->result_array() as $row){
        if(empty($row['location_id'])){
          $row['location_id'] = "None Specified";
        }
        $result[$row['eus_instrument_id']] = $row;
      }
    }
    return $result;
  }

  function cancel_existing_reservation($res_id){
    $proxy_list = $this->get_user_available_proxy_list($this->eus_user_id);
    $available_users = array_merge($proxy_list, array(intval($this->eus_user_id)));

    $DB_sched = $this->load->database('default',TRUE);

    //make sure we're allowed to delete this reservation
    $res_info = $this->get_single_reservation($res_id);

    if(empty($res_info)){
      $status_code = 410;
      $message = "A reservation with id = {$res_id} does not exist";
      return array('status_code' => $status_code, 'message' => $message);
    }

    $can_delete = false;

    if($this->scheduling_access_level < 500){
      //not an admin
      if(in_array($res_info['eus_user_id'],$available_users) || in_array($res_info['scheduled_by_id'],$available_users)){
      // if($this->eus_user_id == $res_info['eus_user_id'] || $this->eus_user_id == $res_info['scheduled_by_id']){
        $can_delete = true;
      }
    }else{
      $can_delete = true;
    }
    if($res_info['reservation_status_type_id'] > 1){
      $can_delete = false;
      $status_code = 410;
      $message = "Reservations which started in the past cannot be removed";
      return array('status_code' => $status_code, 'message' => $message);
    }

    if(!$can_delete){
      $status_code = 403;
      $message = "You are not allowed to delete this reservation";
      return array('status_code' => $status_code, 'message' => $message);
    }

    $DB_sched->where('reservation_id',$res_id);
    $DB_sched->update(RES_INSERT,array('deleted' => date('Y-m-d H:i:s'), 'reservation_status_type_id'));
    if($DB_sched->affected_rows() > 0){
      return array('status_code' => 204, 'message' => 'reservation successfully removed');
    }else{
      return array('status_code' => 500, 'message' => 'a server side error occurred that prevented this reservation from being removed');
    }

  }

  function process_reservation($reservation_info){
    $DB_sched = $this->load->database('default',TRUE);
    //$reservation_info should contain fields for...
    // eus_user_id, eus_instrument_id, eus_proposal_id,
    // start_time (as Y-m-d H:i:s),
    // end_time [opt] (as Y-m-d H:i:s), duration [opt] (one or the other),
    // reservation_type_id
    $eus_user_name = $this->eus->get_name_from_eus_id($reservation_info['eus_user_id']);

    if(!isset($reservation_info['reservation_id']) || !$reservation_info['reservation_id']){
      //must be new, some check and add
      $results = $this->_check_reservation_info($reservation_info);

      if(!empty($results['errors'])){
        $status_code = 500;
        $message = "<ul><li style='font-size:1.1em;'>".implode('</li><li>',$results['errors'])."</li></ul>";
        return array('status_code' => $status_code, 'message' => $message);
      }

      //proceed with the insert
      $insert_data = $results['clean_reservation_info'];

      $DB_sched->insert(RES_INSERT, $insert_data);
      if($DB_sched->affected_rows()==0){
        $status_code = 500;
        $message = "Unable to perform database insert";
        return array('status_code' => $status_code, 'message' => $message);
      }

      $results = array(
        'status_code' => 200,
        'message' => "New reservation created for {$eus_user_name['first_name']} {$eus_user_name['last_name']}",
        'return_info' => $insert_data
      );
    }else{
      //looks like an edit of an existing reservation
      //  check and update
      $original_res_info = $this->get_single_reservation($reservation_info['reservation_id']);
      $diff = array_diff_assoc($reservation_info, $original_res_info);
      if(array_key_exists('end_time',$diff)){
        //need to change end_time to duration
        $res_start_time_obj = new DateTime($original_res_info['start_time']);
        $res_end_time_obj = new DateTime($diff['end_time']);
        $duration_s = $res_end_time_obj->getTimeStamp() - $res_start_time_obj->getTimeStamp();
        $duration = intval($duration_s / 60.0);
        unset($diff['end_time']);
        $diff['duration_in_minutes'] = $duration;
      }
      $DB_sched->where('reservation_id',$reservation_info['reservation_id'])->update(RES_INSERT,$diff);
      if($DB_sched->affected_rows()==0){
        $status_code = 500;
        $message = "Unable to perform database update";
        return array('status_code' => $status_code, 'message' => $message);
      }
      $results = array(
        'status_code' => 200,
        'message' => "Reservation {$reservation_info['reservation_id']} Updated",
        'return_info' => $diff
      );
    }


    return $results;

    // extract($reservation_info);
  }

  function _check_reservation_info($reservation_info){

    extract($reservation_info);

    $now = new DateTime();
    $errors = array();


    $tomorrow = clone $now;
    $tomorrow->modify('+1 day');
    $tomorrow->setTime(0,0,0);
    $contingency_cutoff = clone $tomorrow;


    if(!isset($scheduled_by_id)){
      $scheduled_by_id = $this->eus_user_id;
    }

    //check inst exists
    $inst_exists = $this->does_instrument_exist($eus_instrument_id);
    if(!$inst_exists){
      $errors[] = "Instrument '{$eus_instrument_id}' does not exist";
    }

    //does scheduling_type_id exist?
    $res_types = $this->get_reservation_types();
    if(!array_key_exists($reservation_type_id,$res_types)){
      $errors[] = "Reservation Type '{$reservation_type_id} does not exist";
    }else{
      //is this scheduling type ok for this user?
      $sched_privilege_level = $this->get_scheduling_privilege_level($eus_user_id);
      $res_type_info = $res_types[$reservation_type_id];
      if(intval($sched_privilege_level) < intval($res_type_info['availability'])){
        $errors[] = "{$res_type_info['name']} reservations are not available to user {$eus_user_id}";
      }
    }




    //check user validity and token availability
    $available_tokens = $this->get_available_tokens_info($eus_user_id, $eus_instrument_id);


    //process start/end/duration values
    if(isset($start_time)){
      $start_time_obj = strtotime($start_time) ? new DateTime($start_time): false;
    }else{
      $errors[] = "Start time '{$start_time}' is not valid";
    }

    //check proposal validity
    if(strpos($reservation_type_id,'USER')){
      $proposals_for_inst = $this->eus->get_proposals_for_instrument($eus_instrument_id, $start_time_obj->format('Y-m-d'));
      if(array_key_exists('items',$proposals_for_inst) && sizeof($proposals_for_inst['items']) > 0){
        if(!array_key_exists($eus_proposal_id, $proposals_for_inst['items'])){
          //either wrong proposal or not for this instrument
          $errors[] = "EUS Proposal {$eus_proposal_id} either does not exist or is not valid for instrument {$eus_instrument_id}";
        }
      }else{
        //wrong instrument or no proposals for this instrument
        $errors[] = "No EUS Proposals located for instrument {$eus_instrument_id}";
      }
    }

    // echo "start_time => {$start_time_obj->format(DATE_ATOM)}    cont => {$contingency_cutoff->format(DATE_ATOM)}";

    if($available_tokens <= 0 && $start_time_obj->format('Ymd') > $contingency_cutoff->format('Ymd')){
      //really need error message returns
      $errors[] = "No remaining reservation slots for this user";
    }

    //mark as last-minute if within contingency_cutoff
      $is_last_minute = $start_time_obj->format('Ymd') < $contingency_cutoff->format('Ymd') ? 1 : 0;


    if(isset($end_time) && !isset($duration)){
      $end_time_obj = strtotime($end_time) ? new DateTime($end_time) : false;
      $duration = ($end_time_obj->getTimestamp() - $start_time_obj->getTimestamp()) / 60;
    }

    if(!isset($end_time) && isset($duration) && $duration > 0){
      $end_time_obj = clone $start_time_obj;
      $end_time_obj->modify("+{$duration} min");
    }

    if(!$end_time_obj){
      $errors[] = "Ending time '{$end_time} is not valid";
    }

    //are the times valid for a reservation (i.e. at least end time in the future)
    if($end_time_obj < $now){
      //end time already passed? I don't think so, mister...
      $errors[] = "Ending time cannot be in the past";
    }

    if($duration <= 0){
      //no duration found, also a problem...
      $errors[] = "Duration of event must be more than {$duration} minutes";
    }

    //times are good, check overlap with existing reservations
    $overlaps = $this->check_reservation_overlap_info($eus_instrument_id, $start_time_obj, $end_time_obj);
    if(!empty($overlaps)){
      $overlap_count = sizeof($overlaps);
      $pluralize = $overlap_count != 1 ? "s" : "";
      $errors[] = "This reservation would overlap with {$overlap_count} other reservation{$pluralize}";
    }

    //if regular reservation, does this match a res slot for this instrument
    if($reservation_type_id == "REG_USER" && $inst_exists){
      $effective_day = strtolower($start_time_obj->format('l'));
      $time_slots = $this->get_time_slots($eus_instrument_id, $effective_day);
      $time_slots = $time_slots[$effective_day];
      $slot_match_count = 0;
      foreach($time_slots as $ts){
        if($start_time_obj->format("H:i:s") == $ts['start_time'] && $end_time_obj->format("H:i:s") == $ts['end_time']){
          $slot_match_count++;
          break;
        }
      }
      if($slot_match_count == 0){
        $errors[] = "For Regular Reservations, the start and end times must match an available time slot for that instrument";
      }
    }

    $clean_reservation_info = array(
      'eus_instrument_id' => $eus_instrument_id,
      'eus_user_id' => $eus_user_id,
      'start_time' => $start_time_obj->format('Y-m-d H:i:s'),
      'duration_in_minutes' => $duration,
      'scheduled_by_id' => $scheduled_by_id,
      'reservation_type_id' => $reservation_type_id,
      'eus_proposal_id' => $eus_proposal_id,
      'created' => NULL
    );

    if(!empty($comments)){
      $clean_reservation_info['comments'] = $comments;
    }

    //check for external user if EXT_USER
    if($reservation_type_id == 'EXT_USER'){
      $clean_reservation_info['eus_user_id'] = $ext_eus_user_id;
    }

    $results = array('errors' => $errors, 'clean_reservation_info' => array());
    if(empty($errors)){
      $results['clean_reservation_info'] = $clean_reservation_info;
    }

    return $results;
  }

  function check_reservation_overlap_info($eus_instrument_id, $start_time, $end_time){
    $DB_sched = $this->load->database('default',TRUE);
    $ds = $start_time->format('Y-m-d H:i:s'); //desired start time
    $de = $end_time->format('Y-m-d H:i:s');   //desired end time

    $DB_sched->where("(start_time <= '{$ds}' AND end_time < '{$de}' AND end_time > '{$ds}' AND eus_instrument_id = {$eus_instrument_id})");
    $DB_sched->or_where("(start_time <= '{$ds}' AND end_time > '{$de}' AND eus_instrument_id = {$eus_instrument_id})");
    $DB_sched->or_where("(start_time > '{$ds}' AND start_time < '{$de}' AND end_time > '{$de}' AND eus_instrument_id = {$eus_instrument_id})");
    $DB_sched->or_where("(start_time >= '{$ds}' AND end_time < '{$de}' AND eus_instrument_id = {$eus_instrument_id})");
    $DB_sched->or_where("(start_time = '{$ds}' AND end_time = '{$de}' AND eus_instrument_id = {$eus_instrument_id})");

    $query = $DB_sched->get(RES_TABLE);

    $user_info_cache = array();
    $proposal_cache = array();
    $overlaps = array();
    if($query && $query->num_rows()>0){
      //must have some overlapping reservations, send them back
      foreach($query->result_array() as $row){
        $scheduled_by_info = !array_key_exists($row['scheduled_by_id'],$user_info_cache) ? $this->get_user_info_from_cache($row['scheduled_by_id']) : $user_info_cache[$row['scheduled_by_id']];
        $user_info_cache[$row['scheduled_by_id']] = $scheduled_by_info;
        $scheduled_user_info = !array_key_exists($row['eus_user_id'],$user_info_cache) ? $this->get_user_info_from_cache($row['eus_user_id']) : $user_info_cache[$row['eus_user_id']];
        if(empty($scheduled_user_info)){
          //must be an external user
          $scheduled_user_info = $this->eus->get_name_from_eus_id($row['eus_user_id']);
        }
        $user_info_cache[$row['eus_user_id']] = $scheduled_by_info;
        $row['scheduled_user'] = $scheduled_user_info['display_name'];
        if($scheduled_by_info != $scheduled_user_info){
          $row['scheduled_by'] = $scheduled_by_info['display_name'];
        }
        $proposal_name = !array_key_exists($row['eus_proposal_id'],$proposal_cache) ? $this->eus->get_proposal_name($row['eus_proposal_id']) : $proposal_cache[$row['eus_proposal_id']];
        $proposal_cache[$row['eus_proposal_id']] = $proposal_name;
        $row['eus_proposal_name'] = $proposal_name;
        $overlaps[] = $row;
      }
    }
    return $overlaps;
  }

  function get_available_tokens_info($eus_user_id, $eus_instrument_id){
    //look into the reservations table for the next (specified time period, specified on a per user class basis)
    // and see how many "tokens" this user has consumed, how many they are allowed according to the rules
    // return counts and
    $DB_sched = $this->load->database('default',TRUE);

    if($eus_user_id != $this->eus_user_id){
      $other_scheduling_level = $this->get_scheduling_privilege_level($this->eus_user_id);
      if($other_scheduling_level > 400){
        //looks like an admin entering a reservation for someone else
        return 99999;
      }
    }

    $where_array = array(
      'eus_user_id' => $eus_user_id,
      'reservation_type_id' => "REG_USER",
      'eus_instrument_id' => $eus_instrument_id,
      'is_last_minute' => 0
    );

    $query = $DB_sched->where($where_array)->get(UPCOMING_RES);

    $outstanding_reservation_count = 0;

    if($query && $query->num_rows() > 0){
      $outstanding_reservation_count = $query->num_rows();
    }

    //how many are we allowed?
    $token_select = array(
      'number_of_available_slots as slots', 'availability_window_in_days as time'
    );
    $DB_sched->select($token_select)->order_by('user_class_id desc');
    $token_query = $DB_sched->get_where(CLASS_INFO, array('eus_user_id' => $eus_user_id),1);

    $slots = 0;
    if($token_query && $token_query->num_rows()>0){
      $slots = $token_query->row()->slots;
    }else{
      //didn't find them in the map, so assume level 100
      $DB_sched->select('number_of_available_slots as slots')->order_by('user_class_id');
      $level_query = $DB_sched->get('scheduling_user_classes',1);
      $slots = $level_query->row()->slots;
    }


    $available = $slots >= 0 ? $slots - $outstanding_reservation_count : 99999;

    $available = $available >= 0 ? $available : 0;

    return intval($available);

  }

  function get_upcoming_reservations($eus_user_id, $eus_instrument_id = false){
    $DB_sched = $this->load->database('default',TRUE);

    if($eus_instrument_id){
      $DB_sched->where('eus_instrument_id', $eus_instrument_id);
    }
    // $query = $DB_sched->order_by('end_time')->get_where(UPCOMING_RES,array('eus_user_id' => $eus_user_id));
    $DB_sched->where('eus_user_id',$eus_user_id)->or_where('scheduled_by_id',$eus_user_id);
    $query = $DB_sched->order_by('end_time')->get(UPCOMING_RES);
    $results = array();

    $instrument_cache = $this->get_current_instrument_list();
    $user_cache = $this->get_current_user_list();

    // $next_available_time = new DateTime();
    if($query && $query->num_rows()>0){
      foreach($query->result_array() as $row){
        $category = $row['eus_user_id'] == $eus_user_id ? "My Reservations" : "Proxy Reservations";

        $ending_time = new DateTime($row['end_time']);
        if(!isset($next_available_time)){
          $next_available_time = clone $ending_time;
        }
        if($ending_time < $next_available_time){
          $next_available_time = clone $ending_time;
        }
        $row['eus_instrument_name'] = $instrument_cache[$row['eus_instrument_id']]['friendly_name'];
        $row['eus_user_name'] = $user_cache[$row['eus_user_id']]['eus_user_name'];
        if(!empty($row['scheduled_by_id']) && $row['eus_user_id'] != $row['scheduled_by_id']){
          $row['scheduler_name'] = $user_cache[$row['scheduled_by_id']]['eus_user_name'];
        }
        $results[$category][$row['eus_instrument_name']][$row['reservation_id']] = $row;
      }
    }else{
      $next_available_time = new DateTime();
    }

    return array('reservations' => $results, 'next_available_time' => $next_available_time);
  }


  function get_next_available_slot($eus_user_id){
    $DB_sched = $this->load->database('default',TRUE);
    $where_array = array(
      'eus_user_id' => $eus_user_id
    );
    $select_array = array(
      'eus_instrument_id', 'start_time', 'end_time', 'reservation_type_name',
      'eus_proposal_id', 'created', 'modified', 'reservation_type_id'
    );
    $existing_reservations = array();
    $query = $DB_sched->select($select_array)->get_where(UPCOMING_RES,$where_array);
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $start_time = new DateTime($row->start_time);

        $existing_reservations[$row->reservation_type_id][$start_time->format('Ymd-His')] = array(
          'eus_instrument_id' => $row->eus_instrument_id,
          'reservation_type' => $row->reservation_type_name,
          'eus_proposal_id' => $row->eus_proposal_id,
          'start_time' => $start_time,
          'end_time' => new DateTime($row->end_time),
          'created' => new DateTime($row->created),
          'modified' => new DateTime($row->modified)
        );



      }
    }



  }


  function get_instrument_info($eus_instrument_id){
    $DB_sched = $this->load->database('default',TRUE);
    $select_array = array('friendly_name','base_color');
    $where_array = array(
      'eus_instrument_id' => $eus_instrument_id,
      'is_active' => 1
    );
    $query = $DB_sched->select($select_array)->where($where_array)->get(INST_CACHE,1);

    $result = array();

    if($query && $query->num_rows()>0){
      $result = array(
        'friendly_name' => $query->row()->friendly_name,
        'base_color' => $query->row()->base_color
      );
      $result['time_slots'] = $this->get_time_slots($eus_instrument_id);
      return $result;
    }else{
      return false;
    }



  }



  function get_reservation_types($filter = ""){
    $DB_sched = $this->load->database('default',TRUE);
    $select_array = array(
      'reservation_type_id as id',
      'reservation_type_name as name',
      'description', 'availability'
    );
    if(!empty($filter)){
      $DB_sched->where('reservation_type_id',$filter);
    }
    $res_types = array();
    $DB_sched->order_by('sort_order');
    $query = $DB_sched->select($select_array)->where('deleted is NULL')->get(RES_TYPES);
    if($query && $query->num_rows()>0){
      foreach($query->result_array() as $row){
        $res_types[$row['id']] = $row;
      }
    }
    return $res_types;
  }


  function get_time_slots($eus_instrument_id, $specific_day = false){
    $DB_sched = $this->load->database('default',TRUE);
    $select_array = array(
      'start_time','duration_min','effective_day'
    );
    $where_array = array(
      'eus_instrument_id' => $eus_instrument_id,
      'effective_day <>' => 'holiday'
    );

    if(!empty($specific_day)){
      $DB_sched->where('effective_day', $specific_day);
    }

    $DB_sched->where('deleted IS NULL');

    $defined_slots = array();

    $slot_query = $DB_sched->select($select_array)->get_where(SLOT_TABLE,$where_array);

    if($slot_query && $slot_query->num_rows()>0){
      foreach($slot_query->result() as $row){
        $st = new DateTime($row->start_time);
        $et = clone $st;
        $et->modify("+{$row->duration_min} min");
        $defined_slots[$row->effective_day][] = array(
          'start_time' => $row->start_time,
          'end_time' => $et->format('H:i:s'),
          'duration_min' => $row->duration_min);
      }
    }
    return $defined_slots;
  }

  function get_daily_availability($eus_instrument_id, $eus_user_id, $date, $mode){
    $DB_sched = $this->load->database('default',TRUE);
    $today = new DateTime();
    $d = new DateTime($date);
    $t = clone $d;
    $t->modify('+1 days');
    $eff_day = strtolower($d->format('l'));
    $time_slots_raw = $this->get_time_slots($eus_instrument_id, $eff_day);
    $time_slots_raw = $time_slots_raw[$eff_day];
    // $availability = $this->get_availability($eus_instrument_id, $d->format('Y-m-d 00:00:00'), $d->format('Y-m-d 11:59:59'));
    $availability = array();
    $select_array = array(
      'eus_user_id','start_time','end_time','duration_in_minutes',
      'scheduled_by_id','reservation_type_name','reservation_status_name',
      'eus_proposal_id','created','modified','view_url','reservation_id'
    );

    $themed_association_list = $this->eus->is_preferred_status($eus_instrument_id, $eff_day, $d);

    $DB_sched->where("((start_time BETWEEN '{$d->format('Y-m-d')}' AND '{$t->format('Y-m-d')}') OR (end_time BETWEEN '{$d->format('Y-m-d')}' AND '{$t->format('Y-m-d')}'))");
    $DB_sched->select($select_array)->order_by("start_time");
    $query = $DB_sched->get_where(RES_TABLE, array('eus_instrument_id' => $eus_instrument_id));
    // echo $DB_sched->last_query();
    // echo "\n\n";
    // var_dump($query->result_array());
    $time_slots = array();
    foreach($time_slots_raw as $ts){
      $start_time_obj = new DateTime("{$d->format('Y-m-d')} {$ts['start_time']}");
      $end_time_obj = clone $start_time_obj;
      $end_time_obj->modify("+{$ts['duration_min']} min");
      $new_ts_info = array(
        'start_time_obj' => $start_time_obj,
        'end_time_obj' => $end_time_obj,
        'start_time' => $start_time_obj->format('Y-m-d H:i:s'),
        'end_time' => $end_time_obj->format('Y-m-d H:i:s'),
        'duration' => $ts['duration_min'],
        'disabled' => "",
        'reservation_link' => "",
        'state' => "[Available]",
        'selected' => "",
        'display_time' => "<time class='start time' datetime='{$start_time_obj->format('Y-m-d H:i:s')}'>{$start_time_obj->format('g:ia')}</time>&ndash;<time class='end time' datetime='{$end_time_obj->format('Y-m-d H:i:s')}'>{$end_time_obj->format('g:ia')}</time></span>&nbsp;<span class='slot_availability_marker'>"
      );
      if($end_time_obj <= $today){
        //this slot is in the past, disable it
        $new_ts_info['disabled'] = " disabled='disabled'";
        $new_ts_info['state'] = "<span style='color:red'>[Expired]</span>";
      }
      // var_dump($start_time_obj);

      if($query && $query->num_rows()> 0){
        foreach($query->result() as $row){
          $res_start_ts = new DateTime($row->start_time);
          $res_end_ts = new DateTime($row->end_time);
          $res_url = base_url().$row->view_url;
          // var_dump($res_start_ts);
          $new_ts_info['reservation_id'] = intval($row->reservation_id);
          $new_ts_info['selected'] = "";
          $new_ts_info['reserved_by_id'] = $row->eus_user_id;
          $res_user_info = $this->eus->get_name_from_eus_id($row->eus_user_id);

          $new_ts_info['reserved_by_name'] = $res_user_info['display_name'];
          if($start_time_obj >= $res_start_ts && $start_time_obj < $res_end_ts || $end_time_obj > $res_start_ts && $end_time_obj < $res_end_ts){
            //looks like this timestamp falls into the reservation's time space
            $class_disabled = "";
            if($this->scheduling_access_level <= 400 && $eus_user_id != $row->eus_user_id && $eus_user_id != $row->scheduled_by_id){
              $new_ts_info['disabled'] = " disabled='disabled'";
              $class_disabled = " class='disabled_label'";
            }

            // $new_ts_info['selected'] = $mode == 'edit' && $start_time_obj->getTimestamp() == $res_start_ts->getTimestamp() ? " checked='checked'" : "";

            // $new_ts_info['reservation_link'] = base_url()."{$eus_instrument_id}-{$row->eus_user_id}-{$res_start_ts->format('YmdHi')}";
            $new_ts_info['reservation_link'] = base_url().$row->view_url;
            $new_ts_info['display_time'] = "<span {$class_disabled}>{$start_time_obj->format('g:ia')}&ndash;{$end_time_obj->format('g:ia')}</span>";
            $new_ts_info['display_time'] .= "&nbsp;<span class='slot_availability_marker'><span>";
            $new_ts_info['state'] = "<a class='reservation_link' id='reservation_link_{$row->reservation_id}' title='Reserved By {$new_ts_info['reserved_by_name']}' href='{$res_url}'>[Booked]</a>";
            $new_ts_info['disabled'] = " disabled='disabled'";
            break;
          }
        }
      }

      $time_slots[$start_time_obj->format('Gi')] = $new_ts_info;

    }
    $my_page_data = array(
      'd' => $d,
      'time_slots' => $time_slots,
      'eus_instrument_id' => $eus_instrument_id,
      'association_list' => $themed_association_list
    );
    return $my_page_data;

  }


  function get_availability($eus_instrument_id, $eus_user_id, $start_date, $end_date, $token_override = 0){
    $padded_start_date = new DateTime($start_date);
    $padded_start_date->setTime(0,0,0);
    $padded_start_date->modify('-7 days');
    $tokens_available = $this->get_available_tokens_info($eus_user_id, $eus_instrument_id);
    $tokens_available += $token_override;
    $start_date_obj = new DateTime($start_date);
    $build_date = $start_date_obj > new DateTime ? $padded_start_date : new DateTime();
    $start_date_obj = clone $build_date;
    $end_date_obj = new DateTime($end_date);
    while($build_date <= $end_date_obj){
      $date_array[$build_date->format('Y-m-d')] = array(
        'reservation_count' => 0, 'hours_occupied' => 0, 'day_number' => $build_date->format('d')
      );
      $build_date->modify('+1 day');
    }

    $preferred_status = $this->eus->get_preferred_status($eus_instrument_id);

    $DB_sched = $this->load->database('default',TRUE);

    $DB_sched->where('start_time >=', $start_date_obj->format('Y-m-d'))->where('end_time <=', $end_date);
    $DB_sched->order_by('start_time');
    $query = $DB_sched->get_where(RES_TABLE, array('eus_instrument_id' => $eus_instrument_id));
    $availability_array = array(
      'free' => array(),
      'partial' => array(),
      'full' => array(),
      'disallowed' => array(),
      'preferred' => array()
    );


    // $date_array = array();
    $total_reservation_count = 0;

    $today = new DateTime();
    $today->setTime(0,0,0);
    $tomorrow = clone $today;
    $tomorrow->modify('+1 day');
    $tomorrow->setTime(11,59,59);
    $contingency_cutoff = clone $tomorrow;
    // var_dump($contingency_cutoff);
    $contingent_available = array();
    if($query && $query->num_rows()>0){
      $total_reservation_count = 0;
      foreach($query->result() as $row){
        $total_reservation_count++;
        $start_time = new DateTime($row->start_time);
        $reservation_date = $start_time->format('Y-m-d');
        if(!array_key_exists($reservation_date,$date_array)){
          $day_number = $start_time->format('d');
          $date_array[$reservation_date] = array('day_number' => $day_number, 'reservation_count' => 0, 'hours_occupied' => 0);
        }
        $reservation_count = $date_array[$reservation_date]['reservation_count'];
        $reservation_count++;
        $date_array[$reservation_date]['reservation_count'] = $reservation_count;
        $date_array[$reservation_date]['hours_occupied'] += $row->duration_in_minutes / 60.0;

      }

    }

    foreach($date_array as $reservation_date => $res_info){
      $res_date = new DateTime($reservation_date);
      $res_day = strtolower($res_date->format('l'));

      if($tokens_available > 0){
        if($res_info['hours_occupied'] < 3){
          $availability_array['free'][] = $reservation_date;
          if($res_date->getTimestamp() >= $today->getTimestamp() && $res_date->getTimestamp() <= $contingency_cutoff->getTimestamp()){
            $contingent_available[] = $reservation_date;
          }
        }else if($res_info['hours_occupied'] > 20){
          $availability_array['full'][] = $reservation_date;
        }else{
          $availability_array['partial'][] = $reservation_date;
          if($res_date->getTimestamp() >= $today->getTimestamp() && $res_date->getTimestamp() <= $contingency_cutoff->getTimestamp()){
            $contingent_available[] = $reservation_date;
          }
        }
      }else{
        if($res_date->getTimestamp() <= $contingency_cutoff->getTimestamp()){
          if($res_info['hours_occupied'] < 3){
            $availability_array['free'][] = $reservation_date;
            // $contingent_available = true;
            $contingent_available[] = $reservation_date;
          }else if($res_info['hours_occupied'] > 20){
            $availability_array['full'][] = $reservation_date;
          }else{
            $availability_array['partial'][] = $reservation_date;
            // $contingent_available = true;
            $contingent_available[] = $reservation_date;
          }
        }else{
          $availability_array['disallowed'][] = $reservation_date;
        }
      }
      if(array_key_exists($res_day,$preferred_status) && !in_array($reservation_date,$availability_array['disallowed']) && $res_date->getTimestamp() > $contingency_cutoff->getTimestamp()){
        $availability_array['preferred'][] = $reservation_date;
        $availability_array['preferred_info'][$reservation_date] = array('associations' => $preferred_status[$res_day]);
      }
    }
    $results = array(
      'reservations' => $date_array,
      'contingent_available' => $contingent_available,
      'availability' => $availability_array,
      'total_count' => $total_reservation_count
    );
    return $results;
  }


  function get_reservations($instrument_id, $start_date, $duration, $user_id_filter = ""){
    $DB_sched = $this->load->database('default',TRUE);
    if(!empty($user_id_filter)){
      $DB_sched->where('eus_user_id',$user_id_filter);
    }
    $reservations = array();
    //check inst id
    $inst_info = $this->get_instrument_info($instrument_id);
    $inst_name = $inst_info['friendly_name'];
    $inst_color = $inst_info['base_color'];
    if(!$inst_name){
      return $reservations;
    }

    $start = new DateTime($start_date);
    $end = clone $start;
    date_modify($end, "+{$duration} days");

    $DB_sched->where("start_time <= '{$end->format('Y-m-d')}' AND end_time >= '{$start->format('Y-m-d')}'");

    $query = $DB_sched->get_where(RES_TABLE, array('eus_instrument_id' => $instrument_id));
    // echo $DB_sched->last_query();
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $user_color = $row->user_base_color;
        $hsl_user_color = rgbStringToHsl($user_color);
        $hsl_user_border_color = array($hsl_user_color[0],$hsl_user_color[1],$hsl_user_color[2]);
        $start_time = new DateTime($row->start_time);
        $end_time = new DateTime($row->end_time);
        $time_slot_info = $this->generate_time_slot_info($start_time, $end_time);
        $res_id = "{$row->eus_instrument_id}-{$row->eus_user_id}-{$start_time->format('YmdHis')}";
        $user_info = $this->get_user_info_from_cache($row->eus_user_id);
        if(empty($user_info)){
          $user_info = $this->eus->get_name_from_eus_id($row->eus_user_id);
        }
        $hsl_base_color = rgbStringToHsl($user_color);
        $hsl_user_border_color = array(
          $hsl_base_color[0], $hsl_base_color[1] * 0.75, $hsl_base_color[2] * 0.75
        );
        $user_border_color = hslToRgbString($hsl_user_border_color);
        $reservations[$res_id] = array(
          'reservation_id' => $res_id,
          'reservation_type_id' => $row->reservation_type_id,
          'reservation_type_name' => $row->reservation_type_name,
          'eus_instrument_id' => $row->eus_instrument_id,
          'eus_user_id' => $row->eus_user_id,
          'eus_user_name' => $user_info['display_name'],
          'contact_email' => $user_info['email'],
          'eus_proposal_id' => $row->eus_proposal_id,
          'eus_proposal_name' => !empty($row->eus_proposal_id) ? $this->eus->get_proposal_name($row->eus_proposal_id) : "",
          'time_slot_info' => $time_slot_info,
          'start_timestamp' => $start_time,
          'end_timestamp' => $end_time,
          'start_time' => $start_time->format('g:ia'),
          'end_time' => $end_time->format('g:ia'),
          'is_last_minute' => $row->is_last_minute == 0 ? false : true,
          'comments' => $row->comments,
          'base_color' => $user_color,
          'border_color' => $user_border_color,
          'text_color' => $row->text_color,
          'view_url' => $row->view_url
        );
        $other_scheduling_access_level = $this->get_scheduling_privilege_level($row->scheduled_by_id);
        if($other_scheduling_access_level < 400 && $row->eus_user_id != $row->scheduled_by_id){
          $new_user_info = $this->get_user_info_from_cache($row->scheduled_by_id);
          $reservations[$res_id]['proxy_reservation'] = true;
          $reservations[$res_id]['proxy_user_id'] = $reservations[$res_id]['eus_user_id'];
          $reservations[$res_id]['proxy_user_name'] = $reservations[$res_id]['eus_user_name'];
          $reservations[$res_id]['eus_user_id'] = $this->eus_user_id;
          $reservations[$res_id]['eus_user_name'] = $new_user_info['display_name'];
        }else{
          $reservations[$res_id]['proxy_reservation'] = false;
        }
      }
    }
    return $reservations;
  }

  //splits up a reservation across columns if necessary
  function generate_time_slot_info($start_time,$end_time){
    $time_slots = array();
    //check if start and end are on the same day
    if($start_time->format('Y-m-d') == $end_time->format('Y-m-d')){
      $time_slots[] = array('start_time' => $start_time, 'end_time' => $end_time);
    }else{
      //step through time until we hit the end timepoint
      while($start_time < $end_time){
        $next_day = clone $start_time;
        date_modify($next_day, "+1 day");
        $upcoming_midnight = new DateTime($next_day->format('Y-m-d'));
        if($upcoming_midnight < $end_time){
          $time_slots[] = array('start_time' => $start_time, 'end_time' => $upcoming_midnight);
          $start_time = clone $upcoming_midnight;
        }else{
          //this should be our last entry
          $time_slots[] = array('start_time' => $start_time, 'end_time' => $end_time);
          $start_time = clone $end_time;
        }
      }
    }

    $time_slots_with_classes = array();

    foreach($time_slots as $slot){
      // $new_slot = $slot;
      $slot_start_time = $slot['start_time'];
      $slot_end_time = $slot['end_time'];
      $top_class_number = intval($slot_start_time->format('G')) * 60;
      $top_class = "rt{$top_class_number}";
      $bottom_class_number = (24 - intval($slot_end_time->format('G'))) * 60;
      $bottom_class = "rb{$bottom_class_number}";
      $column = intval($slot_start_time->format('w')) + 1;

      $new_slot = array(
        'start_timestamp' => $slot_start_time,
        'end_timestamp' => $slot_end_time,
        'start_time' => $slot_start_time->format('g:ia'),
        'end_time' => $slot_end_time->format('g:ia'),
        'top_class' => $top_class,
        'bottom_class' => $bottom_class,
        'column' => $column
      );

      $time_slots_with_classes[] = $new_slot;
    }

    return $time_slots_with_classes;

  }

  function get_single_reservation_lookup($instrument_id,$user_id,$start_time_string){
    $DB_sched = $this->load->database('default',TRUE);
    $start_time = strtotime($start_time_string) !== FALSE ? new DateTime($start_time_string) : FALSE;
    if(!$start_time){
      $reservation_id = -1;
      //maybe return a list of possible reservations?
    }else{
      //retrieve reservation info
      $where_array = array(
        'eus_instrument_id' => $instrument_id,
        'eus_user_id' => $user_id,
        'start_time' => $start_time->format('Y-m-d H:i:s')
      );
      $query = $DB_sched->select('reservation_id')->where($where_array)->get(RES_TABLE,1);
      if($query && $query->num_rows()>0){
        $reservation_id = $query->row()->reservation_id;
      }
    }
    if(isset($reservation_id)){
      return $this->get_single_reservation($reservation_id);
    }else{
      return array();
    }
  }


  function get_single_reservation($reservation_id){
    $DB_sched = $this->load->database('default',TRUE);
    //check for valid input variables


    $reservation_info = array();
    $res_query = $DB_sched->where('reservation_id',$reservation_id)->get(RES_TABLE,1);
    if($res_query && $res_query->num_rows()>0){
      $reservation_info = $res_query->row_array();
    }else{
      $reservation_info = FALSE;
    }

    return $reservation_info;
  }

  function get_user_info($network_id){
    $DB_sched = $this->load->database('default',TRUE);
    $query = $DB_sched->get_where(USER_CACHE, array('pnnl_network_id' => $network_id),1);
    $result = array();
    if($query && $query->num_rows()>0){
      $result =  array(
        'first_name' => $query->row()->first_name,
        'last_name' => $query->row()->last_name,
        'email' => $query->row()->primary_email_address,
        'display_name' => $query->row()->eus_user_name,
        'eus_user_id' => $query->row()->eus_user_id
      );
    }
    return $result;
  }

  function get_user_info_from_cache($eus_user_id){
    $DB_sched = $this->load->database('default',TRUE);
    $select_array = array(
      'first_name','last_name','primary_email_address','eus_user_id','eus_user_name'
    );
    $result = array();
    $query = $DB_sched->select($select_array)->get_where(USER_CACHE,array('eus_user_id' => $eus_user_id),1);
    if($query && $query->num_rows()>0){
      $result = array(
        'first_name' => $query->row()->first_name,
        'last_name' => $query->row()->last_name,
        'email' => $query->row()->primary_email_address,
        'display_name' => $query->row()->eus_user_name,
        'eus_user_id' => $query->row()->eus_user_id
      );
    }
    return $result;
  }

  function get_scheduling_privilege_level($eus_user_id){
    $DB_sched = $this->load->database('default',TRUE);
    $level = 100;
    $query = $DB_sched->select('user_classification_level as level')->get_where(PRIV_TABLE,array('eus_user_id' => $eus_user_id),1);
    if($query && $query->num_rows() > 0){
      $level = $query->row()->level;
    }
    return $level;
  }

  function get_user_available_proxy_list($eus_user_id){
    $DB_sched = $this->load->database('default',TRUE);
    $proxy_list = array();
    $query = $DB_sched->select('eus_user_id_proxy as id')->where('eus_user_id_primary',$eus_user_id)->get('scheduling_user_proxy_list');
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $proxy_list[] = intval($row->id);
      }
    }
    return $proxy_list;
  }

  function get_instrument_id_from_lookup($instrument_name_fragment){
    $DB_sched = $this->load->database('default',TRUE);
    // $DB_sched->query("friendly_name ILIKE '%{$instrument_name_fragment}'");
    // $DB_sched->or_like('friendly_name',$instrument_name_fragment);
    $DB_sched->where('abbreviation',$instrument_name_fragment);
    $query = $DB_sched->select('eus_instrument_id')->get(INST_CACHE,1);
    $eus_instrument_id = -1;
    if($query && $query->num_rows()>0){
      $eus_instrument_id = $query->row()->eus_instrument_id;
    }
    return $eus_instrument_id;
  }

  function does_instrument_exist($eus_instrument_id){
    $DB_sched = $this->load->database('default',TRUE);
    $where_array = array(
      'eus_instrument_id' => $eus_instrument_id,
      'site_id' => $this->site_id
    );
    $query = $DB_sched->get_where(INST_CACHE, $where_array);
    $exists = $query && $query->num_rows()>0 ? TRUE : FALSE;

    return $exists;
  }




}
?>
