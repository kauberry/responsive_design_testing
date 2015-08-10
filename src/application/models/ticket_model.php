<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Ticket_Model						                                       */
/*                                                                             */
/*             functionality for getting ticket info					       */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class Ticket_model extends CI_Model {
  function __construct(){
    parent::__construct();
    $this->load->helper('url','solr');
    $this->load->model('user_operations_model','user_model');
    define("STATUS_COUNTS_TABLE", "v_ticket_counts_by_state");
    define("PRIORITY_COUNTS_TABLE", "v_ticket_counts_by_priority");
    define("TICKET_DETAILS_TABLE", "v_ticket_details");
    define("TICKET_ACTIONS_TABLE", "ticket_activity");
    define("TICKET_EQUIPMENT_TABLE", "v_ticket_equipment_associations");
    define("NEW_TICKETS", 'tickets');
  } 
  
  public function get_ticket_listing_by_priority($site_id,$priority_filters = false){
    // $accepted_filters = array("all","high","medium","low");
    //catch errant priority values and fix them
    //if(!in_array($priority_filter,$accepted_filters)){ $priority_filter = "all"; }
    
    $DB_data = $this->load->database('default',TRUE);
    if($priority_filters){
      $DB_data->where_in('priority',$priority_filters);
    }
    $DB_data->where('site_id', $site_id);
    $query = $DB_data->order_by('id','desc')->get("v_ticket_details");
    if($query && $query->num_rows()>0){
      $results = array();
      foreach($query->result_array() as $row){
        $row['equipment'] = $this->get_equipment_listing_for_ticket($row['id']);
        $results[$row['priority']][$row['id']] = $row; 
      }
    }
    return $results;     
    
  }
  
  public function get_ticket_listing_by_list($site_id,$ticket_id_list){
    $DB_data = $this->load->database('default',TRUE);
    if(!empty($ticket_id_list)){
      $DB_data->where_in('id',$ticket_id_list);
    }
    $DB_data->where('site_id', $site_id);
    $query = $DB_data->order_by('id','desc')->get("v_ticket_details");
    if($query && $query->num_rows()>0){
      $results = array();
      foreach($query->result_array() as $row){
        $row['equipment'] = $this->get_equipment_listing_for_ticket($row['id']);
        $results['current'][$row['id']] = $row; 
      }
    }
    return $results;     
  }
  
  public function get_ticket_listing_by_user($site_id,$filters = false){
    $user_id = $this->user_id;
    $DB_data = $this->load->database('default',TRUE);
    if($user_id){
      $DB_data->where(array('author_id' => $user_id, 'site_id' => $site_id))->or_where(array('assigned_user_id' => $user_id, 'site_id' => $site_id));
    }else{
      $DB_data->where('site_id', $site_id);
    }
    // $results = array('owned' => array(), 'assigned' => array());
    $results = array();
    $query = $DB_data->order_by('id','desc')->get("v_ticket_details");
    if($query && $query->num_rows()>0){
      foreach($query->result_array() as $row){
        $row['equipment'] = $this->get_equipment_listing_for_ticket($row['id']);
        if($row['author_id'] == $user_id){
          $results['my'][$row['id']] = $row;
        }
        if($row['assigned_user_id'] == $user_id){
          $results['assigned'][$row['id']] = $row;
        }
      }
    }
    return $results;     
  }
   
  public function get_ticket_listing_by_status($site_id,$status_filters = false){
    $accepted_filters = array(
      "all","Pending","Processing","Follow-Up","Closed"
    );
    //catch errant status_filter values and correct them
    //if(!in_array($status_filter,$accepted_filters)) { $status_filter = "all"; }
    
    $DB_data = $this->load->database('default',TRUE);
    if($status_filters){
      $DB_data->where_in('state',$status_filters);
    }
    $DB_data->where('site_id', $site_id);
    $query = $DB_data->order_by('id','desc')->get("v_ticket_details");
    if($query && $query->num_rows()>0){
      $results = array();
      foreach($query->result_array() as $row){
        $row['equipment'] = $this->get_equipment_listing_for_ticket($row['id']);        
        $results[$row['state']][$row['id']] = $row;
      }
    }
    return $results;     
  }
  
  public function get_ticket_listing_by_added($site_id,$filters = false){
    //catch errant status_filter values and correct them
    //if(!in_array($status_filter,$accepted_filters)) { $status_filter = "all"; }
    
    $DB_data = $this->load->database('default',TRUE);
//    if($status_filters){
//      $DB_data->where_in('state',$status_filters);
//    }
    if($filters){
      $DB_data->like('author_name',$filters)->or_like('name',$filters)->or_like('description',$filters);
      // $DB_data->or_like('probe_name',$filters)->or_like('magnet_name',$filters)->or_like('comment',$filters);
    }
    $DB_data->where('site_id', $site_id);
    $query = $DB_data->order_by('id','desc')->get("v_ticket_details");
    if($query && $query->num_rows()>0){
      $results = array();
      foreach($query->result_array() as $row){
         $row['equipment'] = $this->get_equipment_listing_for_ticket($row['id']);        
         $results["most_recently"][$row['id']] = $row;
      }
    }
    return $results;     
  }
  
  public function get_equipment_listing_for_ticket($ticket_id){
    $DB_data = $this->load->database('default',TRUE);
    $entries = array();
    $DB_data->select(array("equipment_identifier","ticket_id"));
    $assoc_equipment_query = $DB_data->where('ticket_id',$ticket_id)->get(TICKET_EQUIPMENT_TABLE);
    if($assoc_equipment_query && $assoc_equipment_query->num_rows()>0){
      foreach($assoc_equipment_query->result() as $row){
        extract(split_equipment_identifier($row->equipment_identifier));
        $entries[$equipment_type][$equipment_id] = array(
          'identifier' => $row->equipment_identifier,
          'id' => $equipment_id,
          'name' => $this->get_equipment_name($row->equipment_identifier)
        );
      }
    }
    return $entries;
  }

  public function get_ticket_listing_by_equipment($equipment_type,$equipment_id){
    $equipment_identifier = make_equipment_identifier($equipment_type,$equipment_id);
    $entries = array();
    $DB_data = $this->load->database('default',TRUE);
    $ticket_listing = array();
    //get tickets with this piece of equipment
    $DB_data->select('ticket_id')->distinct();
    $ticket_query = $DB_data->get_where(TICKET_EQUIPMENT_TABLE,array("equipment_identifier" => $equipment_identifier));
    if($ticket_query && $ticket_query->num_rows()>0){
      foreach($ticket_query->result() as $row){
        $ticket_listing[$row->ticket_id] = array();
      }
    }
    if(!empty($ticket_listing)){
      $details_query = $DB_data->where_in('id',array_keys($ticket_listing))->get(TICKET_DETAILS_TABLE);
      if($details_query && $details_query->num_rows()>0){
        foreach($details_query->result_array() as $row){
          $ticket_id = $row['id'];
          unset($row['id']);
          $entries[$ticket_id] = $row;
        }
      }
      $equipment_list = array();
      $DB_data->select(array("equipment_identifier","ticket_id"));
      $assoc_equipment_query = $DB_data->where_in('ticket_id',array_keys($ticket_listing))->where('ISNULL(`deleted_at`)')->get(TICKET_EQUIPMENT_TABLE);
      if($assoc_equipment_query && $assoc_equipment_query->num_rows()>0){
        foreach($assoc_equipment_query->result() as $row){
          extract(split_equipment_identifier($row->equipment_identifier));
          $entries[$row->ticket_id]['equipment'][$equipment_type][$equipment_id] = array(
            'identifier' => $row->equipment_identifier,
            'id' => $equipment_id,
            'name' => $this->get_equipment_name($row->equipment_identifier)
          );
        }
      }
    }
    
    $my_equipment_name = $this->get_equipment_name(make_equipment_identifier($equipment_type,$equipment_id));
    $results = array(
      'entries' => $entries,
      'section_name' => "{$equipment_type}_{$my_equipment_name}",
      'section_description' => ucwords($equipment_type).": {$my_equipment_name} Tickets"
    );
    return $results;
  }

  private function get_equipment_name($equipment_identifier){
    $DB_data = $this->load->database('default',true);
    $result = false;
    $query = $DB_data->select("display_name")->get_where('tracked_equipment',array('name' => $equipment_identifier),1);
    if($query && $query->num_rows() > 0){
      $result = $query->row()->display_name;
    } 
    return $result;
  }
  
  // public function get_ticket_listing_by_equipment_old($equipment_type,$id){
    // $accepted_equipment_types = array(
      // 'magnet','probe','software_application','misc'
    // );
    // if(in_array($equipment_type, $accepted_equipment_types)){
//       
    // }
    // $DB_data = $this->load->database('default',true);
    // $query = $DB_data->where("{$equipment_type}_id",$id)->order_by('id','desc')->get("v_ticket_details");
    // if($query && $query->num_rows()>0){
      // $results = array();
      // $entries = array();
      // foreach($query->result_array() as $row){
        // $entries[$row['id']] = $row;
      // }
      // $row = $query->row_array();
      // $equipment_name = $row["{$equipment_type}_name"];
      // $results = array(
        // 'entries' => $entries,
        // 'section_name' => "{$equipment_type}_{$equipment_name}",
        // 'section_description' => ucwords($equipment_type).": {$equipment_name} Tickets"
      // );
//       
//       
    // }else{
      // $results = false;
    // }
    // return $results;
  // }
  
  private function get_ticket_listing_generic($filter_array){
    $DB_data = $this->load->database('default',TRUE);
    
    $DB_data->where($filter_array)->get();
    
  }
  
  //retrieve counts for page summary block (i.e. "pending (2)  processing (11), etc.")
  public function get_ticket_class_counts($site_id){
    //define the parent collection object
    $counts = array();
    $DB_data = $this->load->database('default', TRUE);
        
    //grab ticket counts by status
    $DB_data->where('site_id', $site_id);
    $status_query = $DB_data->get(STATUS_COUNTS_TABLE);
    
    //make sure we got something back
    if($status_query && $status_query->num_rows()>0){
      $status = array();
      //loop through and add to a holding collection
      foreach($status_query->result() as $row){
        $row->state = ucwords($row->state);
        $status[$row->state] = intval($row->counts);
      }
      //add to the parent object
      if(sizeof($status) > 0){
        $counts['state'] = $status;
      }
    }
    
    //grab ticket counts by priority
    $DB_data->where('site_id', $site_id);
    $priority_query = $DB_data->get(PRIORITY_COUNTS_TABLE);
    
    //same as above, but for priorities
    if($priority_query && $priority_query->num_rows()>0){
      //$priority_list = array("High" => 0,"Medium" => 0,"Low" => 0,"Unspecified" => 0);
      $priority = array();
      foreach($priority_query->result() as $row){
        $row->priority = ucwords($row->priority);
        $priority_list[$row->priority] = intval($row->counts);
        
      }
      if(sizeof($priority_list) > 0){
        $counts['priority'] = $priority_list;
      }
    }
    return $counts; 
  }

   
  //retrieve ticket details
  public function get_ticket_details($ticket_id,$site_id){
    $DB_data = $this->load->database('default',TRUE);
    $ticket_info = false;
    if($ticket_id >= 0){
      $DB_data->where('id',$ticket_id);
    }else{
      $DB_data->limit(1);
    }
    $DB_data->where('site_id',$site_id);
    $details_query = $DB_data->get(TICKET_DETAILS_TABLE);
    // echo $DB_data->last_query();
    if($details_query && $details_query->num_rows()>0){
      $ticket_info = $details_query->row_array();
    }
    
    $equipment_info = $this->get_equipment_listing_for_ticket($ticket_id);
    if(!empty($equipment_info)){
      foreach($equipment_info as $equip_type => $equip_item){
        foreach($equip_item as $equip_id => $equip_info){
          $ticket_info["{$equip_type}_name"] = $equip_info['name'];
          $ticket_info["{$equip_type}_id"] = $equip_id;
        }
      }
      $ticket_info['equipment_info'] = $equipment_info;
    }
    return $ticket_info;
  }
  
  private function update_equipment_associations($equipment_list,$ticket_id){
    //get the list of existing equipment:
    $existing_equipment = array();
    
    $equipment_identifier_splitter = '/^([^_]+)_(\d+)$/';
    
    $DB_data = $this->load->database('default',TRUE);
    
    $query = $DB_data->select('equipment_identifier')->get_where('ticket_equipment_associations', array('ticket_id' => $ticket_id));
    
    if($query && $query->num_rows() > 0){
      foreach($query->result() as $row){
        preg_match($equipment_identifier_splitter,$row->equipment_identifier,$matches);
        $equip_id = intval($matches[2]);
        $extracted_equip_type = $matches[1];
        $existing_equipment[$extracted_equip_type] = $row->equipment_identifier;
      }
    }
    
    $null_equipment = array();
    $equipment_insert = array();
    
    foreach($equipment_list as $type => $item){
      $null_equipment[$type] = "{$type}_0000";
    }
   
    $non_changing_equipment = array_intersect($existing_equipment,$equipment_list);
  
    $old_equip_to_update = array_diff($equipment_list,$non_changing_equipment);
    
    $delete_list = array_diff($existing_equipment,$equipment_list);
    
    $insert_list = array_diff($old_equip_to_update, $null_equipment);
    
    
    foreach($delete_list as $equip_type => $equip_id){
      $DB_data->where(array('ticket_id' => $ticket_id, 'equipment_identifier' => $equip_id))->from('ticket_equipment_associations')->delete();
    }
    
    foreach($insert_list as $equip_type => $equip_id){
      $equipment_insert[$equip_id] = array(
        'equipment_identifier' => $equip_id,
        'ticket_id' => $ticket_id,
        'created_at' => null,
        'updated_at' => null,
        'creator_id' => $this->user_id,
        'updater_id' => $this->user_id
      );
    }
    if(sizeof($equipment_insert) > 0){
      $DB_data->insert_batch('ticket_equipment_associations',$equipment_insert);
      if($DB_data->affected_rows() > 0){
        $equipment_updated = true;
        trigger_solr_update();
      }
    }
    
            
    
    
    // echo "\nexisting\n";
    // var_dump($existing_equipment);
//    
    // echo "\nnew\n";
    // var_dump($equipment_list);
//    
    // echo "\nto delete\n";
    // var_dump($delete_list);
// 
    // echo "\nto insert\n";
    // var_dump($insert_list);
    

  }
  
  
//     
    // if(array_key_exists('equipment',$update_values)){
      // $equipment = $update_values['equipment'];
      // unset($update_values['equipment']);
    // }else{
      // $equipment = false;
    // }
//     
    // var_dump($equipment);
//     
//  

    // $existing_equipment = array();
    // $new_inserts = array();
    // $cleared_items = array();
    // $equipment_insert = array();
    // $equipment_delete = array();
//     
    // if($equipment){
      // //make sure they all exist
      // $equipment = $this->check_equipment_entry_existence($equipment);
//       

//       
      // var_dump($equipment);
      // foreach($equipment as $type => $entry){
        // preg_match($equipment_identifier_splitter,$entry,$matches);
        // $equip_id = intval($matches[2]);
        // $extracted_equip_type = $matches[1];
        // echo "item => {$entry} ; equip_id => {$equip_id} ; equip_type => {$extracted_equip_type}\n";
        // if($equip_id > 0){
          // $new_inserts[$extracted_equip_type][] = $equip_id;
        // }else{
          // echo "clearing {$extracted_equip_type} #{$equip_id}\n";
          // $cleared_items[$extracted_equip_type][] = $equip_id;
        // }
        // if($equip_id > 0 && !in_array($entry,$existing_equipment)){
          // $equipment_insert[$entry] = array(
            // 'equipment_identifier' => $entry,
            // 'ticket_id' => $ticket_id,
            // 'created_at' => null,
            // 'updated_at' => null,
            // 'creator_id' => $this->user_id,
            // 'updater_id' => $this->user_id
          // );
        // }
      // }
//       
//       
      // //wipe out the existing entries that aren't in the new set
      // foreach($existing_equipment as $existing_item){
        // preg_match($equipment_identifier_splitter,$existing_item,$matches);
        // $equip_id = intval($matches[2]);
        // $extracted_equip_type = $matches[1];
//         
        // if(!array_key_exists($existing_item, $equipment) && array_key_exists($extracted_equip_type,$new_inserts)){
          // //not in change set, so delete
          // $equipment_delete[$existing_item] = array(
            // 'equipment_identifier' => $existing_item,
            // 'ticket_id' => $ticket_id
          // );
        // }
      // }
      // foreach($equipment_delete as $equip_id => $item_info){
        // $DB_data->where(array('ticket_id' => $item_info['ticket_id'], 'equipment_identifier' => $equip_id))->from('ticket_equipment_associations')->delete();
      // }
//       
      // var_dump($cleared_items);
//       
      // foreach($cleared_items as $equipment_type => $equip_id){
        // "deleting {$equipment_type} based on {$equip_id}\n\n";
        // $DB_data->like('equipment_identifier', $equipment_type)->where('ticket_id',$item_info['ticket_id'])->from('ticket_equipment_associations')->delete();
      // }
//       
      // if(sizeof($equipment_insert) > 0){
        // $DB_data->insert_batch('ticket_equipment_associations',$equipment_insert);
        // if($DB_data->affected_rows() > 0){
          // $equipment_updated = true;
        // }
      // }
    // }

 
  
  
  public function update_ticket($ticket_id, $update_values, $equipment_types){
    $equipment_identifier_splitter = '/^([^_]+)_(\d+)$/';
    
    $updated_assigned_user = false;
    //update our assigned user
    if(isset($update_values['assigned_user_id'])){
      $assigned_user_id = $update_values['assigned_user_id'];
      unset($update_values['assigned_user_id']);
      //update the assigned user table independently;
      $this->update_assigned_user($ticket_id,$assigned_user_id);
      unset($update_values['assigned_user_id']);
      $updated_assigned_user = true;
    }
    
    
    if($equipment_types){
      $update_values = $this->extract_equipment_entries($update_values, $equipment_types);
    }   
    
    if(array_key_exists('equipment',$update_values)){
      $equipment = $update_values['equipment'];
      unset($update_values['equipment']);
    }else{
      $equipment = array();
    }
    
    
    
    //pull out the equipment entry changes
    $DB_data = $this->load->database('default',TRUE);
    
    if(!empty($update_values)){
      $DB_data->where(array('site_specific_id' => $ticket_id, 'site_id' => $this->site_id))->update(NEW_TICKETS,$update_values);
      $ticket_data_updated = $DB_data->affected_rows() > 0 ? TRUE : FALSE;
    }
    
    $this->update_equipment_associations($equipment, $ticket_id);
    trigger_solr_update();

    return $ticket_id;
  }
  
  // //update ticket from uploaded form data
  // public function update_ticket_old($ticket_id, $update_values){
    // $updated_assigned_user = false;
    // if(isset($update_values['assigned_user_id'])){
      // $assigned_user_id = $update_values['assigned_user_id'];
      // unset($update_values['assigned_user_id']);
      // //update the assigned user table independently;
      // $this->update_assigned_user($ticket_id,$assigned_user_id);
      // unset($update_values['assigned_user_id']);
      // $updated_assigned_user = true;
    // }
    // //no other changes after updating assigned user so return
    // if(sizeof($update_values) == 0) { return $ticket_id; }
//     
    // //must be more things to change, keep going
    // if(isset($update_values['title'])){
      // $update_values['name'] = $update_values['title'];
      // unset($update_values['title']);
    // }
//     
    // $DB_data = $this->load->database('default',TRUE);
    // $DB_data->where(array('site_specific_id' => $ticket_id, 'site_id' => $this->site_id))->update(NEW_TICKETS,$update_values);
    // if($DB_data->affected_rows()>0 || $updated_assigned_user){
      // return $ticket_id;
    // }else{
      // return false;
    // }    
  // }
  
  public function delete_ticket($ticket_id){
    $result = false;
    $DB_data = $this->load->database('default',TRUE);
    $DB_data->where(array('site_specific_id' => $ticket_id, 'site_id' => $this->site_id))->set('deleted_at','NOW()',false)->update(NEW_TICKETS);
    if($DB_data->affected_rows()>0){
      $result = true;
      trigger_solr_update();
    }
    return $result;
  }
  
  public function new_ticket($values,$equipment_types){
    $values['created_at'] = date('Y-m-d H:i:s');
    $values['updated_at'] = null;
    $values['author_id'] = $this->user_id;
    $values['creator_id'] = $this->user_id;
    $values['site_id'] = $this->site_id;
    $values['site_specific_id'] = $this->get_next_available_site_specific_id($this->site_id); //could have a race condition here, but I doubt it will be an issue
    if(isset($values['title'])){
      $values['name'] = $values['title'];
      unset($values['title']);
    }
    
    $values = $this->extract_equipment_entries($values, $equipment_types);
    
    // var_dump($values);
    
    $equipment = $values['equipment'];
    unset($values['equipment']);
    unset($values['type']);
    
    //verify equipment list
    if(sizeof($equipment) > 0){
    $equipment = $this->check_equipment_entry_existence($equipment);
    }
    
    $DB_data = $this->load->database('default',TRUE);
    
    $DB_data->trans_start();
    
    $DB_data->insert(NEW_TICKETS,$values);
    
    if($DB_data->affected_rows()>0){
      $ticket_id = $DB_data->insert_id();
    }else{
      $ticket_id = false;
    }
    
    if($DB_data->trans_status() === FALSE){
      $DB_data->trans_rollback();
    }
    else{
      //update the tracked equipment tables
      $equipment_insert = array();
      foreach($equipment as $type => $entry){
        $equipment_insert[] = array(
          'equipment_identifier' => $entry,
          'ticket_id' => $ticket_id,
          'created_at' => $values['created_at'],
          'updated_at' => null,
          'creator_id' => $this->user_id,
          'updater_id' => $this->user_id
        );
      }
      if(sizeof($equipment_insert) > 0){
      $DB_data->insert_batch('ticket_equipment_associations',$equipment_insert);
    }
    }
    
    if($DB_data->trans_status() === FALSE){
      $DB_data->trans_rollback();
    }else{
      $DB_data->trans_commit();
    }
    trigger_solr_update();
    
    
    return $values['site_specific_id'];

    

    
  }
  
  private function extract_equipment_entries($values,$equipment_types){
    $new_values = array();
    $equipment = array();
    foreach($values as $entry_name => $entry_value){
      if(stristr($entry_name,'_id')){
        $equipment_type_name = str_replace('_id','',$entry_name);
        if(array_key_exists($equipment_type_name, $equipment_types)){
          $equipment[$equipment_type_name] = $entry_value;
        }else{
          $new_values[$entry_name] = $entry_value;
        }
      }else{
        $new_values[$entry_name] = $entry_value;
      }
    }
    if(!empty($equipment)){
      $new_values['equipment'] = $equipment;
    }
    return $new_values;
  }
  
  
  private function check_equipment_entry_existence($equipment_entries){
        
    $DB_data = $this->load->database('default',TRUE);
    $select_array = array('id','type');
    $DB_data->where_in('id', $equipment_entries);
    $query = $DB_data->select($select_array)->get_where('v_full_equipment_list', array('site_id' => $this->site_id));
    $found_equipment = array();
    foreach($query->result() as $row){
      $found_equipment[$row->type] = $row->id;
    }
    
    $equipment_identifier_splitter = '/^([^_]+)_(\d+)$/';
    
    foreach($equipment_entries as $item){
      preg_match($equipment_identifier_splitter,$item,$matches);
      $equip_id = intval($matches[2]);
      $extracted_equip_type = $matches[1];
      if($equip_id == 0){
        $found_equipment[$extracted_equip_type] = $item;
      }
    }
    
    return $found_equipment;
  }
  
  
  public function get_next_available_site_specific_id($site_id){
    $DB_data = $this->load->database('default',TRUE);
    $query = $DB_data->select('id')->order_by('id desc')->get_where('v_ticket_details_w_deleted',array('site_id' => $site_id),1);
    $last_id = $query->row()->id;
    return $last_id + 1;
  }
  
  public function update_assigned_user($ticket_id,$user_id){
    //check that user_id exists in the user table first
    if(!$this->user_model->is_user_in_database($user_id)){
      return;
    }
    
    $DB_data = $this->load->database('default',TRUE);
    
    $data = array('assigned_user_id' => $user_id, 'ticket_id' => $ticket_id);
    $DB_data->insert('ticket_assignments',$data);
    if($DB_data->affected_rows()>0){
      trigger_solr_update();
    }
  }
    
  public function update_comment($ticket_id,$comment){
    $DB_data = $this->load->database('default',TRUE);
    
    $data = array('comment' => $comment);
    $DB_data->where(array('ticket_id' => $ticket_id))->insert(NEW_TICKETS,$data);
    if($DB_data->affected_rows()>0){
      trigger_solr_update();
    }
  }

  private function get_next_site_specific_id($site_id){
    $DB_data = $this->load->database('default',TRUE);
    // $query = $DB_data
  }
}
  
?>