/**
 * @author Ken J. Auberry
 */
$(function(){
  //Since JS is working, first thing to do is disable the standard form submittal stuff
  $('#search_form').removeAttr('action');
  $('#search_form').submit(function(e){
    search_submitter(this);
    return false;
  });
});

var search_submitter = function(form_obj){
  var url = base_url + "search/ajax";
  var ticket_values = $(form_obj).serializeFormJSON();
  var facet_values = $('#facet_form') ? $('#facet_form').serializeFormJSON() : {};
  $.extend(ticket_values,facet_values);
  var posting = $.ajax({
    url:url,
    type:"POST",
    data:ticket_values
  });
  
  posting.done(function(data){
    update_ticket_entries(data);
  });
  
};

var hide_more_facets = function(){
  var facet_sets = $('.facet_set .facet_items');
  if(facet_sets.length == 0){
    return;
  }
  var item_container = [];
  facet_sets.each(function(){
    item_container = $(this);
    var hidden_items_container = item_container.find('ul.see_more_items');
    var items = item_container.find('li'); 
    if(items.length >= 10){
      var visible_items = items.slice(0,9);
      var hidden_items = items.slice(10);
      hidden_items.each(function(){
        $(this).appendTo('#hidden_' + item_container.attr('id'));
      });
    }
    if(items.length > 10){
      var refinement_item = items.closest('.facet_set').find('.refinement_list');
      refinement_item.click(function(){
        show_more_entries(this);
      });
      refinement_item.show();
    }
  });
};

var update_ticket_entries = function(tickets){
  $('#results_container').html(tickets.main_results_body).fadeIn(250);
  if($('#refineSearch li').length == 0 || $('#refineSearch input:checked').length == 0){
    $('#facet_container').html($('<form id="facet_form" />'));
    $('#facet_form').html(tickets.facet_listing);
  }else{
    var facet_form = $(tickets.facet_listing);
    $('.facet_set').each(function(){
      var checked_boxes = $(this).find('input:checked');
      if(checked_boxes.length > 0){
        checked_boxes.each(function(){
          $('#facet_container #' + this.id).attr('checked',true);
        });
      }else{
        var my_facet_items = facet_form.find('#' + this.id + ' .facet_items');
        if(my_facet_items.find('li').length > 0){
          $(this).show();
          $(this).find('.facet_items').html(my_facet_items);
        }else{
          $(this).hide();
        }
      }
    });
  }
  
  $('.facet_checkbox').change(function(){
    search_submitter($('#search_form'));
  });
  hide_more_facets();
};

var show_more_entries = function(refinement_el){
  var el = $(refinement_el);
  el.fadeOut(200, function(){
    el.closest('.facet_set').find('ul.hidden_items').fadeIn(200);
  });
};
