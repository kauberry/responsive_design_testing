/**
 * @author Ken J. Auberry
 */
var filter_settings;

$(function(){
  if(!filter_settings){
    filter_settings = new Cookies();
  }
  checkBlockVisibility();
});

var toggleEquipmentBlock = function(state){
  var el = $(event.target);
  var block = $('#' + el.attr("id") + '_block');
  var dc = $('#' + el.attr("id") + '_dc');
  
  var cookie_handler = new Cookies();
  
  if(!state){
    state = el.hasClassName('open') ? "closed" : "open";
  }
  
  if((state == 'closed') && el.hasClassName('open')){
    block.slideUp(400, function(){
      el.removeClassName('open').addClassName('closed');
      dc.removeClassName('dc_down').addClassName('dc_up');
      cookie_handler.set('magres_' + el.closest('div.location_entry_container').attr("id").replace("/","-"),"closed");
    });
  }else if(state == 'open'){
    el.removeClassName('closed').addClassName('open');
    block.slideDown(400, function(){
      dc.removeClassName('dc_up').addClassName('dc_down');
      cookie_handler.set('magres_' + el.closest('div_location_entry_container').attr("id").replace("/","-"),"open");
    });
  }else{
    
  }
  
};

var checkBlockVisibility = function(){
  $('div.location_entry_container').each(function(){
    var el = $(event.target);
    var toggleState = filter_settings.get('magres_' + el.attr("id").replace("/","-")) || "closed";
    toggleEquipmentBlodk(el.find('h2'),toggleState);
  });
};
