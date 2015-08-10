var initial_defaults;

document.observe("dom:loaded", function() {
	initial_defaults = $('edit_form').serialize(true);
	hasFormChanged();
	// configure_add_new_enum_member_handlers();
	setupFormWatchers();
});

function hasFormChanged(){
	var current_values = $('edit_form').serialize(true);
	if(Object.toJSON(current_values) == Object.toJSON(initial_defaults)){
		disable_element($('save_button'));
		disable_element($('save_as_button'));
		disable_element($('reset_button'));
	}else if(initial_defaults.edit_name != current_values.edit_name && equipment_id > 0){ //name change, so new entry
		enable_element($('save_as_button'));
		disable_element($('save_button'));
		enable_element($('reset_button'));
	}else{
		enable_element($('save_button'));
		disable_element($('save_as_button'));
		enable_element($('reset_button'));		
	}
}

function setupFormWatchers(){
	var text_boxes = $$('#edit_form input[type="text"]');
	var textarea_boxes = $$('#edit_form textarea');
	var drop_downs = $$('#edit_form select');
	var field;
	text_boxes.each(function(s){
		s.observe('keyup', hasFormChanged.bindAsEventListener(s));
	});
	textarea_boxes.each(function(s){
		s.observe('keyup', hasFormChanged.bindAsEventListener(s));
	});
	drop_downs.each(function(s){
		s.observe('change', hasFormChanged.bindAsEventListener(s));
	});
	
	// $('edit_name').observe('blur', function(s){
		// el = s.target;
		// if(el.value != initial_defaults.edit_name){
			// $('edit_id').value = "0";
		// }
	// });
	
	// $$('input.ok_button').each(function(el){
		// el.observe('click', addNewEnumEntry.bindAsEventListener(el));
	// });
	
	//$('reset_button').observe('click', reset_form.bindAsEventListener(s));
	//$('save_button').observe('click', update_entry.bindAsEventListener(s));
	//$('save_as_button').observe('click', create_entry.bindAsEventListener(s));
	
}

function configure_add_new_enum_member_handlers(){
	$$('.dynamic_dropdown').each(function(s){
		var box_id = s.id;
		var box_type = box_id.replace("edit_","");
		s.observe('change', function(n){
			if(n.target.value == 0){
				//requesting a new enum value
				Modalbox.show($('modalbox_' + box_type), {
					title: "Add a new " + box_type,
					width: 450, afterLoad: setObservers, 
					onHide: removeObservers
				});
			}
		});
	});
}

var hideObserver = Modalbox.hide.bindAsEventListener(Modalbox);

function setObservers(){
	//$('new_' + box_type + '_save').observe('click', hideObserver);
	// $('new_' + box_type + '_cancel').observe('click', hideObserver);
	//$('new_' + box_type + '_save').observe('click', add_new_enum);
}

function removeObservers(){
	// $('new_' + box_type + '_save').stopObserving('click', hideObserver);
	// $('new_' + box_type + '_cancel').stopObserving('click', hideObserver);
	//$('new_' + box_type + '_save').stopObserving('click', add_new_enum);
	//add_new_enum(box_type);
}

function add_new_enum(){
	var my_form = this.up(2).down('form');
	var newValues = my_form.serialize(true);
	var box_type = newValues.box_type;
	var newValue = newValues['new_' + box_type + '_entry'];
	delete(newValues.box_type);
	if(isBlank(newValue)){
		return false;
	}
	//ok, we've got a real value (theoretically), send it upstream
	var url = base_url + 'admin/add_enum_entry/' + box_type;
	new Ajax.Request(url, {
		method:'post',
		contentType: 'application/json',
		postBody: Object.toJSON(newValues),
		onCreate: function(){
			
		},
		onSuccess: function(transport){
			//call should return a new list for the appropriate control
			Element.replace('edit_' + box_type, transport.responseText);
			setupFormWatchers();
		},
		onError: function(err){
			alert(err);
		},
		onComplete: function(){
			Modalbox.hide();
		}
	});
}

function reset_enum(){
	var my_form = this.up(2).down('form');
	var newValues = my_form.serialize(true);
	var box_type = newValues.box_type;
	$('edit_' + box_type).value = initial_defaults['edit_' + box_type];
}

function reset_form(){
	var current_field_id = "";
	$H(initial_defaults).each(function(item){
		$(item.key).value = item.value;
	});
	hasFormChanged();
}

function update_entry(button_id){
	var f = $('edit_form');
	var data_object = f.serialize(true);
	var orig_edit_id = data_object.edit_id;
  if(button_id == 'save_as_button'){
    data_object.edit_id = '0';
  }
	var checked_object = new Hash();
	$H(data_object).each(function(item){
		if(item.value != initial_defaults[item.key] || data_object.edit_id == 0){
			checked_object.set(item.key,item.value);
		}
	});
	checked_object.set('edit_id',data_object.edit_id);
	
	var url = base_url + 'admin/update_equipment_info/' + equipment_type;
	
	new Ajax.Request(url, {
		method: 'post',
		contentType: 'application/json',
		postBody: Object.toJSON(checked_object),
		onCreate: function(){
			f.disable();
			$$('#form_buttons input').each(function(s){
				disable_element(s);
			});
		},
		onSuccess: function(transport){
			var new_id = transport.responseJSON.id;
			if(new_id != orig_edit_id){
  			window.location = base_url + 'admin/edit_equipment/' + equipment_type + '/' + new_id;
			}
			f.enable();
			update_defaults(transport.responseJSON);
		},
		onFailure: function(err){
			
		}
	});
	
}

function update_defaults(response_object){
  var new_key = "";
  var last_updated = response_object.last_updated;
  $('last_updated_block').update(last_updated);
  delete response_object.last_updated;
  $H(response_object).each(function(pair){
    new_key = "edit_" + pair.key;
    initial_defaults[new_key] = pair.value;
  });
  reset_form();
}

function create_entry(){
	
}
