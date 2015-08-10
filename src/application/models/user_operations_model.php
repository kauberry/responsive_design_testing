<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     User_operations_model                                                   */
/*                                                                             */
/*             functionality dealing with user-management and lookups          */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class User_operations_model extends CI_Model {
  
  function __construct(){
    parent::__construct();
//    define("USER_CACHE_TABLE2", 'user_cache');
    // define("PERM_LOOKUP","user_privilege_levels");
    $this->load->helper('opwhse_search','solr');
  }


  function refresh_user_info($prn,$site_id){
    $DB_info = $this->load->database('default', TRUE);
    //grab user info from Ops Whse
    $user_details_array = get_name_and_prn_array_opw('prn',$prn);
    if(empty($user_details_array)){
      return array();
    }
    $entries = $user_details_array[$prn];
    

    $username = $entries['first_name'] != null ? $entries['first_name'] : "Anonymous Stranger";
    $fullname = $username." ".$entries['last_name'];
    
    $values = array(
        'first_name' => $entries['first_name'],
        'last_name' => $entries['last_name'],
        'display_name' => $fullname,
        'email' => $entries['mail'],
        'telephone' => $entries['telephone'],
        'updated_at' => NULL 
      );
          
    //check for prn in database
    if($this->is_user_in_database($prn) && $entries['first_name'] != null) {
      //update user_info
      $DB_info->update("user_cache",$values, array('network_id' => $prn));
    }else{
      //add user_info to cache
      $values['network_id'] = $prn;
      $values['created_at'] = null;
      $DB_info->insert("user_cache",$values);
    }
    
    //check if user is mapped to this site
    $map_query = $DB_info->where(array('network_id' => $prn, 'site_id' => $site_id))->get('v_user_cache');
    if($map_query && $map_query->num_rows()>0){
      //must be mapped, so we're ok
    }else{
      //not mapped to this site, add them
      $map_insert_data = array(
        'network_id' => $prn, 'site_id' => $site_id
      );
      $DB_info->insert("user_mappings", $map_insert_data);
    }
    
    $retval = $DB_info->affected_rows() > 0 ? true : false;
    return $retval;
  }
  
  function get_user_permissions_level($group_list,$site_id){
    // var_dump($group_list);
    $DB_info = $this->load->database('default',TRUE);
    if(!$group_list || !is_array($group_list)){
      $group_list = array();
    }
    $DB_info->where('site_id',$site_id);
    $query = $DB_info->select(array('privilege_level','group_list'))->get('user_privilege_levels');
    // echo $DB_info->last_query();
    $max_level = 100;
    if($query && $query->num_rows()>0){
      // var_dump($query->result_array());
      $levels = array();
      foreach($query->result() as $row){
        $group_intersect = array_intersect($group_list,explode(',',$row->group_list));
        // var_dump($group_intersect);
        if(!empty($group_intersect)){
          $levels[$row->privilege_level] = $group_intersect; 
        }
      }
      $levels = array_filter($levels);
      krsort($levels);
      foreach($levels as $level => $list){
        $max_level = $level > $max_level ? $level : $max_level;
        continue;
      }
    }
    return $max_level;
  }
  
  function get_user_permissions_level_new($group_list){
    
  }
 
   function is_user_in_database($prn){
    //look for user
    $DB_info = $this->load->database('default',true);
    $query = $DB_info->where('network_id', $prn)->from("v_user_cache");
    $retval = $DB_info->count_all_results() > 0 ? true : false;      
    return $retval;
  }
  
  function is_user_update_required($last_observed_timestamp){
    $stored_timestamp = strtotime($last_observed_timestamp);
    $now = time();
    $term = 24 * 60 * 60; //24 hours
    $diff = $now - $stored_timestamp;
    
    $response = $diff >= $term ? true : false;
     
    return $response;
  }
  
  function get_user_assignment_picklist(){
    $DB_data = $this->load->database('default',TRUE);
    $DB_data->order_by('display_name')->where('site_id',$this->site_id);
    $query = $DB_data->select(array('display_name','telephone','network_id'))->get_where('v_user_cache',array('is_staff' => 1));
    $results = array();
    if($query && $query->num_rows() > 0){
      foreach($query->result() as $row){
        $results[$row->network_id] = "{$row->display_name} - Phone: {$row->telephone}";
      }
    }
    array_unshift($results, " Select a staff member...");
    return $results;
  }
  
  function get_autosuggest_name_list( $query = false){
    $DB_data = $this->load->database('default',TRUE);
    if($query){
      $DB_data->like('display_name',$query);
    }
    $query = $DB_data->where('site_id',$this->site_id)->get('v_user_cache');
    $results_for_json = array();
    $result_array = array();
    if($query && $query->num_rows()>0){
      foreach ($query->result_array() as $row){
        $info = $row;
        $prn = $row['network_id'];
        $fullname = $info['display_name'];
        $infoblock = isset($info['telephone']) ? "phone: {$info['telephone']}" : "";
        $results_for_json[] = array(
          'id'=>$prn,
          'value'=>$fullname,
          'info'=>$infoblock
        );
      }     
    }
    $final_results = array(
      'results'=>$results_for_json
    );
    return json_encode($final_results);
  }

  function get_current_user_list($filter){
    $DB_data = $this->load->database('default',true);
    if($filter){
      $DB_data->like('name',$filter)->or_like('user_id',$filter);
    }
    $sorted = false;
    $query = $DB_data->where('site_id',$this->site_id)->get('v_users');
    // echo $DB_data->last_query();
    if($query && $query->num_rows()>0){
      //$results = $query->result_array(); 
      foreach($query->result_array() as $row){
        $group_name = $row['is_staff'] == 1 ? "Staff Members" : "Other Users";
        $sorting_name = "{$row['last_name']}_{$row['first_name']}";
        $results[$group_name][$sorting_name] = $row;
      }
      $sorted = array();
      foreach($results as $group_name => $member){
        ksort($member);
        $sorted[$group_name] = $member;
      }
      ksort($results);
    }
    return $sorted;
  }
  
  function get_permission_level_info($admin_access_level){
    $DB_data = $this->load->database('default',TRUE);
    $query = $DB_data->get_where("user_privilege_levels",array('privilege_level' => $admin_access_level),1);
    if($query && $query->num_rows()>0){
      $desc = $query->row()->privilege_name;
    }else{
      $desc = 'Guest';
    }
    return $desc;
  }

  function change_user_staff_status($user_id,$is_staff){
    $success = false;
    $DB_data = $this->load->database('default',TRUE);
    $is_staff_numeric = $is_staff ? 1 : 0;
    $DB_data->where('site_id',$this->site_id)->where('network_id',$user_id)->update('user_mappings',array('is_staff' => $is_staff_numeric));
    if($DB_data->affected_rows()>0){
      $success = true;
      trigger_solr_update();
    }
    return $success;
  }
  
}
