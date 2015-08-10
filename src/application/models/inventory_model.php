<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/*                                                                             */
/*     Inventory_model                                                         */
/*                                                                             */
/*             functionality for getting ticket info                           */
/*                                                                             */
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class Inventory_model extends CI_Model {
  function __construct(){
    parent::__construct();
    $this->load->library('table');
    $this->load->helper(array('form','edit_equipment','inflector','solr'));
    define("EQUIPMENT_LOCATION_TABLE","v_equipment_by_location_extended");
    define("EQUIPMENT_ASSOC_TABLE", "tracked_equipment_associations");
    define("EQUIPMENT_INFO_LOOKUP", 'v_tracked_equipment_basic_info');
    define("EQUIPMENT_MD_LOOKUP", "tracked_equipment_metadata");
    define("FIELD_LOOKUP_TABLE", "equipment_field_details");
    define("EQUIPMENT_TYPES_TABLE", "tracked_equipment_types");
  }
  
  
  /* publicly exposed functions */
  
  public function get_equipment_identifier_and_name($equipment_type,$equipment_id){
    $DB_data = $this->load->database('default',TRUE);
    $DB_data->select(array("equipment_type","equipment_identifier","name"));
    $DB_data->where(array("id" => $equipment_id, "equipment_type" => $equipment_type));
    $query = $DB_data->get('v_tracked_equipment_basic_info',1);
    $results = array();
    if($query && $query->num_rows() > 0){
      $row = $query->row();
      $results = array(
        'equipment_type' => $row->equipment_type, 
        'equipment_identifier' => $row->equipment_identifier,
        'equipment_name' => $row->name);
    }
    return $results;
  }
  
  public function get_equipment_by_location($site_id, $type_filter = false){
    $DB_data = $this->load->database('default', TRUE);
    $DB_data->where('item_type !=', 'software');
    $DB_data->where('site_id', $site_id);
    $DB_data->order_by('location');
    $query = $DB_data->get(EQUIPMENT_LOCATION_TABLE);
    $locations = array();
    foreach($query->result() as $row){
      $formatted_description = !empty($row->description) ? "<ul class='desc_list'><li>".str_replace("\n","</li><li>",$row->description)."</li></ul>" : "";
      $item = array("name" => $row->name, "description" => $row->description, "display" => $formatted_description);
        $locations[$row->location][$row->item_type][$row->id] = $item;
    }
    return $locations;
  }
  
  /* private functions for getting equipment lists and data */
  
  
  public function get_equipment_details($equipment_type,$equipment_id,$edit_mode){
    //get basic info about the equipment      
    $details = $this->get_equipment_basic_details($equipment_type, $equipment_id, $edit_mode);
    
    //get associated equipment list
    $associated_equipment = $this->get_associated_equipment($equipment_type, $equipment_id);
    // $associated_equipment = $this->get_associated_equipment_old('v_probes_by_magnet',$equipment_id,'probe');
    
    $results = array(
      'details' => $details,
      'equipment' => $associated_equipment
    );
    return $results;
  }  
  
  
  
  /* functions for getting equipment picklists to use in tickets */
  
  public function get_picklists($site_id,$equipment_info = false, $excluded_equipment = false){
    $DB_data = $this->load->database('default',TRUE);
    $equipment_list = array();
    $selected_items = array();
    $DB_data->select(array("type","description"))->where("deleted_at is null")->where("site_id",$site_id);
    $query = $DB_data->get("tracked_equipment_types");
    // echo $DB_data->last_query();
    // var_dump($equipment_info);
    if($equipment_info && is_array($equipment_info)){
      foreach($equipment_info as $item_type => $item){
        foreach($item as $item_id => $item_info){
          // $selected_items[$item_type] = "{$item_type}_".str_pad($item_id,3,"0",STR_PAD_LEFT);
          $selected_items[$item_type] = $item_info['identifier'];
        }
      }
    }
    if($query && $query->num_rows() > 0){
      foreach($query->result() as $row){
        $selected_item = array_key_exists($row->type,$selected_items) ? $selected_items[$row->type] : "{$row->type}_0000";
        // echo $selected_item;
        $pick_list_data = $this->get_equipment_array($row->type,$site_id);
        if($pick_list_data){
          if($excluded_equipment){
            foreach($excluded_equipment as $identifier){
              if(array_key_exists($identifier, $pick_list_data)){
                unset($pick_list_data[$identifier]);
              }
            }
          }
          $eq_identifier = "{$row->type}_0000";
          $pick_list_data[$eq_identifier] = "Please Select a {$row->description}...";
          $desc_array = explode(" ",$row->description);
          $last_word = plural(array_pop($desc_array));
          array_push($desc_array,$last_word);
          $plural_description = implode(" ",$desc_array);
          
          $equipment_list[$row->type] = array(
            "equipment_type" => $row->type,
            "description" => $row->description,
            "plural_description" => $plural_description,
            "picklist" => form_dropdown("{$row->type}_id",$pick_list_data,$selected_item,"class='equipment_dropdown' placeholder='Select an Applicable Equipment Item' id='{$row->type}_id' style='width:100%;'")
          );
        }
      }
    }
    return $equipment_list;
  }

  public function get_equipment_types($site_id){
    $DB_data = $this->load->database('default',true);
    $DB_data->where("`deleted_at` is null");
    $results = array();
    $query = $DB_data->get_where(EQUIPMENT_TYPES_TABLE,array('site_id' => $site_id));
    if($query && $query->num_rows()>0){
      foreach($query->result_array() as $row){
        $results[$row['type']] = $row;
      }
    }
    return $results;
  }
  
  
  private function retrieve_equipment_query($equipment_type, $site_id, $name_filter = false){
    $DB_data = $this->load->database('default', TRUE);
    if($name_filter) {
      $DB_data->like('name',$name_filter);
    }    
    $select_array = array(
      "Name", "Description", "location_name as `Current Location`", "id", "type as item_type"
    );
    $DB_data->select($select_array)->order_by('Name');
    $DB_data->where('site_id',$site_id)->where('is_deleted',0);
    $query = $DB_data->like(array("type" => $equipment_type))->get("v_full_equipment_list");
    // echo $DB_data->last_query();
    return $query;
  }
  
  public function get_equipment_array($equipment_type,$site_id){
    $query = $this->retrieve_equipment_query($equipment_type,$site_id);
    $data = array();
    if($query && $query->num_rows() > 0){
      foreach($query->result_array() as $row){
        $location = isset($row['Current Location']) && !empty($row['Current Location']) ? " [{$row['Current Location']}]" : "";
        $data[$row['id']] = "{$row['Name']}{$location}";
      }
    }else{
      $data = false;
    }
    return $data;
  }
  
  public function get_equipment_table($equipment_type, $name_filter){
    $site_id = $this->site_id;
    $query = $this->retrieve_equipment_query($equipment_type, $site_id, $name_filter);
    $tmpl = array(
      'table_open'      => "<table class=\"members_table\" id=\"pending_param_file_table\">",
      'row_start'       => '<tr class="prim_row">',
      'row_alt_start'   => '<tr class="sec_row">',
      'heading_row_start' => '<tr>',
      'heading_row_end' => '</tr>'
    );
    
    $this->table->set_template($tmpl);

    $table_data = array();
    if($query && $query->num_rows > 0){
      foreach($query->result_array() as $row){
        $item_type = array_pop($row);
        $equipment_identifier = split_equipment_identifier(array_pop($row));
        $equipment_id = $equipment_identifier['equipment_id'];
        $row['Name'] = anchor("equipment/{$equipment_type}/{$equipment_id}",$row['Name']);
        $row['Description'] = !empty($row["Description"]) ? "<ul class='equipment_desc'><li>".str_replace("\n","</li><li>",$row["Description"])."</li></ul>" : "";
        if(empty($row["Current Location"])) { array_pop($row); }      
        $table_data[] = $row;
      }
      $this->table->set_heading(array_keys($row));
      $table = $this->table->generate($table_data);
    }else{
      $table = "<h2>No equipment found in the ".$this->get_equipment_display_name($equipment_type,true)." category</h2>";
    }
    return $table;
  }

  private function get_equipment_basic_details($equipment_type,$equipment_id,$edit_mode){
    $DB_data = $this->load->database('default',TRUE);
    $equipment_identifier = make_equipment_identifier($equipment_type,$equipment_id);
    
    $field_lookup = array();
    $hidden_fields = array("equipment_identifier","equipment_type");
    
    $returned_results = array();
    //get baseline data
    $query = $DB_data->get_where(EQUIPMENT_INFO_LOOKUP,array('equipment_identifier' => $equipment_identifier),1);
    // echo $DB_data->last_query();
    if($query && $query->num_rows()>0){
      $results = $query->row_array();
      $returned_results = array();
      foreach($results as $name => $value){
        if(!in_array($name,$hidden_fields)){
          $returned_results[$name] = empty($value) ? "" : $value;
        }
      }
    }
    
    //get field information
    $field_query = $DB_data->get_where(FIELD_LOOKUP_TABLE,array('equipment_type' => $equipment_type));
    if($field_query && $field_query->num_rows() > 0){
      foreach($field_query->result_array() as $row){
        unset($row["id"]);
        $field_lookup[$row['field_name']] = $row;
      }
    }
    
    
    //get metadata
    $md_select_array = array(
      "name","value"
    );
    $DB_data->select($md_select_array);
    $md_query = $DB_data->get_where(EQUIPMENT_MD_LOOKUP,array('equipment_identifier' => $equipment_identifier));
    if($md_query && $md_query->num_rows()>0){
      foreach($md_query->result() as $row){
        if(!in_array($row->name,$hidden_fields)){
          $units = !empty($field_lookup[$row->name]["units"]) && !$edit_mode ? " ".$field_lookup[$row->name]["units"] : ""; 
          $returned_results[$row->name] = $row->value.$units;
        }
      }
    }
    return $returned_results;
  }
    

  public function get_associated_equipment($equipment_type,$equipment_id){
    $result = array();
    $assoc_list = array();
    $equipment_identifier = "{$equipment_type}_".str_pad($equipment_id, 4, "0",STR_PAD_LEFT);
    $DB_data = $this->load->database('default',TRUE);
    
    $equipment_types = $this->get_equipment_types($this->site_id);
    
    foreach($equipment_types as $equip_type => $equip_type_info){
      if($equip_type != $equipment_type){
        $result[$equip_type] = array();
      }
    }
    
    //grab association data from EQUIPMENT_ASSOC_TABLE where this item is parent
    $query_1 = $DB_data->select('member_2 as member')->get_where(EQUIPMENT_ASSOC_TABLE, "`member_1` = '{$equipment_identifier}' and ISNULL(`deleted_at`)");
    //echo $DB_data->last_query();
    if($query_1 && $query_1->num_rows() > 0){
      foreach($query_1->result() as $row){
        if(!in_array($row->member,$assoc_list)){
          $assoc_list[] = $row->member;
        }
      }
    }
    
    //grab association data from EQUIPMENT_ASSOC_TABLE where this item is child
    $query_2 = $DB_data->select('member_1 as member')->get_where(EQUIPMENT_ASSOC_TABLE, "`member_2` = '{$equipment_identifier}' and ISNULL(`deleted_at`)");
    // echo $DB_data->last_query();
    if($query_2 && $query_2->num_rows() > 0){
      foreach($query_2->result() as $row){
        if(!in_array($row->member,$assoc_list)){
          $assoc_list[] = $row->member;
        }
      }
    }
    // var_dump($assoc_list);
    if($assoc_list && sizeof($assoc_list) > 0){
      $info_query = $DB_data->where_in('equipment_identifier',$assoc_list)->get(EQUIPMENT_INFO_LOOKUP);
      if($info_query && $info_query->num_rows()>0){
        foreach($info_query->result() as $row){
          if(!array_key_exists($row->equipment_type, $result)){
            $result[$row->equipment_type] = array();
          }
          $result[$row->equipment_type][$row->id] = array(
            "name" => $row->name, 
            "description" => $row->description,
            "equipment_identifier" => $row->equipment_identifier
          );
        }
      }
    }
    return $result;
  }

  public function delete_equipment_item($equipment_type_id,$equipment_id){
    $DB_data = $this->load->database('default',true);
    $where_clause = array("equipment_type" => $equipment_type_id, "legacy_id" => $equipment_id);
    $update_info = array(
      'deleted_at' => date('c'),
      'deleter_id' => $this->user_id
    );
    $DB_data->where($where_clause)->update("tracked_equipment", $update_info);
    $status = $DB_data->affected_rows() == 1 ? true : false;
    return $status;
  }
  
  public function get_associated_ticket_listing($equipment_type,$equipment_id){
    $DB_data = $this->load->database('default',TRUE);
    //get the equipment identifier
    $where_clause = array(
      'equipment_type' => $equipment_type, 'id' => $equipment_id
    );
    $identifier_query = $DB_data->select('equipment_identifier as name')->get_where("v_tracked_equipment_basic_info",$where_clause);
    $equipment_identifier = $identifier_query && $identifier_query->num_rows() > 0 ? $identifier_query->row()->name : false;    
    
    if(!$equipment_identifier){
      return 0;
    }
    
    
    $DB_data->select(array(
      'ticket_id','equipment_type','name'
    ))->distinct()->where("ISNULL(deleted_at) AND equipment_identifier = '{$equipment_identifier}'");
    $ticket_count_query = $DB_data->get("v_ticket_equipment_associations_ext");
    $ticket_list = array();
    if($ticket_count_query && $ticket_count_query->num_rows() > 0){
      foreach($ticket_count_query->result() as $row){
        $ticket_list[$row->ticket_id] = array(
          'ticket_id' => $row->ticket_id,
          'equipment_type' => $row->equipment_type,
          'equipment_name' => $row->name
        );
      }
    }
    return $ticket_list;
  }

  public function update_equipment_info($equipment_type,$equipment_id,$new_info){
    $DB_data = $this->load->database('default',TRUE);
    $equipment_table = "tracked_equipment";
    if(!isset($new_info['name']) || !isset($new_info['description'])){
      $existing_info = $this->get_equipment_basic_details($equipment_type, $equipment_id, TRUE);
    }
    $md_table = EQUIPMENT_MD_LOOKUP;
    $location_id = isset($new_info['location']) && $new_info['location'] > 0 ? $new_info['location'] : false;
    unset($new_info['location']);
    $name = isset($new_info['name']) ? $new_info['name'] : $existing_info['name'];
    $description = isset($new_info['description']) ? $new_info['description'] : $existing_info['description'];
    if(isset($new_info['name'])) { unset($new_info['name']); }
    if(isset($new_info['description'])) { unset($new_info['description']); }
    $insert_data = array();
    $metadata_set = $new_info;
    $general_info = array('display_name' => $name, 'description' => $description);
    if($equipment_id > 0){
      //existing id, update existing entry
      $general_info['updater_id'] = $this->user_id;
      $identifier = make_equipment_identifier($equipment_type,$equipment_id);
      if(!empty($new_info)){
        $query = $DB_data->where('name',$identifier)->update($equipment_table,$general_info);
      }
    }else{
      //get the equipment_type_id
      $etid_where = array(
        'site_id' => $this->site_id,
        'type' => $equipment_type,
        'deleted_at' => NULL
      );
      $etid_query = $DB_data->get_where('tracked_equipment_types', $etid_where,1);
      $equipment_type_id = intval($etid_query->row()->id);

      //grab a new legacy id
      $legacy_where = array(
        'equipment_type' => $equipment_type_id
      );
      $legacy_query = $DB_data->select_max('legacy_id')->where($legacy_where)->get($equipment_table);
      $last_legacy_id = $legacy_query->row()->legacy_id;
      $new_legacy_id = $last_legacy_id + 1;
      $new_legacy_identifier = make_equipment_identifier($equipment_type,$new_legacy_id);
      
      //no id, create a new one
      $general_info['created_at'] = date('Y-m-d H:i:s');
      $general_info['creator_id'] = $this->user_id;
      $general_info['updater_id'] = $this->user_id;
      $general_info['equipment_type'] = $equipment_type_id;
      $general_info['legacy_id'] = $new_legacy_id;
      $general_info['name'] = $new_legacy_identifier;
      $query = $DB_data->insert($equipment_table,$general_info);
      $equipment_id = $new_legacy_id;
      
      $identifier = $new_legacy_identifier;

    }

      foreach($metadata_set as $key => $value){
        if(!empty($value)){
          $insert_data = array(
            'name' => $key, 'value' => $value, 'equipment_identifier' => $identifier,
            'created_at' => date('Y-m-d H:i:s'), 'creator_id' => $this->user_id, 'updater_id' => $this->user_id
          );
          //make sure the entry isn't already there
          $check_where_array = array(
            'name' => $key, 'equipment_identifier' => $identifier
          );
          $check_query = $DB_data->get_where($md_table,$check_where_array);
          if($check_query && $check_query->num_rows() > 0){
          $update_data = array(
            'value' => $value,
            'updater_id' => $this->user_id
          );
         
            //must be there; is it deleted?
            if($check_query->row()->deleted_at != NULL){
              //looks like it was marked as deleted, so bring it back
            $update_data['deleted_at'] = NULL;
            $update_data['deleter_id'] = NULL;
          }
              $update_query = $DB_data->where($check_where_array)->update($md_table, $update_data);
          }else{
            //must not be in the table at all, so insert it
            $insert_data = array(
              'name' => $key, 'value' => $value, 'equipment_identifier' => $identifier,
              'created_at' => NULL, 'creator_id' => $this->user_id, 'updater_id' => $this->user_id
            );
            $DB_data->insert($md_table,$insert_data);
          }
        }
      }
      
    if($location_id){
      $this->update_equipment_location($equipment_type, $equipment_id, $location_id);
    }
    $return_info = $this->get_equipment_basic_details($equipment_type, $equipment_id, TRUE);
    trigger_solr_update();
    return $return_info;
    
  }
  
    
  public function update_equipment_location($equipment_type,$equipment_id,$location_id){
    $DB_data = $this->load->database('default',TRUE);
    $table = "locations";
    $success = false;
    $locatable_types = $this->get_equipment_types($this->site_id);
    $locatable_identifier = make_equipment_identifier($equipment_type,$equipment_id);
    $new_info = array('location_id' => $location_id);
    $new_info['updater_id'] = $this->user_id;
    $new_info['locatable_type_id'] = $locatable_types[$equipment_type]['id'];
    $new_info['locatable_id'] = $equipment_id;
    $new_info['locatable_identifier'] = $locatable_identifier;
    
    $test_query = $DB_data->get_where($table,array(
      'locatable_identifier' => $locatable_identifier),1);
    if($test_query && $test_query->num_rows() > 0){
      //already exists, update
      $id = $test_query->row()->id;
      $DB_data->where('id', $id)->update($table,$new_info);
      if($DB_data->affected_rows()>0){
        $success=true;
      }
    }else{
      //doesn't exist, make it
      $new_info['created_at'] = date('Y-m-d H:i:s');
      $new_info['creator_id'] = $this->user_id;
      $DB_data->insert($table,$new_info);
      if($DB_data->affected_rows()>0){
        $success=true;
      }      
    }
    trigger_solr_update();
    return $success;
  }
  
  public function get_field_details($equipment_type){
    $details = array();
    $DB_data = $this->load->database('default',TRUE);
    $DB_data->order_by('value_ordering');
    $query = $DB_data->get_where('equipment_field_details',array('equipment_type' => $equipment_type));
    if($query && $query->num_rows()>0){
      foreach($query->result_array() as $row){
        $details[$row['field_name']] = $row;
      }
    }else{
      //try something more broad
      $DB_data->where_in('field_name',array('name','description'))->order_by('value_ordering');
      $generic_query = $DB_data->distinct()->get('equipment_field_details');
      if($generic_query && $generic_query->num_rows()>0){
        foreach($generic_query->result_array() as $row){
          $details[$row['field_name']] = $row;
        }
      }
    }
    return $details;
  }
  
  
  public function get_available_field_types(){
    $DB_data = $this->load->database('default',true);
    $fields = array();
    $DB_data->order_by('value_ordering');
    $query = $DB_data->select("field_type")->distinct()->get("equipment_field_details");
    if($query && $query->num_rows()>0){
      foreach($query->result() as $row){
        $fields[] = $row->field_type;
      }
      sort($fields);  
    }
    return $fields;
  }
  
  
  public function get_enum_values($equipment_type,$field_name){
    $DB_data = $this->load->database('default',TRUE);
    $table_name = "v_{$equipment_type}_details";
    $enums = array();
    $query = $DB_data->distinct()->select($field_name)->get($table_name);
    foreach($query->result_array() as $row){
      $enums[] = $row[$field_name];
    }
    return $enums;
  }

   
  public function get_dynamic_enum_list($table_name){
    $DB_data = $this->load->database('default',TRUE);
    $query = $DB_data->order_by('name')->get($table_name);
    $return_set = array();
    if($query && $query->num_rows() > 0){
      foreach($query->result() as $row){
        $return_set[$row->value] = $row->name;
      }
    }else{
      $return_set = false;
    }
    return $return_set;
  }

  public function add_to_enum_list($entry_type,$new_value){
    $table_name = "{$entry_type}_list";
    $ret_object = array();
    $DB_data = $this->load->database('default',TRUE);
    //first make sure it doesn't already exist
    $query = $DB_data->get_where($table_name,array("{$entry_type}_name" => $new_value));
    //echo $DB_data->last_query();
    if($query && $query->num_rows() > 0){
      //already exists, skip the rest and throw an error
      $ret_object['status'] = "Item with this name already exists";
      $ret_object['success'] = false;
      $ret_object['new_list'] = false;
      $ret_object['new_id'] = false;
    }else{
      $insert_data = array(
        "{$entry_type}_name" => $new_value,
        "creator_id" => $this->user_id,
        "updater_id" => $this->user_id,
        "created_at" => null
      );
      $DB_data->insert($table_name,$insert_data);
      //now get the new set of entries
      $ret_object['status'] = "Success";
      $ret_object['success'] = true;
      $ret_object['new_id'] = $DB_data->insert_id();
      $ret_object['new_list'] = $this->get_dynamic_enum_list("v_{$entry_type}_list");
    }
    return $ret_object;
  }

  public function get_equipment_display_name($equipment_type,$is_plural = false){
    $DB_data = $this->load->database('default',TRUE);
    $description = false;
    $query = $DB_data->select(array("type","description as display_name"))->get_where('tracked_equipment_types',array('type' => $equipment_type, 'site_id' => $this->site_id),1);
    if($query && $query->num_rows() > 0){
      $description = $query->row()->display_name;
      if($is_plural){
        $desc_array = explode(" ",$description);
        $last_word = plural(array_pop($desc_array));
        array_push($desc_array,$last_word);
        $description = implode(" ",$desc_array);
      }
    }
    return $description;
  }
  
  public function check_for_metadata_field_existence($field_name,$equipment_type,$site_id){
    $DB_data = $this->load->database('default',TRUE);
    $where_array = array(
      'site_id' => $site_id,
      'equipment_type' => $equipment_type,
      'field_name' => $field_name
    );
    $description = false;
    $query = $DB_data->select('field_name')->distinct()->where($where_array)->get('equipment_field_details',1);
    if($query && $query->num_rows() > 0){
      $description = $query->row()->field_name;
    }
    return $description;
  }
  
  public function add_metadata_field($field_name,$units,$equipment_type,$site_id){
    $DB_data = $this->load->database('default',true);
    $success = false;
    $insert_data = array(
      'field_name' => $field_name,
      'field_type' => 'text',
      'equipment_type' => $equipment_type,
      'site_id' => $site_id
    );
    if(!empty($units)){
      $insert_data['units'] = $units;
    }
    $DB_data->insert('equipment_field_details',$insert_data);
    if($DB_data->affected_rows() > 0){
      $success = true;
    }
    return $success;
  }
  
  public function change_equipment_associations($parent_identifier, $child_identifier,$action){
    $DB_data = $this->load->database('default',true);
    $te_table = "tracked_equipment_associations";
    $found_rows = array();
    $found_row_ids = array();
    $update_data = array();
    $where = "(`member_1` = '{$parent_identifier}' and `member_2` = '{$child_identifier}') or (`member_2` = '{$parent_identifier}' and `member_1` = '{$child_identifier}')";
    $query = $DB_data->where($where)->get('tracked_equipment_associations');
    if($query && $query->num_rows() > 0){
      foreach($query->result_array() as $row){
        $found_rows[$row['id']] = $row;
        $found_row_ids[] = $row['id'];
      }
    }
    switch($action){
      case 'remove':
        foreach($found_rows as $row_to_delete){
          if($row_to_delete['deleted_at'] == NULL){
            //not showing deleted, so set deleted_at and deleter_id
            $update_data = array(
              'deleted_at' => date('Y-m-d H:i:s'),
              'deleter_id' => $this->user_id
            );
            $DB_data->where('id', $row_to_delete['id'])->update($te_table,$update_data);
          }
        }
        $ret_object = array('status' => 'association deleted');
        break;
      default:
        foreach($found_rows as $row_to_add){
          // var_dump($row_to_add);
          if($row_to_add['deleted_at'] != NULL){
            //it's already here, just deleted -> restore it
            $update_data = array(
              'deleted_at' => NULL,
              'deleter_id' => NULL
            );
            $DB_data->where('id', $row_to_add['id'])->update($te_table,$update_data);
          }
        }
        $check_query = $DB_data->where('member_1', $parent_identifier)->where('member_2', $child_identifier)->where("`deleted_at` is null")->get($te_table);
        // echo $DB_data->last_query();
        if($check_query && $check_query->num_rows() > 0){
          
        }else{
          $insert_data = array(
            'member_1' => $parent_identifier,
            'member_2' => $child_identifier,
            'created_at' => NULL,
            'creator_id' => $this->user_id,
            'updater_id' => $this->user_id
          );
          $DB_data->insert($te_table,$insert_data);
        }
        
        $ret_object = $this->get_equipment_lookup_info($child_identifier);
        break;
    }
    return $ret_object;
  }

  public function get_equipment_lookup_info($equipment_identifier){
    $ret_object = array();
    $DB_data = $this->load->database('default',true);
    $query = $DB_data->get_where('v_full_equipment_list', array('id' => $equipment_identifier),1);
    if($query && $query->num_rows()>0){
      $results = $query->row();
      $ret_object = array(
        'new_equip_id' => $results->legacy_id,
        'new_equipment_type' => $results->type,
        'new_equipment_identifier' => $results->id,
        'new_equip_description' => $results->Description,
        'new_equip_name' => $results->Name
      );
    }
    return $ret_object;
  }
  

}
  
?>