/**
 * @author d3k857
 */
document.observe("dom:loaded", function() {
  setup_listeners();
});

var setup_listeners = function(){
  $$('.ticket_entries input[type="checkbox"]').each(function(s){
    s.observe('change', update_staff_status.bindAsEventListener(s));
  });
  
  var sb = $('name_list_search');
  new Form.Element.DelayedObserver(sb, 0.5, function() {
    var filter = sb.value;
    new Ajax.Updater('user_list_holder', base_url + '/admin/refresh_user_list/' + filter);
  });
  
};

var update_staff_status = function(id){
  var el = id.findElement();
  var url = base_url + "ajax/update_staff_status/";
  var val = {
    user_id: el.id,
    is_staff : el.checked
  };
  new Ajax.Request(url, {
    method: 'POST',
    postBody: Object.toJSON(val),
    onCreate: function(){
      var parent_el = el.up('.ticket_entry_container');
      new Effect.Opacity(parent_el.id, { from: 1.0, to: 0.3, duration: 0.5 });
    },
    onSuccess: function(transport){
      new Ajax.Updater('user_list_holder',base_url + 'admin/refresh_user_list', {
        onComplete: function(){
          setup_listeners();
        }
      });
    }
  });
};


