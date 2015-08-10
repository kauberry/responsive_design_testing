document.observe("dom:loaded", function() {
   $$('.associate_equipment_submit_buttons input').each(function(s){
     var el = $(s);
     el.observe('click', submit_equipment_association.bindAsEventListener(el));
     disable_element(el);
   });
   $$('.equip_remove_button').each(function(t){
     var el = $(t);
     el.observe('click', remove_equipment_association.bindAsEventListener(el));
   });
   $$('.equipment_dropdown').each(function(sel){
     var el = $(sel);
     var btn = el.up('.full_width_block').down('input[type="button"]');
     var my_equipment_type = el.up('form').down('input[type="hidden"]').value;
     el.observe('change', function(){
       if(el.value != my_equipment_type + '_000'){
         enable_element(btn);
       }else{
         disable_element(btn);
       }
     });
   });
});

function submit_equipment_association(el){
  el = el.target;
  var picklist = el.up('div.full_width_block').down('select');
  var equipment_list = el.up('div.associate_equipment_block').down('div.equipment_list_holder ul');
  var my_equipment_type = el.up('form').down('input[type=hidden]').value;
  var empty_notifier = equipment_list.next();
  
  var upload_object = {
    'parent_equipment_type': equipment_type,
    'parent_equipment_id': equipment_id,
    'new equipment_type': my_equipment_type,
    'new_equipment_item': picklist.value
  };
  var url = base_url + 'admin/add_associated_equipment';
  new Ajax.Request(url, {
    method: 'post',
    contentType: 'application/json',
    postBody: Object.toJSON(upload_object),
    onSuccess: function(transport){
      var retObject = transport.responseJSON;
      
      var newEntry = Builder.node('li');
      
      //var newSpan = Builder.node('span');
      
      var removeButtonA = Builder.node('span', {
            class: 'equip_remove_button',
            id: retObject.new_equipment_identifier + '_remove_button',
            title: "Remove this association"
          });
      
      removeButtonA.update('&nbsp;');
          
      var infoLink = Builder.node('a', {
          href: base_url + 'equipment/' + my_equipment_type + '/' + retObject.new_equip_id
      }, ' ' + retObject.new_equip_name);
      
      
      // newSpan.insert(removeButtonA);
      // newEntry.insert(newSpan);
      newEntry.insert(removeButtonA);
      newEntry.insert(infoLink);
      empty_notifier.fade({duration:0.25});
      equipment_list.appear();

      equipment_list.insert(newEntry);
      var new_el = $(retObject.new_equipment_identifier + '_remove_button'); 
      new_el.observe('click', delete_equipment_association.bindAsEventListener(new_el));
      picklist.value = my_equipment_type + '_000';
      disable_element(el);
    }
  });
}

function remove_equipment_association(el){
  el = el.target;
  var equipment_list = el.up('div.equipment_list_holder ul');
  var my_equipment_type = el.up('form').down('input[type=hidden]').value;
  var my_list_item = el.up('li');
  var item_to_remove_id = el.id.replace('_remove_button','');
  var empty_notifier = equipment_list.next();  
  
  var upload_object = {
    'parent_equipment_type': equipment_type,
    'parent_equipment_id': equipment_id,
    'new equipment_type': my_equipment_type,
    'new_equipment_item': item_to_remove_id,
  };
  var url = base_url + 'admin/remove_associated_equipment';
  
  new Ajax.Request(url, {
    method: 'post',
    contentType: 'application/json',
    postBody: Object.toJSON(upload_object),
    onSuccess: function(transport){
    	$(my_list_item).fade();
      $(my_list_item).remove();
      if(equipment_list.empty()){
      	if(!empty_notifier.visible()){
	      	empty_notifier.appear({duration:0.5});
      	}
      	equipment_list.hide();
      }else{
      	if(!equipment_list.visible()){
      		equipment_list.appear({duration:0.5});
      	}
      	empty_notifier.hide();
      }
    }
  });
}




