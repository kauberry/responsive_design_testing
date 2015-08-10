/**
 * @author Ken J. Auberry
 */
$(function(){
  
  $('.container_type_dropdown')
    .select2({placeholder:'Select a Container Type...'})
    .on('change', function(evt){
      var url = base_url + 'sample_tracking_containers/load_container_details_fields/' + evt.val;
      var block = $(this);
      var details_block = block.parent().find('.container_details_block');
      if(details_block){
        $(details_block).hideUp('slow');
      }
      $.get(url, function(data){
        block.parent().find('.container_options_block').html(data);
        block.parent().find('.container_options_block .container_details_block').showDown('slow');

        block.parent().find('.container_details_block').find('input, .select2-container, textarea, select').on("blur", function(evt){
          check_container_create_active();
        });
      });
    });
  
  if($('#create_form_buttons')){
    $('#form_buttons input').each(function(){
      $(this).attr("disabled","disabled").addClass("disabled_button");
    });
  }
  
  
  
  var newUserDialogOpts = {
      modal: true,
      autoOpen: false,
      dialogClass: "dialogWithDropShadow",
      width: 750,
      show: "showDown",
      hide: "hideUp",
      draggable: true,
      resizeable: true,
      open : function (event){
          $(this).load (base_url + "sample_tracking_users/load_add_user_dialog");
      },
      buttons : [
        {
          text : "Done", 
          click : function(){
            $(this).dialog("close");
          }
        }
      ]
   };
   
   var newContainerDialogOpts = {
     modal: true,
     autoOpen: false,
     dialogClass: "dialogWithDropShadow",
     width: 750,
     show: "showDown",
     hide: "hideUp",
     draggable: true,
     resizeable: true,
     open : function (event){
        $(this).load (base_url + "sample_tracking_containers/load_add_container_dialog");
     },
     buttons : [
       {
         text : "Done", 
         click : function(){
           $(this).dialog("close");
         }
       }
     ]
  };

  
  $('#populated_user_list li').click(function(){
    user_identifier = this.id;
    load_user_details(user_identifier);
  });
  $('#populated_user_list li').mouseover(function(){
    user_identifier = this.id;
    load_user_details(user_identifier);
  });
  $("#add_user").click(function() {
    $( "#add-user-dialog-form" ).dialog( "open" );
  });
  
  $('#create_sample_container').click(function(event){
    submit_new_sample_container();
  });

  $("div#add-user-dialog-form").dialog (newUserDialogOpts);
  
  $("#add_sample_container").click(function(){
    $('#container_info_block_div').css('min-height','').css('height',$('#sample_containers').height());
    $('#no_sample_containers_notification').hideUp();
    $('#sample_containers').fadeIn('slow', function(){
      $('#container_info_block_div').css('min-height',$('#container_info_block_div').height()).css('height','');
    });
  });

  $('#sample_info_form input[type="text"]:visible, #sample_info_form textarea:visible').on('keyup', function(event){
    var form_changed = has_form_changed();
    if(form_changed){
      $('#form_buttons input').each(function(){
        $(this).removeAttr("disabled").removeClass("disabled_button");
      });
    }else{
      $('#form_buttons input').each(function(){
        $(this).attr("disabled","disabled").addClass("disabled_button");
      });
    }
  });
  
  $('#sample_info_form').on('reset', function(event){
    $('#form_buttons input').each(function(){
      $(this).attr("disabled","disabled").addClass("disabled_button");
    });
  });

  $('#create_sample_btn').on('click', function(event){
    submit_new_sample();
  });
  
  
  // $("#add_sample").click(function() {
    // $( "#add-container-dialog-form").dialog("open");
  // });
//   
  // $("div#add-container-dialog-form").dialog(newContainerDialogOpts);
  
  
});

function submit_new_sample(){
  var form_data = $('#sample_info_form input[type="text"]:visible, #sample_info_form textarea:visible').serializeFormJSON();
  var posting = $.post(base_url + 'samples/create_new_sample', JSON.stringify(form_data));
  posting.done(function(data){
    window.location.replace(data.new_url);
  });
}

function submit_new_sample_container(){
  var form_data = $('#sample_container_body_x input, #sample_container_body_x select').serializeFormJSON();
  var posting = $.post(base_url + 'samples/create_sample_container/' + sample_id, JSON.stringify(form_data));
  posting.done(function(data){
    
  });
}

function check_container_create_active(){
  var valid_container = true;
  $('#sample_containers .required:visible').each(function(){
    if(this.willValidate && valid_container == true){
      valid_container = $(this).valid();
    }
    
  });
  if(valid_container == true){
    $('#create_sample_container').removeAttr("disabled").removeClass("disabled_button");
    //enable_element($('#create_sample_container'))
  }else{
    $('#create_sample_container').attr("disabled","disabled").addClass("disabled_button");
    // disable_element($('#create_sample_container'))
  }
}

function load_user_details(user_identifier){
  //first look to see if we have the info locally
  var user_id = parseInt(user_identifier.replace("user_",""),10);
  if(user_id in default_user_info){
    $('#user_details').html(format_user_details(default_user_info[user_id]));
  }else{
    url = base_url + 'ajax/get_user_details/' + user_identifier;
    $.get(url, function(data, status, x){
      $('#user_details').html(format_user_details(getHeaderJSON(x)));
    });
    
  }
}

function format_user_details(user_info){
  var info_block = $('<div id="user_details_' + user_info.contact_id +'" class="user_details_insert">' +
    '<table>' +
      '<tr>' +
        '<td class="user_details_label">Name:</td>' +
        '<td class="user_details_item">' +
          '<span id="user_' + user_info.contact_id +'_full_name" class="user_full_name">' + 
             '<address><a href="mailto:' + user_info.email +'">' + user_info.full_name + '</a></address>' +
          '</span>' +
        '</td>' +
      '</tr>' +
      '<tr>' +
        '<td class="user_details_label">Address:</td>' +
        '<td class="user_details_item">' +
          '<span id="user_' + user_info.contact_id +'_address" class="user_address">' +
            user_info.street_address +'<br />' +
            user_info.city + ', ' + user_info.state_province + ' ' + user_info.postal_code +'<br />' +
            user_info.country +
          '</span>' +
        '</td>' +
      '</tr>' +
    '</table>' +
  '</div>');
  return info_block;
}

function getHeaderJSON(xhr) {
  var json;
  try { json = xhr.getResponseHeader('X-Json'); }
  catch(e) {}

  if (json) {
    var data = eval('(' + json + ')'); // or JSON.parse or whatever you like
    return data;
  }
}

function has_form_changed(){
  var form_data_obj = $('#sample_info_form input[type="text"]:visible, #sample_info_form textarea').serializeFormJSON();
  var merged_object = $.extend({}, default_sample_info.general, form_data_obj);
  return JSON.stringify(merged_object) != JSON.stringify(default_sample_info.general) && $('#sample_name').val().trim() !== "";
}


(function($) {
    'use strict';
    // Sort us out with the options parameters
    var getAnimOpts = function (a, b, c) {
            if (!a) { return {duration: 'normal'}; }
            if (!!c) { return {duration: a, easing: b, complete: c}; }
            if (!!b) { return {duration: a, complete: b}; }
            if (typeof a === 'object') { return a; }
            return { duration: a };
        },
        getUnqueuedOpts = function (opts) {
            return {
                queue: false,
                duration: opts.duration,
                easing: opts.easing
            };
        };
    // Declare our new effects
    $.fn.showDown = function (a, b, c) {
        var slideOpts = getAnimOpts(a, b, c), fadeOpts = getUnqueuedOpts(slideOpts);
        $(this).hide().css('opacity', 0).slideDown(slideOpts).animate({ opacity: 1 }, fadeOpts);
    };
    $.fn.hideUp = function (a, b, c) {
        var slideOpts = getAnimOpts(a, b, c), fadeOpts = getUnqueuedOpts(slideOpts);
        $(this).show().css('opacity', 1).slideUp(slideOpts).animate({ opacity: 0 }, fadeOpts);
    };
    
    $.fn.hideDown = function (a, b, c) {
        var slideOpts = getAnimOpts(a, b, c), fadeOpts = getUnqueuedOpts(slideOpts);
        $(this).show().css('opacity', 1).slideDown(slideOpts).animate({ opacity: 0}, fadeOpts);
    };
    
    // $.fn.serializeFormJSON = function() {
// 
      // var o = {};
      // var a = this.serializeArray();
      // $.each(a, function() {
        // if (o[this.name] !== undefined) {
          // if (!o[this.name].push) {
            // o[this.name] = [o[this.name]];
          // }
          // o[this.name].push(this.value || '');
          // } else {
            // o[this.name] = this.value || '';
          // }
        // }
      // );
        // return o;
      // };    
    
}(jQuery));

