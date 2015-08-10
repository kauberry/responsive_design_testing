
document.observe("dom:loaded", function() {
  reset_values = $('ticket_editor').serialize(true);
  $$('.form_container input[type="text"], .form_container textarea, .form_container select').each(function(s){
    if(!$('retrieved_comments')){
      s.addClassName('default');
    }
    s.observe('focus', clear_defaults.bindAsEventListener(s));
    s.observe('blur', set_defaults.bindAsEventListener(s));
    
    s.observe('keyup', check_submit_button_active.bindAsEventListener(s));
    s.observe('change', check_submit_button_active.bindAsEventListener(s));
  });
  if($('retrieved_comments')){
    update_comments();
    var c = $('comment_text');
    disable_element($('comment_post_button'));
    c.observe('keyup', check_comment_post_button_active.bindAsEventListener(c));
    c.observe('change', check_comment_post_button_active.bindAsEventListener(c));    
  }
  
  if($('time_tracking')){
    var t = $('time_tracking');
    time_reset_values = t.serialize(true);
    disable_element($('add_labor_interval'));
    default_values.time_assignment_description = t.time_assignment_description.value;
    
    $$('#time_tracking input, #time_tracking textarea, #time_tracking select').each(function(s){
      s.observe('keyup', check_time_add_button_active.bindAsEventListener(s));
      s.observe('change', check_time_add_button_active.bindAsEventListener(s));
    });

    
    var tad = $('time_assignment_description');
    tad.observe('focus', clear_defaults.bindAsEventListener(tad));
    tad.observe('blur', set_defaults.bindAsEventListener(tad));
    
    update_time_entries();
  }
    
  
  if($('ticket_update_button')){
    disable_element($('ticket_update_button'));
  }
  
  if($('priority_status_info_edit_block')){
    $$('#priority_status_info_edit_block select').each(function(s){
      s.observe('change', update_ticket_status.bindAsEventListener(s));
    });
  }
    
//  new PeriodicalExecuter(update_comments, 3);
});

var time_reset_values = {};

function update_ticket_status(s){
  var el = s.findElement();
  var f = el.up('form');
  var fields = Form.serializeElements(f.getElements('select'),true);
  
  var url = base_url + 'ajax/update_ticket_status/' + f.id.value;

  new Ajax.Request(url, {
    method: 'post',
    contentType: 'application/json',
    postBody: Object.toJSON(fields),
    onCreate: function(){
      f.disable();
    },
    onSuccess: function(transport){
      //redirect to view page
      var ticket_id = transport.responseJSON.ticket_id;
      f.enable();
      //window.location = base_url + 'ticket/' + ticket_id; 
    },
    onFailure: function(err){
      
    }
  });
  
}

function clear_defaults(s){
  var el = s.nodeName ? s : s.target;
  if(el.hasClassName('default') && el.value == default_values[el.id]){
    el.removeClassName('default');
    el.clear();
  }
}

function set_defaults(s){
  var el = s.nodeName ? s : s.target;
  if(!el.hasClassName('default') && !el.present()){
    el.value = default_values[el.id];
    el.addClassName('default');
  }
}


function submit_ticket(s){
  var f = $(s).up('form');
  var data_object = f.serialize(true);
  var checked_object = new Hash();
  
  default_values = data_object.type == 'new' ? reset_values : default_values;
  
  //get rid of fields with default content
  $H(data_object).each(function(pair) {
    if(pair.value != default_values[pair.key]){
      checked_object.set(pair.key,pair.value);
    }  
  });
  
  var ticket_id = $('ticket_editor').id.value || 0;
  var url = f.action;
  
  new Ajax.Request(url, {
    method: 'post',
    contentType: 'application/json',
    postBody: Object.toJSON(checked_object),
    onCreate: function(){
      f.disable();
    },
    onSuccess: function(transport){
      //redirect to view page
      var ticket_id = transport.responseJSON.ticket_id;
      window.location = base_url + 'ticket/' + ticket_id; 
    },
    onFailure: function(err){
      
    }
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
        setTimeout(delay, time);
    };
    return function(eventtrigger){
        ++callcount;
        delayAction(action, 200);
    };
}();

function check_submit_button_availability(){
  if(Object.toJSON($('ticket_editor').serialize(true)) == Object.toJSON(reset_values)){
    disable_element($('ticket_update_button'));
  }else{
    enable_element($('ticket_update_button'));
  }
}