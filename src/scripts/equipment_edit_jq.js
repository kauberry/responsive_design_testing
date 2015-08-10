/**
 * @author Ken J. Auberry
 */
var initial_defaults;

$(function(){
  initial_defaults = $('#edit_form').serializeFormJSON();
  hasFormChanged();
  // configure_add_new_enum_member_handlers();
  setupFormWatchers();
  $("#delete_button").click(function(){
    delete_entry();
  });
  
  
});


var hasFormChanged = function(){
  var current_values = $('#edit_form').serializeFormJSON();
  if(JSON.stringify(current_values).toLowerCase() == JSON.stringify(initial_defaults).toLowerCase()){
    $('#save_button').disable();
    $('#save_as_button').disable();
    $('#reset_button').disable();
  }else if(initial_defaults.edit_name != current_values.edit_name && equipment_id > 0){
    $('#save_button').disable();
    $('#save_as_button').enable();
    $('#reset_button').enable();
  }else{
    $('#save_button').enable();
    $('#save_as_button').disable();
    $('#reset_button').enable();    
  }
};

var setupFormWatchers = function(){
  $('#edit_form input[type="text"], #edit_form textarea').keyup(function(){
    hasFormChanged();
  });
  $('#edit_form select').change(function(){
    hasFormChanged();
  });
};

var reset_form = function(){
  var f = $('#edit_form').serializeFormJSON();
  $.each(f, function(key, value){
    if(value != initial_defaults[key]){
    // if(element.val() != initial_defaults[this.id]){
      $('#edit_form #' + key).val(initial_defaults[key]);
    }
  });
  hasFormChanged();
};

var delete_entry = function(){
  var url = base_url + 'admin/delete_equipment_item/' + equipment_type + '/' + equipment_id;
  $.get(url, function(data){
    //alert("");
    window.location = base_url + 'equipment/' + equipment_type;
  });
};

var update_entry = function(){
  var el = $(event.target);
  var f = $('#edit_form');
  var data_object = f.serializeFormJSON();
  var orig_edit_id = data_object.edit_id;
  var button_id = el.id;
  if(button_id == 'save_as_button'){
    data_object.edit_id = '0';
  }
  var checked_object = {};
  
  f.find('*').filter(':input').each(function(){
    if(this.id in initial_defaults){
      if($.trim($(this).val()) != $.trim(initial_defaults[this.id]) || data_object.edit_id == 0){
        checked_object[this.id] = $(this).val();
      }
    }
  });
  checked_object.edit_id = data_object.edit_id;
  
  var url = base_url + 'admin/update_equipment_info/' + equipment_type;
  
  var posting = $.ajax({
    url: url,
    method:'post',
    data: JSON.stringify(checked_object),
    beforeSend: function(){
      f.disable();
      $('#form_buttons input').each(function(){
        $(this).disable();
      });
    }
  });
  posting.done(function(data){
    var new_id = data.id;
    if(new_id != orig_edit_id){
      window.location = base_url + 'admin/edit_equipment/' + equipment_type + '/' + new_id;
    }
    update_defaults(data);
  });
  
};


var update_defaults = function(response_object){
  var new_key = "";
  var last_updated = response_object.last_updated;
  $('#last_updated_block').html(last_updated);
  delete response_object.last_updated;
  // response_object.each(function(){}
  $.each(response_object, function(key, value){
    new_key = "edit_" + key;
    initial_defaults[new_key] = value;
  });
  reset_form();
  
  
};
