<?php
require_once('baseline_controller.php');

class Admin extends Baseline_controller {

  function __construct() {
    parent::__construct();
    if($this->admin_access_level < 400){
      $this->page_data['message'] = "You must have at least 'Power User' status to use these pages";
      $this->load->view('insufficient_privileges', $this->page_data);
    }
    $this->load->helper(array('inflector','url','opwhse_search','form','network','edit_equipment'));
    $this->load->library(array('table'));
    $this->load->model('User_operations_model','user_model');
    $this->load->model('Inventory_model','inv_model');
  }
  
  public function index(){
  }
  
  
  /* User management */
  
  public function update_users(){
    $DB_data = $this->load->database('default', TRUE);
    $query = $DB_data->select('network_id')->get('user_cache');
    foreach($query->result() as $row){
      $network_id = $row->network_id;
      $this->user_model->refresh_user_info($network_id,$this->site_id);
    }
  }
  
  public function users($name_filter = FALSE){
    $is_admin = $this->admin_access_level >= 400 ? true : false;
    
    if(!$is_admin){
      $message = "<p>Ticket contents can only be altered by the ticket 
      owner or someone with administrator privileges.</p>";
      show_error($message,401);
    }
    
    $this->page_data['user_list'] = $this->user_model->get_current_user_list($name_filter);
    $this->page_data['css_uris'] = array('/stylesheets/tickets.css');
    $this->page_data['script_uris'] = array('/scripts/user_list_jq.js', '/scripts/jquery-delayedObserver.js'); 
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;    
    
    $this->load->view('user_listing_bubbles',$this->page_data);
  }
  
  public function refresh_user_list($name_filter = FALSE){
    $data['user_list'] = $this->user_model->get_current_user_list($name_filter);
    $data['search_term'] = $name_filter;
    $this->load->view('user_listing_bubble_guts',$data);
  }
  
  public function update_staff_status(){
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,true);
    if($this->user_model->change_user_staff_status($values['user_id'],$values['is_staff'])){
      transmit_array_with_json_header(array('user_id' => $values['user_id']),"User {$values['user_id']} updated successfully");
    }else{
      $this->output->set_status_header('500');
      transmit_array_with_json_header("","Error updating User {$values['user_id']}");
    }
  }
  
  
  
  
  /* equipment handling */
  
  public function add_equipment(){
    $equipment_types = $this->inv_model->get_equipment_types($this->site_id);
    $this->page_data['equipment_types'] = $equipment_types;
    $this->page_data['content'] = $this->load->view('new_equipment_type_select_block',$this->page_data,TRUE);    
    $this->load->view('equipment_list_report',$this->page_data);
  }
  
  public function delete_equipment_item($equipment_type,$equipment_id){
    $equipment_types = $this->inv_model->get_equipment_types($this->site_id);
    //get the equipment_type_id
    if(array_key_exists($equipment_type, $equipment_types)){
      $equipment_type_id = $equipment_types[$equipment_type]["id"];
    }else{
      $new_info = array(
        'last_updated' => date("D, j M Y  H:i T"),
        'success' => false ? "Item Deletion Succeeded" : "Item Deletion Failed"
      );      
      transmit_array_with_json_header($new_info);
    }
    
    $success = $this->inv_model->delete_equipment_item($equipment_type_id, $equipment_id);
    $new_info = array(
      'last_updated' => date("D, j M Y  H:i T"),
      'success' => $success ? "Item Deletion Succeeded" : "Item Deletion Failed"
    );
    transmit_array_with_json_header($new_info);
    
  }
  
  public function edit_equipment($equipment_type,$equipment_id){
    $this->load->model('Inventory_model','inv_model');
    $equipment_type = singular($equipment_type);
    $ticket_listing = $this->inv_model->get_associated_ticket_listing($equipment_type,$equipment_id);
    $ticket_count = sizeof($ticket_listing);
    if($equipment_id > 0){
      $content = $this->inv_model->get_equipment_details($equipment_type,$equipment_id,TRUE);
    }else{
      $content = array(
        'details' => array(
          'id' => 0,
          'name' => "New ".ucwords($equipment_type),
        ),
        'equipment' => array()
      );
    }
    $default_values = $content['details'];
    $field_details = $this->inv_model->get_field_details($equipment_type);
    $content['field_types'] = $this->inv_model->get_available_field_types();
    $field_entries = array();
    $modalbox_entries = array();
    $associated_equipment = $content['equipment'];
    // $associated_equipment = 
    $excluded_equipment_from_picklist = array();
    foreach($associated_equipment as $equip_type => $equip_list){
      foreach($equip_list as $equip_id => $equip_info){
        $excluded_equipment_from_picklist[] = $equip_info['equipment_identifier'];
      }
    }
    $available_equipment_picklists = $this->inv_model->get_picklists($this->site_id,false,$excluded_equipment_from_picklist);
    unset($available_equipment_picklists[$equipment_type]);
    foreach($field_details as $name => $info){
      if(!array_key_exists($name,$default_values)){
        $default_values[$name] = "";
      }
      $display_name = ucwords(humanize($name));
      $units = "";
      $id = "edit_{$name}";
      $label_text = "<label for='{$id}'>{$display_name}</label>";
      switch($info['field_type']){
        case "number":
          $units = isset($info['units']) ? " {$info['units']}" : "";
          $value = isset($default_values[$name]) ? " value='{$default_values[$name]}'" : "";
          $field_text = "<input type='text'{$value} id='edit_{$name}' name='{$id}' />";
          $entry = array('label_text' => $label_text,'field_text' => $field_text, 'units' => $units);
          break;
        case "enum":
          $options = array();
          $option_items = empty($info['static_value_list']) ? $this->inv_model->get_enum_values($equipment_type,$name) : explode(',',$info['static_value_list']);
          foreach($option_items as $item){
            $options[$item] = ucwords($item);
          } 
          $field_text = form_dropdown($id,$options, $default_values[$name], "id='{$id}' style='width:90%;'");
          $entry = array('label_text' => $label_text,'field_text' => $field_text, 'units' => $units);
          break;
        case "dynamic":
          $options = $this->inv_model->get_dynamic_enum_list($info['dynamic_value_list']);
          // array_unshift($options, "Add a new ".strtolower($label_text)."...");
          //$options[0] = "Add a new ".strtolower($label_text)."...";
          // var_dump($default_values);
          $default_value = !empty($default_values["{$name}_name"]) ? $default_values["{$name}_name"] : ""; 
          $field_text = form_dropdown($id, $options, $default_value, "id='{$id}' class='dynamic_dropdown' style='width:90%;'");
          $entry = array('label_text' => $label_text, 'field_text' => $field_text, 'units' => $units);
          // $mb_entry = generate_modalbox_element($name);          
          // $modalbox_entries[] = $mb_entry;
          break;
        case "textarea":
          $data = array(
            'name' => $id,
            'id' => $id,
            'value' => $default_values[$name],
            'rows' => 4
          );
          $field_text = form_textarea($data);
          $entry = array('label_text' => $label_text,'field_text' => $field_text, 'units' => $units);
          break;
        case "text":
        default:
          $data = array(
            'name' => $id,
            'id' => $id,
            'value' => $default_values[$name]
          );
          $field_text = form_input($data);
          $entry = array('label_text' => $label_text,'field_text' => $field_text, 'units' => $units);
          break;
      }
      $field_entries[$name] = $entry;
    }
    $this->page_data['css_uris'] = array(
      '/stylesheets/equipment_list.css',
      '/scripts/select2/select2.css'
    );
    $this->page_data['script_uris'] = array(
      '/scripts/equipment_edit_jq.js',
      '/scripts/utility_functions.js',
      '/scripts/select2/select2.min.js',
      '/scripts/add_associated_equipment_jq.js'
    );
    
    $page_header = $equipment_id > 0 ? "Edit ".ucwords($equipment_type)." Information for {$default_values['name']}" : "Add a New ".ucwords($equipment_type);
    $this->page_data['equipment_id'] = $equipment_id;
    $this->page_data['associated_equipment_picklists'] = $available_equipment_picklists;
    $this->page_data['associated_equipment'] = $associated_equipment;
    $this->page_data['equipment_type'] = $equipment_type;
    $this->page_data['field_entries'] = $field_entries;
    $this->page_data['title'] = "Edit ".ucwords($equipment_type)." Information";
    $this->page_data['page_header'] = $page_header;
    $this->page_data['content'] = $content;
    $this->page_data['load_prototype'] = false;
    $this->page_data['load_jquery'] = true;
    $this->page_data['ticket_count'] = $ticket_count;
    
    // $this->page_data['modalbox_elements_list'] = $modalbox_entries;
    $this->page_data['js'] = "var equipment_type = '{$equipment_type}'; var equipment_id = '{$equipment_id}'";
    $this->load->view('equipment_edit_view',$this->page_data);
  }
  
  
  function update_equipment_info($equipment_type){
    $this->load->model('Inventory_model','inv_model');
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,true);
    if(empty($HTTP_RAW_POST_DATA) || sizeof($values) == 0){
      show_error("No updated parameters found", 400);
      
    }
    $db_values = array();
    $db_name = "";
    //remove the "edit_" bit from the name
    foreach($values as $key => $value){
      $db_name = str_ireplace('edit_', '', $key);
      $db_values[$db_name] = $value;
    }
    $equipment_id = $db_values['id'];
    unset($db_values['id']);
    
    $new_info = $this->inv_model->update_equipment_info($equipment_type,$equipment_id,$db_values);
    $new_info['last_updated'] = date("D, j M Y  H:i T",strtotime($new_info['last_updated']));
    transmit_array_with_json_header($new_info);
  }
  
  function add_enum_entry($entry_type){
    $this->load->model('Inventory_model','inv_model');
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,true);
    $new_enum_value = $values["new_{$entry_type}_entry"];
    $ret_object = $this->inv_model->add_to_enum_list($entry_type,$new_enum_value);
    // var_dump($ret_object);
    if($ret_object['success']){
      $options = $ret_object['new_list'];
      $id = "edit_{$entry_type}";
      $options[0] = "Add a new ".strtolower($entry_type)."...";
      $field_text = form_dropdown($id, $options, $ret_object['new_id'], "id='{$id}' class='dynamic_dropdown'");
      print($field_text);
    }else{
      //$this->output->set_status_header("409","Entry already exists");
    }
  }
  
  function check_field_name_existence($equipment_type, $field_name, $local = false){
    $field_description = $this->inv_model->check_for_metadata_field_existence($field_name,$equipment_type,$this->site_id);
    $field_name_flattened = underscore(strtolower($field_name));
    $field_description_flattened = $this->inv_model->check_for_metadata_field_existence($field_name_flattened,$equipment_type,$this->site_id);
    if($field_description || $field_description_flattened){
      $response_array = array(
        'statusText' => "A field for '{$field_name}' already exists",
        'okToAdd' => false
      );
    }else{
      $response_array = array(
        'statusText' => '',
        'okToAdd' => true
      );
    }
    if($local){
      return $response_array;
    }else{    
      transmit_array_with_json_header($response_array, $response_array['statusText'], $response_array['okToAdd']);
    }
  }
  
  function add_field_entry($equipment_type){
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,TRUE);
    $new_field_name = $values['new_field_name'];
    $units = $values['field_units'];
    $equipment_id = $values['equipment_id'];
    $response = $this->check_field_name_existence($equipment_type, $new_field_name,true);
      //check flattened version
      $flattened_field_name = underscore(strtolower($new_field_name));
      $response = $this->check_field_name_existence($equipment_type, $flattened_field_name,true);
      if($response['okToAdd']){
        //clean, let's go
        $success = $this->inv_model->add_metadata_field($flattened_field_name, $units, $equipment_type, $this->site_id);
        if($success){
          //regenerate the equipment info block and return
          // $content = $this->inv_model->get_equipment_details($equipment_type,$equipment_id,TRUE);
          // $ret_value = $this->load->view('equipment_edit_block',array(
            // 'content' => $content, 
            // 'equipment_id' => $equipment_id, 
            // 'equipment_type' => $equipment_type));
          $ret_value = array('success' => $success);
          transmit_array_with_json_header($ret_value, "field added successfully", true);
        }
      }else{
      //show error
    }
  }
  
  
  function add_associated_equipment(){
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,TRUE);
    $this->_change_associated_equipment($values, 'add');
  }
  
  function remove_associated_equipment(){
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    $values = json_decode($HTTP_RAW_POST_DATA,TRUE);
    $this->_change_associated_equipment($values, 'remove');
  }
  
  function _change_associated_equipment($values, $action){
    extract($values);
    $child_identifier = $new_equipment_item;
    
    $parent_identifier = is_numeric($parent_equipment_id) ? make_equipment_identifier($parent_equipment_type,$parent_equipment_id) : $parent_equipment_id;
    $ret_object = $this->inv_model->change_equipment_associations($parent_identifier,$child_identifier,$action);
    transmit_array_with_json_header($ret_object, "field {$action}ed successfully", true);
  }
  
  
  
  
}
?>