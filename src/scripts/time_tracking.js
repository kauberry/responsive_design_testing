/**
 * @author d3k857
 */
function submit_time_tracking_entry(s){
  var f = $(s).up('form');
  //var data_object = f.serialize(true);
  var data_object = {};
  var field_array = ['time_submitter_id','time_tracking_ticket_id','time_assignment_description','time_tracking_user_id','time_tracking_elapsed_min'];
  
  field_array.each(function(field){
    data_object[field] = f[field].value;
  });
  var ticket_id = $('ticket_editor').id.value || 0;
  var url = f.action;
  
  new Ajax.Request(url, {
    method: 'post',
    contentType: 'application/json',
    postBody: Object.toJSON(data_object),
    onCreate: function(){
      f.disable();
    },
    onSuccess: function(transport){
      update_time_entries();
      f.enable();
    },
    onFailure: function(err){
      
    }
  });
  
}

function update_time_entries(){
  var ticket_id = $('ticket_editor').id.value || 0;
  var url = base_url + 'ajax/time_tracking/entries/' + ticket_id;
  
  new Ajax.Request(url, {
    method: 'get',
    onSuccess: function(transport){
      format_time_entries(transport.responseJSON);
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
        setTimeout(delay, time);
    };
    return function(eventtrigger){
        ++callcount;
        delayAction(action, 200);
    };
}();


function format_time_entries(entries){
  var time_entries_div = Builder.node('div');
  var time_entry;
  var time_entry_header;
  
  entries.results.each(function(s){
    time_entry = Builder.node('div', {
      id: 'time_entry_' + s.entry_id,
      className: 'comment_entry_container edit_container'
    });
    
    //friendly_time = $('time_tracking_elapsed_min').clone();
    
    var friendly_date = Date.parse(s.created);
    var friendly_time = friendly_time_formatter(s.time_elapsed_min);
    
    var time_selector_clone = $('time_tracking_elapsed_min').clone(true);
    var user_selector_clone = $('time_tracking_user_id').clone(true);
    
    time_selector_clone.id = 'time_selector_' + s.entry_id;
    time_selector_clone.name = 'time_selector_' + s.entry_id;
    time_selector_clone.value = s.time_elapsed_min;
    time_selector_clone.observe('change', function(e){
      var ts_element = time_selector_clone;
      var user_element = user_selector_clone;
      var save_btn = ts_element.up('.comment_entry_container').down('.time_entry_save_button');
      if(ts_element.value != s.time_elapsed_min || user_element.value != s.assigned_user_id){
        enable_element(save_btn);
      }else{
        disable_element(save_btn);
      }
    });

    
    user_selector_clone.id = 'user_selector_' + s.entry_id;
    user_selector_clone.name = 'user_selector_' + s.entry_id;
    user_selector_clone.value = s.assigned_user_id;
    user_selector_clone.observe('change', function(e){
      var ts_element = time_selector_clone;
      var save_btn = ts_element.up('.comment_entry_container').down('.time_entry_save_button');
      var user_element = user_selector_clone;
      if(ts_element.value != s.time_elapsed_min || user_element.value != s.assigned_user_id){
        enable_element(save_btn);
      }else{
        disable_element(save_btn);
      }
    });
 
    
    
    var time_entry_header = Builder.node('div', {
      className: 'comment_header',
      style: 'font-size:0.85em;font-style:italic;'
    },[
        friendly_date + ' by ' + s.submitter_name,
        Builder.node('span', {
          style: 'float:right;',
          id: 'time_entry_description_' + s.entry_id,
          className: 'time_entry_description_span'
        },friendly_time + ' by ' + s.assigned_user_name),
        Builder.node('div', {
        	style: 'text-align:right;display:none;',
        	id: 'time_entry_edit_block_' + s.entry_id,
        	className: 'time_entry_edit_block_span'
        }, Builder.node('div', {}, [
         [
          time_selector_clone,
          ' hr by ',
          user_selector_clone
        ]
        ]))
      ]
    );
    
    description_viewer_box = Builder.node('span', {id:'time_description_span_' + s.entry_id, className:'time_description_span'}, s.description);
    
    description_editor_box = Builder.node('textarea', {style:'display:none;width:100%;',rows:'3',id:'time_description_edit_' + s.entry_id, className:'time_description_edit'});
    description_editor_box.observe('keyup', function(e){
      var el = e.target;
      var view = el.up('.comment_entry_container').down('.time_description_span');
      var save_btn = el.up('.comment_entry_container').down('.time_entry_save_button');
      if(el.getValue() != el.defaultValue){
        enable_element(save_btn);
      }else{
        disable_element(save_btn);
      }
    });
    
    var time_entry_body = Builder.node('div', {
      className: 'comment_body'
    }, [
      description_viewer_box,
      description_editor_box
    ]);
    
    var time_entry_footer = Builder.node('div', {
      className: 'comment_footer'
    }, 
			Builder.node('div', {
				className: 'buttons time_edit_button'
			}, [
				Builder.node('input', {
					type: 'button',
					value: 'Change Time',
					// style: 'display:none;',
					className: 'time_entry_edit_button',
					id: 'time_entry_edit_' + s.entry_id,
					name: 'time_entry_edit_' + s.entry_id
				}),
				Builder.node('input', {
					type: 'button',
					value: 'Save Changes',
					style: 'display:none;',
					className: 'time_entry_save_button disabled_button',
					id: 'time_entry_save_' + s.entry_id,
					name: 'time_entry_save' + s.entry_id,
					disabled: 'disabled'
				}),
				Builder.node('input', {
					type: 'button',
					value: 'Cancel',
					style: 'display:none;',
					className: 'time_entry_cancel_button',
					id: 'time_entry_cancel_' + s.entry_id,
					name: 'time_entry_cancel_' + s.entry_id
				})
			]
			)
    );
    
    
    
    time_entry.appendChild(time_entry_header);
    time_entry.appendChild(time_entry_body);
    time_entry.appendChild(time_entry_footer);
    time_entries_div.appendChild(time_entry);
    
  });
  
  var friendly_elapsed_time = friendly_time_formatter(entries.elapsed_time);
  
  var summary_block = Builder.node('div', {
    className: 'full_width_block elapsed_time_summary'
  }, [
    Builder.node('div', {className:'left_block',style:'width:60%;'}, Builder.node('p',{style:'font-style:italic;text-align:left;'}, "Click on a time entry to edit the assigned user and/or time")),
    Builder.node('div', {className:'right_block',style:'width:35%'}, 'Overall elapsed time = ' + friendly_elapsed_time)
  ]);
  
  time_entries_div.appendChild(summary_block);
  
  $('time_tracking_summary').update(time_entries_div);
  set_defaults($('time_assignment_description'));
  
  // $$('#time_tracking_summary .comment_entry_container').each(function(s){
  	// s.observe('click', make_entry_edit_button_visible.bindAsEventListener(s));
  	// s.observe('blur', make_entry_edit_button_invisible.bindAsEventListener(s));
  // });
  
  $$('.time_entry_edit_button').each(function(s){
  	s.observe('click', make_time_entry_editable.bindAsEventListener(s));
  });
  
  if(!$('time_tracking_summary').visible()){
    $('time_tracking_summary').slideDown({duration:0.25});
  }
  
}

// var make_entry_edit_button_visible = function(id){
	// var el = id.findElement();
	// if(!el.hasClassName('comment_entry_container')){
		// el = el.up('.comment_entry_container');
	// }
	// var save_btn = el.down('.time_entry_save_button');
	// if(!save_btn.visible()){
		// el.down('input.time_entry_edit_button').appear({duration:0.5});
	// }
// };
// 
// var make_entry_edit_button_invisible = function(id){
	// var el = id.findElement();
	// if(!el.hasClassName('comment_entry_container')){
		// el = el.up('.comment_entry_container');
	// }	
	// el.down('input.time_entry_edit_button').fade({duration:0.5});
// };

var make_time_entry_editable = function(id){
	var el = id.findElement();
	var block = el.up('.comment_entry_container');
	var desc = block.down('.time_entry_description_span');
	var edit = block.down('.time_entry_edit_block_span');
	
	desc.fade({duration: 0.25});
	Effect.SlideDown(edit,{duration:0.5});
	
	var edit_btn = block.down('.time_entry_edit_button');
	var save_btn = block.down('.time_entry_save_button');
	var cancel_btn = block.down('.time_entry_cancel_button');
	
	var time_desc_view = block.down('.time_description_span');
	var time_desc_edit = block.down('.time_description_edit');
	
	time_desc_edit.textContent = time_desc_view.textContent;
	time_desc_view.fade({duration:0.25});
	time_desc_edit.appear({duration:0.25});
	
	save_btn.observe('click', upload_time_entry_changes.bindAsEventListener(save_btn));
	cancel_btn.observe('click', cancel_time_entry_changes.bindAsEventListener(cancel_btn));
	
  // new Effect.Parallel([
    // new Effect.Move('edit_btn', { sync: true, x: -100, y: 0, mode:'relative'}),
    // new Effect.Opacity('edit_btn', { sync: true, from: 1, to: 0})
  // ], { duration: 0.5 });
  edit_btn.fade({duration:0.25});
	save_btn.appear({duration:0.25});
	cancel_btn.appear({duration:0.25});
	
};

var make_time_entry_static = function(el){
	var save_btn = el.up('.time_edit_button').down('.time_entry_save_button');
	var edit_btn = el.up('.time_edit_button').down('.time_entry_edit_button');
	var cancel_btn = el.up('.time_edit_button').down('.time_entry_cancel_button');
	cancel_btn.fade({duration:0.25});
	save_btn.fade({duration:0.25});
	
	var block = el.up('.comment_entry_container');
	var edit = block.down('.time_entry_edit_block_span');
	var desc = block.down('.time_entry_description_span');
	
  var time_desc_view = block.down('.time_description_span');
  var time_desc_edit = block.down('.time_description_edit');
  
  time_desc_edit.value = time_desc_edit.defaultValue;

  //time_desc_view.textContent = time_desc_edit.textContent;
  time_desc_edit.fade({duration:0.25});
  time_desc_view.appear({duration:0.25});

	
	Effect.SlideUp(edit,{duration:0.25});
	desc.appear({duration:0.5});
	edit_btn.appear({duration:0.25});
};

var upload_time_entry_changes = function(id){
	var save_btn = id.findElement();
  var ticket_id = $('ticket_editor').id.value || 0;
  var entry_container = save_btn.up('.comment_entry_container');
  

  var url = base_url + 'ajax/time_tracking/update/' + ticket_id;
  var selectors = $$('#' + entry_container.id + ' select');
  var data_object = {};
  selectors.each(function(el){
    var time_id = el.id.substring(el.id.lastIndexOf('_') + 1);
    var type = el.id.substring(0,el.id.lastIndexOf('_'));
    data_object[type] = el.value;
    data_object['time_id'] = time_id;
  });
  
  data_object['time_description'] = entry_container.down('.time_description_edit').getValue();
  
  

  new Ajax.Request(url, {
    method: 'post',
    contentType: 'application/json',
    postBody: Object.toJSON(data_object),
    onSuccess: function(transport){
      //update_time_entries();
      //f.enable();
      // var static_block = entry_container.down('.time_entry_description_span');
      // static_block.update(transport.responseJSON.new_description);
      // make_time_entry_static(save_btn);
      update_time_entries();
    },
    onFailure: function(err){
      
    }
  });
};

var cancel_time_entry_changes = function(id){
	var cancel_btn = id.findElement();
	make_time_entry_static(cancel_btn);	
};

function check_time_add_button_availability(){
  var tad = $('time_assignment_description');
  if(Object.toJSON($('time_tracking').serialize(true)) != Object.toJSON(time_reset_values) && tad.present() && tad.value != time_reset_values.time_assignment_description){
    enable_element($('add_labor_interval'));
  }else{
    disable_element($('add_labor_interval'));
  }
}

function friendly_time_formatter(time_in_min){
  time_in_min = parseInt(time_in_min,10);
  var hrs = Math.floor(time_in_min / 60);
  var min = time_in_min - (hrs * 60);
  min = min < 10 ? "0" + min : min;
  return hrs.toString() + ":" + min.toString() + " hr";
}
