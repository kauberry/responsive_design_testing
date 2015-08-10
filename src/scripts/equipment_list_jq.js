/**
 * @author Ken J. Auberry
 */
var filter_settings;

$(function(){
  checkBlockVisibility();
  $('.location_header').click(function(){
    toggleEquipmentBlock(this);
  });
});

var toggleEquipmentBlock = function(obj, state){
  var el = $(obj);
  var block = $('#' + el.attr("id") + '_block');
  var dc = $('#' + el.attr("id") + '_dc');
  
  if(!state){
    state = el.hasClass('open') ? "closed" : "open";
  }
  
  if((state == 'closed') && el.hasClass('open')){
    block.slideUp(400, function(){
      el.removeClass('open').addClass('closed');
      dc.removeClass('dc_down').addClass('dc_up');
      $.cookie('magres_' + el.closest('div.location_entry_container').attr("id").replace("/","-"),"closed");
    });
  }else if(state == 'open'){
    el.removeClass('closed').addClass('open');
    block.slideDown(400, function(){
      dc.removeClass('dc_up').addClass('dc_down');
      $.cookie('magres_' + el.closest('div.location_entry_container').attr("id").replace("/","-"),"open");
    });
  }else{
    
  }
  
};

var checkBlockVisibility = function(){
  $('div.location_entry_container').each(function(){
    var el = $(this);
    var toggleState = $.cookie('magres_' + el.attr("id").replace("/","-")) || "closed";
    toggleEquipmentBlock(el.find('h2'),toggleState);
  });
};
