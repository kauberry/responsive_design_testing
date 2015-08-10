/**
 * @author Ken J. Auberry
 */

$(function(){
  $('#ticket_filter input').click(function(){ update_filters(this); });
  $('.more_button').click(function(){ expand_text_block(); });
  $('.location_header').click(function(){ toggleTicketBlock($(this).closest('div')); });
  checkTicketVisibility();
});

var first_load = true;

var expand_text_block = function(){
  var totalHeight = 0;
  var el = $(event.target);
  var p = el.closest('p');
  var up = p.closest('.ticket_body_summary');
  var ps = up.find('p.ticket_info');
  
  ps.each(function(){
    totalHeight += $(this).height();
  });
  
  up.height(totalHeight).removeClass('truncated_text');
  
  p.fadeOut(250);
  
  return false;
  
};

var update_filters = function(el){
  el = $(el);
  var checked = el.prop("checked");
  var type = el.closest('fieldset').attr("id").replace('by_','');
  var identifier = 'magres_ticket_filters_' + type + '_' + el.attr("id");
  
  checkTicketVisibility();
  $.cookie(identifier,checked ? "1" : "0", 7);
};




var toggleTicketBlock = function(obj, state){
  var header = $(obj);
  var header_label = header.find('h2');
  var block = header_label.siblings('.ticket_entries');
  var dc = header_label.find('.disclosure_button');
  
  if(!state){
    state = header_label.hasClass('open') ? 'closed' : 'open';
  }
  
  if(state == 'closed' && header_label.hasClass('open')){
    block.slideUp(250, function(){
      header_label.removeClass('open').addClass('closed');
      dc.removeClass('dc_down').addClass('dc_up');
    });
    $.cookie('magres_' + header_label.attr("id").replace("/","-"),"closed");
  }else if(state == 'open'){
    header_label.removeClass('closed').addClass('open');
    block.slideDown(250, function(){
      dc.removeClass('dc_up').addClass('dc_down');
      $.cookie('magres_' + header_label.attr("id").replace("/","-"),"open");
    });
  }else{
    
  }
  
};


var checkTicketVisibility = function(){
  var status = "";
  var priority = "";
  var current_filter_settings = {
    'priority':{},
    'status':{}
  };
  var ticket_counts = {};
  var all_tix_active = false;
  
  if($('#by_priority').length == 0 || $('#by_status').length == 0){
    all_tix_active = true;
  }else{
    $('#by_priority_list input').each(function(){
      current_filter_settings.priority[this.id] = $(this).prop("checked");
    });
    $('#by_status_list input').each(function(){
      current_filter_settings.status[this.id]  = $(this).prop("checked");
    });
  }

  
  
  var my_tix_container;
  $('div.ticket_entry_container').each(function(){
    status = $.grep($(this).find('span.state').attr("class").split(' '), function(n,i){ return n != 'state';})[0].replace('state_','').toLowerCase();
    priority = $.grep($(this).find('span.priority').attr("class").split(' '), function(n,i){ return n != 'priority';})[0].replace('priority_','').toLowerCase();
    my_tix_container = $(this).closest('.tix_container');
    
    if(ticket_counts[my_tix_container.attr("id")] == undefined){ 
      ticket_counts[my_tix_container.attr("id")] = {'priority':{},'status':{}};
    }
    
    if(ticket_counts[my_tix_container.attr("id")]['priority'][priority] == undefined){
      ticket_counts[my_tix_container.attr("id")]['priority'][priority] = {'visible':0,'hidden':0};
    }
    if(ticket_counts[my_tix_container.attr("id")]['status'][status] == undefined){
      ticket_counts[my_tix_container.attr("id")]['status'][status] = {'visible':0,'hidden':0};
    }
    if((current_filter_settings.status[status] && current_filter_settings.priority[priority]) || all_tix_active ){ //both must be set to be shown
      $(this).show();
      ticket_counts[my_tix_container.attr("id")]['priority'][priority]['visible'] += 1;
      ticket_counts[my_tix_container.attr("id")]['status'][status]['visible'] += 1;
    }else{
      $(this).hide();
      ticket_counts[my_tix_container.attr("id")]['priority'][priority]['hidden'] += 1;
      ticket_counts[my_tix_container.attr("id")]['status'][status]['hidden'] += 1;      
    }
  });
  var ticket_partitioning = {};
  
  var summary = {};  
    
  $('div.tix_container').each(function(){
    var el = $(this);
    
    var counter_object;
    $.each(ticket_counts[el.attr("id")],function(type, names){
      if(ticket_partitioning[el.attr("id")] == undefined){
        ticket_partitioning[el.attr("id")] = {'visible':0,'hidden':0};
      }
      if(summary[type] == undefined){
        summary[type] = {};
      }
      $.each(names, function(name, states){
        if(summary[type][name] == undefined){
          summary[type][name] = {'visible':0,'hidden':0};
        }
        ticket_partitioning[el.attr("id")]['visible'] += parseInt(states.visible,10);
        ticket_partitioning[el.attr("id")]['hidden'] += parseInt(states.hidden,10);
        summary[type][name]['visible'] += parseInt(states.visible,10);
        summary[type][name]['hidden'] += parseInt(states.hidden,10);
      });
    });
    
    
    var visible_tix_count = parseInt(ticket_partitioning[el.attr("id")]['visible'],10)/2;
    var hidden_tix_count = parseInt(ticket_partitioning[el.attr("id")]['hidden'],10)/2;
    if(visible_tix_count > 0){
      el.show();
    }else{
      el.hide();
    }
    var vis_tix_container = el.find('.visible_tix_count');
    current_count = parseInt(vis_tix_container.html(),10);
    vis_tix_container.html(visible_tix_count);
    if(current_count != visible_tix_count && !first_load){
      if(el.is(":visible")){
        el.find('h2').effect('highlight');
      }
    }else if(first_load){
      var toggleState = $.cookie('magres_' + el.find('h2').attr("id").replace("/","-")) || "closed";
      toggleTicketBlock(el,toggleState);
    }
  });
  
  $.each(summary, function(type, names){
    $.each(names, function(name, states){
      visible_counter_object = $('#' + type + '_' + name + '_visible_count');
      hidden_counter_object = $('#' + type + '_' + name + '_hidden_count');
      hidden_indicator = $('#' + type + '_' + name + '_hidden_indicator');
      visible_counter_object.html(states.visible);
      hidden_counter_object.html(states.hidden);
      if(states.hidden > 0){
        hidden_indicator.show();
      }else{
        hidden_indicator.hide();
      }
    });
  });
  
  
};








