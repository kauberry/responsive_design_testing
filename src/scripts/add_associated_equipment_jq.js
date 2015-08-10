/**
 * @author Ken J. Auberry
 */
$(function(){
  $('.associate_equipment_submit_buttons input').click(function(){
    submit_equipment_association();
  });
  $('.associate_equipment_submit_buttons input').each(function(){
    $(this).disable();
  });
  
  $('.equip_remove_button').click(function(){
    remove_equipment_association();
  });
  
  $('.equipment_dropdown').each(function(){
    var el = $(event.target);
    var btn = el.closest('.full_width_block').find('input[type="button"]');
    var my_equipment_type = el.closest('form').find('input[type="hidden"]').val();
  });
  $('.equipment_dropdown').change(function(){
    // var el = $(this);
    var btn = $(this).closest('.full_width_block').find('input[type="button"]');
    var my_equipment_type = $(this).closest('form').find('input[type="hidden"]').val();    
    if($(this).val() != my_equipment_type + '_000'){
      btn.enable();
    }else{
      btn.disable();
    }
  });
  
  $('#edit_form select').select2();
  
  $('#edit_form select').each(function(){
    var naming_element = $(this).prop('id').replace('edit_','').replace("_"," ");
    var sel2 = $(this).data("select2");
    sel2.opts.placeholder = "Choose a " + naming_element + "...";
    sel2.setPlaceholder();
  });

  
  $('.associate_equipment_block select').select2();
  
});

var configure_select_box = function(index, item){
  debugger;
  alert(index);
};

var submit_equipment_association = function(){
  var el = $(event.target);
  var picklist = el.closest('div.full_width_block').find('select');
  var equipment_list = el.closest('div.associate_equipment_block').find('div.equipment_list_holder ul');
  var my_equipment_type = el.closest('form').find('input[type="hidden"]').val();
  var empty_notifier = equipment_list.next();
  
  var upload_object = {
    'parent_equipment_type': equipment_type,
    'parent_equipment_id': equipment_id,
    'new equipment_type': my_equipment_type,
    'new_equipment_item': picklist.val()
  };
  var url = base_url + 'admin/add_associated_equipment';
  
  var posting = $.ajax({
    url:url,
    method:'post',
    data:JSON.stringify(upload_object)
  });
  posting.done(function(data){
    var new_entry = $('<li>')
      .append($('<span>',{
        class:'equip_remove_button',
        id:data.new_equipment_identifier + '_remove_button',
        title: "Remove this association" 
      })).html('&nbsp;')
      .append($('<a>', {
        href: base_url + 'equipment/' + my_equipment_type + '/' + data.new_equip_id
      })
        .append(' ' + data.new_equip_name));
    
    empty_notifier.fadeOut(250);
    equipment_list.show();
    
    equipment_list.append(new_entry);
    var new_el = $('#' + data.new_equipment_identifier + '_remove_button');
    new_el.click(function(){
      delete_equipment_association();
    });
    picklist.val(my_equipment_type + '_000');
    el.disable();
  });
};

var remove_equipment_association = function(){
  var el = $(event.target);
  var equipment_list = el.closest('div.associate_equipment_block').find('div.equipment_list_holder ul');
  var my_equipment_type = el.closest('form').find('input[type="hidden"]').val();
  var my_list_item = el.parents('li');
  var item_to_remove_id = el.attr('id').replace('_remove_button', '');
  var empty_notifier = equipment_list.closest('div.associate_equipment_block').find('.empty_set_notifier');
  
  var upload_object = {
    'parent_equipment_type': equipment_type,
    'parent_equipment_id': equipment_id,
    'new equipment_type': my_equipment_type,
    'new_equipment_item': item_to_remove_id,
  };
  var url = base_url + 'admin/remove_associated_equipment';

  var posting = $.ajax({
    url:url,
    method:'post',
    data:JSON.stringify(upload_object)
  });
  posting.done(function(){
    $(my_list_item).fadeOut();
    $(my_list_item).remove();
    if(equipment_list.empty()){
      if($('#' + empty_notifier.attr("id") + ':visible').length == 0){
        equipment_list.fadeIn(500);
      }
      empty_notifier.hide();
    }else{
      empty_notifier.show();
    }
  });
  
  
};

