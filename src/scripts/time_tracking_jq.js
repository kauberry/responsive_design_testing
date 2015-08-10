/**
 * @author Ken J. Auberry
 */
function submit_time_tracking_entry(){
  var trigger_el = $(event.target);
  var form_obj = trigger_el.closest('form');
  var data_object = {};
  var field_array = ['time_submitter_id','time_tracking_ticket_id','time_assignment_description','time_tracking_user_id','time_tracking_elapsed_min'];
  
  var form_data = form_obj.serializeFormJSON();
  
  
  $.each(field_array,function(index,value){
    data_object[value] = form_data[value];
  });
  
  // field_array.each(function(){
    // data_object[this.id] = f[this.id].val();
  // });
//   
  // var ticket_id = ticket_obj.id || 0;
  var url = form_obj.attr('action');
  
  var posting = $.ajax({
    url:url,
    type:"POST",
    data:JSON.stringify(data_object),
    beforeSend:function(){
      form_obj.disable();
    }
  });
  
  posting.done(function(data){
    update_time_entries();
    reset_time_entry_form();
    form_obj.enable();
  });
  
}


function update_time_entries(){
  // var ticket_obj = $('#ticket_editor').serializeFormJSON();
  // var ticket_id = ticket_obj.id || 0;
  var url = base_url + 'ajax/time_tracking/entries/' + ticket_id;
  
  $.get(url, function(data){
    if(data.results.length > 0){
      format_time_entries(data);
    }
  });
}

var check_time_add_button_active = function(){
  var callcount = 0;
  var action = check_time_add_button_availability();
  var delayAction = function(action, time){
    var expectcallcount = callcount;
    var delay = function(){
      if(callcount == expectcallcount){
        action();
      }
    };
  };
  return function(eventtrigger){
    ++callcount;
    delayAction(action, 200);
  };
}();

function format_time_entries(entries){
  var time_entries_div = $('<div>');
  var time_entry;
  var time_entry_header;
  
  $.each(entries.results, function(){
    time_entry = $('<div>' , {
      id: 'time_entry_' + this.entry_id,
      class: 'comment_entry_container edit_container'
    });
    
    var friendly_date = $.format.date(new Date(this.created).getTime(), 'ddd MMM d yyyy HH:mm:ss ');
    var friendly_time = friendly_time_formatter(this.time_elapsed_min);
    
    var time_selector_clone = $('#time_tracking_elapsed_min').clone();
    var user_selector_clone = $('#time_tracking_user_id').clone();
    
    time_selector_clone.attr("id", 'time_selector_' + this.entry_id);
    time_selector_clone.attr("name", 'time_selector_' + this.entry_id);
    time_selector_clone.val(this.time_elapsed_min);
    
    time_selector_clone.change(function(e){
      var ts_element = time_selector_clone;
      var user_element = user_selector_clone;
      var save_btn = ts_element
        .parents('.comment_entry_container')
        .find('.time_entry_save_button');
      if(ts_element.val() != this.time_elapsed_min || user_element.val() != this.assigned_user_id){
        save_btn.enable();
      }else{
        save_btn.disable();
      }
    });
    
    user_selector_clone.attr("id", "user_selector_" + this.entry_id);
    user_selector_clone.attr("name", "user_selector_" + this.entry_id);
    user_selector_clone.val(this.assigned_user_id);
    user_selector_clone.change(function(e){
      var ts_element = time_seletor_clone;
      var save_btn = ts_element
        .parents('.comment_entry_container')
        .find('.time_entry_save_button');
      var user_element = user_selector_clone;
      if(ts_element.val() != this.time_elapsed_min || user_element.val() != this.assigned_user_id){
        save_btn.enable();
      }else{
        save_btn.disable();
      }
    });
    
    var time_entry_header = $('<div>', {class: 'comment_header',style: 'font-size:0.85em;font-style:italic;'})
      .append('Added ' + friendly_date + ' by ' + this.submitter_name)
      .append($('<span>', { style:'float:right;', id: 'time_entry_description_' + this.entry_id,
                            class: 'time_entry_description_span' })
        .append(friendly_time + ' by ' + this.assigned_user_name)
      )
      .append($('<div>', { style:'text-align:right;display:none;', 
                           id:'time_entry_edit_block_' + this.entry_id, 
                           class: 'time_entry_edit_block_span' })
        .append($('<div>')
          .append(time_selector_clone).append(' hr by ').append(user_selector_clone)
        )
      );
      
    var description_viewer_box = $('<span>', { id:'time_description_span_' + this.entry_id,
                                               class:'time_description_span'
                                             }).append(this.description);
                                             
    var description_editor_box = $('<textarea>', { style:'display:none;width:100%',
                                                   rows:'3', 
                                                   id:'time_description_edit_' + this.entry_id,
                                                   class:'time_description_edit'
                                               }).keyup(function(){
                                                 var view = this.parents('.comment_entry_container').find('.time_description_span');
                                                 var save_btn = this.parents('.comment_entry_container').find('.time_entry_save_button');
                                                 if(this.val() != this.defaultValue){
                                                   save_btn.enable();
                                                 }else{
                                                   save_btn.disable();
                                                 }
    });
    
    var time_entry_body = $('<div>', { class:'comment_body' })
      .append(description_viewer_box)
      .append(description_editor_box);
      
    var time_entry_footer = $('<div>', { class:'comment_footer' })
      .append($('<div>', { class:'buttons time_edit_button' })
        .append($('<input>', { type:'button', value:'Change Time', class:'time_entry_edit_button',
                               id:'time_entry_edit_' + this.entry_id, name:'time_entry_edit_' + this.entry_id}))
        .append($('<input>', { type:'button', value:'Save Changes', style:'display:none;',
                               class:'time_entry_save_button disabled_button', id:'time_entry_save_' + this.entry_id,
                               name:'time_entry_save_' + this.entry_id }))
        .append($('<input>', { type:'button', value:'Cancel', style:'display:none;', class:'time_entry_cancel_button',
                               id:'time_entry_cancel_' + this.entry_id, name:'time_entry_cancel_' + this.entry_id}))
    );
    
    time_entry.append(time_entry_header).append(time_entry_body).append(time_entry_footer);
    time_entries_div.append(time_entry);
      
  });
  
  var friendly_elapsed_time = friendly_time_formatter(entries.elapsed_time);
  var summary_block = $('<div>', { class:'full_width_block elapsed_time_summary' })
    .append($('<div>', { class:'left_block', style:'width:80%;' })
      .append($('<p>', { style:'font-style:italic;text-align:left' })
        .append("Click on a time entry to edit the assigned user and/or time")
      )
    )
    .append($('<div>', { class:'right_block',style:'width:35%;' })
      .append('Overall elapsed time = ' + friendly_elapsed_time)
    );
    
  time_entries_div.append(summary_block);
  
  $('#time_tracking_summary').html(time_entries_div);
  set_defaults($('#time_assignment_description'));
  
  $('.time_entry_edit_button').click(function(e){
    make_time_entry_editable(e);
  });
  
  if($('#time_tracking_summary:hidden')){
    $('#time_tracking_summary').slideDown({duration:0.25});
  }
  
}

var make_time_entry_editable = function(s){
  // debugger;
  var el = $(s.target);
  var block = el.parents('.comment_entry_container');
  var desc = block.find('.time_entry_description_span');
  var edit = block.find('.time_entry_edit_block_span');
  
  desc.hide();
  edit.show();
  
  var edit_btn = block.find('.time_entry_edit_button');
  var save_btn = block.find('.time_entry_save_button');
  var cancel_btn = block.find('.time_entry_cancel_button');
  
  var time_desc_view = block.find('.time_description_span');
  var time_desc_edit = block.find('.time_description_edit');
  time_desc_edit.html(time_desc_view.html());
  time_desc_edit.data("defaultValue", time_desc_edit.html());
  
  // time_desc_view.fadeOut(250);
  // time_desc_edit.fadeIn(250);
  
  time_desc_view.hide();
  time_desc_edit.show();
  
  
  save_btn.click(function(e){ upload_time_entry_changes(e); });
  cancel_btn.click(function(e){ cancel_time_entry_changes(e); });
  
  // edit_btn.fadeOut(250);
  // save_btn.fadeIn(250);
  // cancel_btn.fadeIn(250);
  edit_btn.hide();
  save_btn.show();
  cancel_btn.show();
  
};

var make_time_entry_static = function(s){
  // debugger;
  var el = $(s.target);
  var time_block = el.parents('.time_edit_button');
  var edit_btn = time_block.find('.time_entry_edit_button');

  var save_btn = time_block.find('.time_entry_save_button');
  var cancel_btn = time_block.find('.time_entry_cancel_button');
  
  var block = el.parents('.comment_entry_container');
  var desc = block.find('.time_entry_description_span');
  var edit = block.find('.time_entry_edit_block_span');
  
  var time_desc_view = block.find('.time_description_span');
  var time_desc_edit = block.find('.time_description_edit');
  
  time_desc_edit.html(time_desc_edit.defaultValue);
  
  time_desc_edit.hide();
  time_desc_view.show();
  // time_desc_view.fadeIn(250);
  
  edit_btn.show();
  save_btn.hide();
  cancel_btn.hide();
  edit.hide();
  desc.show();  
  
};

var upload_time_entry_changes = function(s){
  var save_btn = $(s.target);
  var ticket_obj = serializeFormJSON();
  var ticket_id = ticket_obj.id || 0;
  var entry_container = save_btn.parents('.comment_entry_container');
  
  var url = base_url + 'ajax/time_tracking/update/' + ticket_id;
  var data_object = {};
  $.each('#' + entry_container.id + ' select', function(){
    var time_id = this.id.substring(this.id.lastIndexOf('_') + 1);
    var type = this.id.substring(0,this.id.lastIndexOf('_'));
    data_object[type] = this.value;
    data_object['time_id'] = time_id;
  });
  
  data_object['time_description'] = entry_container.find('.time_description_edit').val();
  
  var posting = $.post(url, data_object, function(){
    update_time_entries();
  });
   
};

var cancel_time_entry_changes = function(s){
  //var cancel_btn = $(s.target);
  make_time_entry_static(s);
};

function check_time_add_button_availability(){
  var tad = $('#time_assignment_description');
  if(this.time_reset_values === undefined || this.time_reset_values.length === 0){
    this.time_reset_values = $('#time_tracking').serializeFormJSON();
  }
  if(JSON.stringify($('#time_tracking').serializeFormJSON()).toLowerCase() != JSON.stringify(this.time_reset_values).toLowerCase() && tad.val().length > 0 && tad.val() != this.time_reset_values.time_assignment_description){
    $('#add_labor_interval').enable();
  }else{
    $('#add_labor_interval').disable();
  }
}

function friendly_time_formatter(time_in_min){
  time_in_min = parseInt(time_in_min,10);
  var hrs = Math.floor(time_in_min / 60);
  var min = time_in_min - (hrs * 60);
  min = min < 10 ? "0" + min : min;
  return hrs.toString() + ":" + min.toString() + " hr";
}

var reset_time_entry_form = function() {
  $('#time_assignment_description').val("");
  $('#time_tracking_user_id').select2("val",0);
  $('#time_tracking_elapsed_min').select2("val",0);
  // $.each(time_reset_values, function(key,value){
    // $('#' + key).val("");
  // });
};



//['time_submitter_id',
// 'time_tracking_ticket_id',
// 'time_assignment_description',
// 'time_tracking_user_id',
// 'time_tracking_elapsed_min'];





