/**
 * @author Ken J. Auberry
 */
$(function(){
  setup_listeners();
});

var setup_listeners = function(){
  $('.ticket_entries input[type="checkbox"]').change(function(){
    update_staff_status();
  });
  
  var sb = $('#name_list_search');
  sb.delayedObserver(function(value, object){
    var filter = sb.val();
    $.get(base_url + 'admin/refresh_user_list/' + filter, function(data){
      $('#user_list_holder').html(data);
    });
  },0.25);
  
};

var update_staff_status = function(){
  var el = $(event.target);
  var url = base_url + 'ajax/update_staff_status/';
  var val = {
    user_id: el.attr("id"),
    is_staff: el.is(":checked")
  };
  var posting = $.ajax({
    type:'POST',
    url:url,
    data:JSON.stringify(val),
    beforeSend: function(){
      el.closest('.ticket_entry_container').fadeTo(0.5,0.3);
    }
  });
  posting.done(function(){
    $.get(base_url + 'admin/refresh_user_list/', function(data){
      $('#user_list_holder').html(data);
      setup_listeners();
    });
  });
};
