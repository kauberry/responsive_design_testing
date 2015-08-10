/**
 * @author Ken J. Auberry
 */
var time_reset_values = {};
var reset_values = {};
var first_load = true;

$(function(){
  reset_values = $('#ticket_editor').serializeFormJSON();
  $('.form_container input[type="text"], .form_container textarea, .form_container select').each(function(){
    // if(!$('#retrieved_comments')){
    if($(this).val() == default_values[$(this).attr('id')]){
      $(this).addClass('default');
    }
    // }
    $(this).focus(function(){
      clear_defaults($(this));
    });
    $(this).blur(function(){
      set_defaults($(this));
    });
    $(this).keyup(function(){
      check_submit_button_active();
    });
    $(this).change(function(){
      check_submit_button_active();
    });
  });
  $('#ticket_update_button').click(function(){
    submit_ticket($(this));
  });
  if($('#retrieved_comments').length > 0){
    update_comments();
    var c = $('#comment_text');
    $('#comment_post_button').disable();
    c.keyup(function(){ check_comment_post_button_active(); });
    c.change(function(){ check_comment_post_button_active(); });
  }
  
  $('#priority').select2({
    placeholder: "Choose a Priority Level"
  });
  $('#status').select2({
    placeholder: "Choose a Ticket Status"
  });
  
  $('#assigned_user_id').select2({
    placeholder: "Assign a staff member for this ticket"
  });
  
  $('#time_tracking_user_id').select2({
    placeholder: "Select a Staff Member"
  });
  $('#time_tracking_elapsed_min').select2();
  
  if($('#equipment_edit_block select')){
    $('#equipment_edit_block select').select2();
  }
  
  
  
  
  if($('#time_tracking').length >0){
    var t = $('#time_tracking').serializeFormJSON();
    time_reset_values = t;
    $('#add_labor_interval').disable();
    default_values.time_assignment_description = t.time_assignment_description;
    
    var input_set = $('#time_tracking_block').find('*').filter('input,textarea');
    
    input_set.keyup(function(){ check_time_add_button_availability(); });
    input_set.change(function(){ check_time_add_button_availability(); });
    
    // $('#time_tracking input, #time_tracking textarea, #time_tracking select').each(function(){
      // $(this).keyup(function(){ check_time_add_button_active(); });
      // $(this).change(function(){ check_time_add_button_active(); });
    // });
    
    var tad = $('#time_assignment_description');
    tad.focus(function(){ clear_defaults($(this)); });
    tad.blur(function(){ set_defaults($(this)); });
    
    update_time_entries();
  }
  
  if($('#ticket_update_button')){ $('#ticket_update_button').disable(); }
  
  check_submit_button_availability();
  
  
  if($('#priority_status_info_edit_block')){
    $('#priority_status_info_edit_block select').each(function(){
      $(this).change(function(){ update_ticket_status(); });
    });
  }
  first_load = false;
});




function update_ticket_status(){
  var f = $('#ticket_editor');
  var fields = f.find('select, input[type="hidden"]').serializeFormJSON();
  
  var url = base_url + 'ajax/update_ticket_status/' + fields['id'];
  
  var posting = $.ajax({
    method:'post',
    url: url,
    data:JSON.stringify(fields),
    contentType:'application/json',
    beforeSend:function(){
     f.disable(); 
    }
  });
  posting.done(function(data){
    var ticket_id = data.ticket_id;
    f.enable();
  });
}

function clear_defaults(el){
  if(el.hasClass('default') && el.val() == default_values[el.attr("id")]){
    el.removeClass('default');
    el.val('');
  }
}

function set_defaults(el){
  if(el.length == 0){
    return;
  }
  if(!el.hasClass('default') && el.val().length == 0){
    el.val(default_values[el.attr("id")]);
    el.addClass('default');
  }
}

function submit_ticket(el){
  var f = el.closest('form');
  var data_object = f.serializeFormJSON();
  var checked_object = {};
  
  if(data_object.type == 'new'){
    var new_default_values = $.extend({},default_values,reset_values);
    $.each(data_object,function(index,element){
      if(data_object[index] != new_default_values[index] && /w+_(0{4})/.exec(element) == null){
        checked_object[index] = element;
      }
    });
  }else{
    $.each(data_object,function(index,element){
      if(data_object[index] != default_values[index] && /w+_(0{4})/.exec(element) == null){
        checked_object[index] = element;
      }
    });  
}
  
  var ticket_id = data_object.id || 0;
  var url = f.attr('action');
  
  var posting = $.ajax({
    url: url,
    method:'post',
    data:JSON.stringify(checked_object),
    contentType:'application/json',
    beforeSend: function(){
      f.disable();
    },
  });
  
  posting.done(function(data){
    var ticket_id = data.ticket_id;
    window.location = base_url + 'ticket/' + ticket_id;
  });
  
  
}

var check_submit_button_active = function(){
  var callcount = 0;
  var action = check_submit_button_availability;
  var delayAction = function(action, time){
    var expectcallcount = callcount;
    var delay = function(){
      if(callcount == expectcallcount){
        action();
      }
    };
    setTimeout(delay,time);
  };
  return function(eventtrigger){
    ++callcount;
    delayAction(action,200);
  };
}();

function check_submit_button_availability(){
  var cloned_reset = {};
  $.extend(cloned_reset,reset_values);
  var field_values = $('#ticket_editor input[type="text"], #ticket_editor textarea, #ticket_editor select, #priority').serializeFormJSON();
  if(first_load && ticket_id == 0){
    if($(field_values).objectIsEqual(default_values)){
      $('#ticket_update_button').disable();
    }else{
      $('#ticket_update_button').enable();
    }
  }else{
    if($(field_values).objectIsEqual(default_values) || $(field_values).objectIsEqual(cloned_reset)){
      $('#ticket_update_button').disable();
    }else{
      $('#ticket_update_button').enable();
    }
  }
}

var reset_to_default_values = function(){
  $.each(default_values, function(key,value){
    $('#' + key).addClass('default').val(value);
  });
};


$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};



