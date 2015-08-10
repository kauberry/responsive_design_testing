<?php 
if(!defined('BASEPATH'))
  exit('No direct script access allowed');

function get_user_details_opw( $user_id ){
  $results = get_name_and_prn_array_opw('prn',$user_id);
  if(array_key_exists($user_id, $results)){
    return $results[$user_id];
  }else{
    $results = retrieve_user_info_from_cache($user_id);
    return $results;
  }
}

function get_name_and_prn_list( $search_type, $query, $additional_fields_array = false ){
  return json_encode(get_name_and_prn_array_opw($search_type,$query));
}

function get_name_and_prn_array_opw($search_type,$query){
  $CI =& get_instance();
  $valid_search_types = array(
    'prn'=>'network_id',
    'name'=>'last_name',
    'email'=>'internet_mail_address'
  );
  
  if(array_key_exists($search_type,$valid_search_types)){
    $search_type = $valid_search_types[$search_type];
  }else{
    $retval = array(
      "entries" => null,
      "error" => "'$search_type' is not a valid search type"
    );
    return $retval;
  }
  
  $employee_table = "vw_pub_bmi_employee";
  $rbac_table =  "vw_pub_rbac_role_all_members";
  $department_table = "vw_pub_bmi_department";
  
  $DB_opwhse = $CI->load->database('opwhse', TRUE);
  
  //get main user details
  $select_array = array(
    "lower(e.network_id) as prn", "lower(e.network_id) as id", "COALESCE(e.pref_first_name,e.first_name,'') as first_name",
    "COALESCE(e.pref_middle_init,e.middle_initial,'') as middle_initial", "COALESCE(e.pref_last_name,e.last_name,'') as last_name",
    "e.business_title as title", "d.desc30 as department", "COALESCE(e.primary_work_phone,e.contact_work_phone,'') as telephone",
    "e.internet_email_address as mail"
  );
  
  $DB_opwhse->select($select_array);
  $DB_opwhse->like(array($search_type => $query));
  $DB_opwhse->join("{$department_table} d","d.org_struc_id = e.reporting_org_id");
  $query = $DB_opwhse->get("{$employee_table} e");
  $retval = array();
  $emplid_lookup = array();
  if($query && $query->num_rows()>0){
    foreach($query->result_array() as $row){
      $row['id'] = strtolower($row['id']);
      $row['mail'] = strtolower($row['mail']);
      $emplid_lookup[strval($row['prn'])] = $row['id'];
      unset($row['prn']);
      $retval[strtolower(strval($row['id']))] = $row;
      $retval[strtolower(strval($row['id']))]['group_list'] = "";
    }
    $group_select = array('m.parent_role_name as group_name','lower(e.network_id) as prn');
    
    $DB_opwhse->select($group_select)->order_by('m.emplid');
    $DB_opwhse->like('m.parent_role_name','emsl-ticket-tracker');
    $DB_opwhse->join("{$employee_table} e","m.emplid = e.emplid");
    $groups_query = $DB_opwhse->where_in('e.network_id',$emplid_lookup)->get("{$rbac_table} m");
    if($groups_query && $groups_query->num_rows()>0){
      foreach($groups_query->result_array() as $group_row){
        $retval[$emplid_lookup[$group_row['prn']]]['group_list'][] = $group_row['group_name'];
      }
    }else{
      //$retval[$emplid_lookup[$retval['id']]]['group_list'][] = "";
    }
  }
     
  return $retval;
}

function retrieve_user_info_from_cache($prn){
  $CI =& get_instance();
  $DB_info = $CI->load->database('default',TRUE);
  $select_array = array(
    "network_id as id", "first_name","'' as middle_initial","last_name",
    "'' as title", "'' as department", "telephone","email as mail"
  );
  $query = $DB_info->where('network_id',$prn)->get('user_cache',1);
  $results = array();
  if($query && $query->num_rows()>0){
    $results = $query->row_array();
    $results['group_list'] = array('EMSL-Ticket-Tracker.microscopy.scheduling.users');
  }
  return $results;
}







?>