/**
 * @author Ken J. Auberry
 */

var availability_data = {};

$(function(){
  
  //set up proposal selector
  $('#proposal_selector').select2({
    placeholder: "Select an EMSL User Proposal..."
  });
  $('#proposal_selector').change(function(e){
    check_ready_to_submit();
  });
  $('#comments').blur(function(){
    check_ready_to_submit();
  });
  
  //set up delete button
  // $('#delete_reservation').enable();
  $('#delete_reservation').click(function(e){
    var delete_it = window.confirm("Are you sure you want to delete this reservation?");
    if(delete_it){
      var url = base_url + 'scheduling/delete_reservation/' + initial_reservation_info.reservation_id;
      var getter = $.getJSON(url);
      getter.done(function(data){
        if(data.result_code && data.result_code < 300){
          //went through ok, return to appropriate calendar
          window.location.replace(base_url + 'scheduling/calendar/' + current_instrument_id);
        }else{
          //some kind of error occurred, display it
          $('#reservation_response').html(data.message);
          $('#reservation_response').fadeIn();
        }
      });
    }
  });
  
  
  $('#advanced_time_picker').on('rangeSelected',function(event){
    var range = get_time_range_from_advanced_picker();
    var start_time_string = $.datepicker.formatDate('yymmdd',range.start_date) + ((range.start_date.getHours() < 10 ? "0":"") + range.start_date.getHours()) + ((range.start_date.getMinutes() < 10 ? "0":"") + range.start_date.getMinutes());
    var end_time_string = $.datepicker.formatDate('yymmdd', range.end_date) + ((range.end_date.getHours() < 10 ? "0":"") + range.end_date.getHours()) + ((range.end_date.getMinutes() < 10 ? "0":"") + range.end_date.getMinutes());
    var url = base_url + 'scheduling/get_reservation_overlap/' + current_instrument_id + '/' + start_time_string + '/' + end_time_string;
    $.get(url, function(data){
      $('#reservation_response').html(data);
      $('#reservation_response').fadeIn();
    });
  });
  
  $('#advanced_time_picker')
    .find('input')
    .unbind("blur")
    .blur(function(){
      get_time_range_from_advanced_picker();
    });

  
  //set up instrument selector
  $("#instrument_selector").select2({
    placeholder: "Select an Instrument...",
    width: 'resolve'
  });
  
  if(page_mode == 'edit'){
    if($('#reservation_type_selector').val() == 'REG_USER'){
      //user res, show main datepicker, loaded
      var st = new Date(initial_reservation_info.start_time.split('-').join('/'));
      get_availability(st.getFullYear(),st.getMonth());
      // $('#time_slot_container').show();
    }
  }
  
  $('#instrument_selector').change(function(e){
    var current_res_type = $('#reservation_type_selector').val();
    var new_instrument_id = $(e.target).val();
    current_instrument_id = new_instrument_id;
    if(current_res_type == "REG_USER"){
      // get_availability();
    }
    // $.getJSON(base_url + "eus_access/get_proposal_list/" + new_instrument_id)
      // .done(function(data){
        // var list_html = '';
        // list_html += "<option></option>";
        // $.each(data.items, function(i, item){
          // list_html += '<option value="' + item.id + '">' + item.text + ' [Proposal# ' + item.id + ']' + '</option>';
        // });
        // $('#proposal_selector').select2('data', null);
        // $('#proposal_selector').html(list_html);
        // if('#proposal_selector_container:visible'){
          // $('#proposal_selector').select2("enable", true);
        // }else{
          // $('#proposal_selector').select2("enable", false);
        // }
      // });
    if(current_res_type.indexOf('USER') > 0){
      //User reservation, so we need a proposal
      $('#proposal_selector_container').show();
      $('#proposal_selector').select2('enable', true);      
      if(current_res_type == "EXT_USER"){
        //enable alternate time picker in addition to regular one?
        $('#time_slot_container').hide();
        load_advanced_datepicker();        
      }else{
        $('#time_slot_container').show();
        clear_advanced_datepicker();
        get_availability();
      }
    }else{
      $('#proposal_selector_container').hide();
      $('#proposal_selector').select2('enable', false);
      $('#time_slot_container').hide();
      load_advanced_datepicker();
      // if($('#advanced_datetime_picker_container').html().length == 0){
      // }
      
    }
    // if(current_res_type )
    check_ready_to_submit();

  });  
  
  
  //configure submit button
  $('#submit_reservation')
    .attr('type','button')
    .attr('disabled','disabled')
    .addClass('disabled_button')
    .click(function(el){
      submit_request();
    });
 
  check_ready_to_submit();
  // var milliseconds = $('#advanced_time_picker').datepair('getTimeDiff');
  // $('#advanced_time_picker').datepair('remove');
  // $('#advanced_time_picker').datepair('refresh');
});

  function get_form_values(){
    var f = $('#reservation_edit');
    var values = f.serializeFormJSON();
    if('reservation_type_id' in values === false){
      values['reservation_type_id'] = $('#reservation_type_selector').val();
    }
    if(!$('#external_user_selector').is(':visible')){
      delete values['ext_eus_user_id'];
    }
    if('eus_user_id' in values === false){
      values['eus_user_id'] = $('#user_selector').val();
    }
    return values;
  }
  
  
  
  function submit_request(){
    var values = get_form_values();
    var inst = $('#instrument_selector').select2('data').text;
    var alert_text = "You are about to submit a request\nto reserve the " + inst + '\n';
    var res_type_name = $("#reservation_type_selector").select2('data').text;
    var myuser = $('#user_selector').select2('data').text;
    var start = new Date(values.start_time.split('-').join('/'));
    var end = new Date(values.end_time.split('-').join('/'));
    if(values.reservation_type_id == "EXT_USER"){
      var extusername = $('#external_user_selector').select2("data").text;
      alert_text += "for EMSL User " + extusername + " (hosted by " + myuser + ")";
    }else if(values.reservation_type_id == "REG_USER"){
      alert_text += "for " + myuser;
    }else{
      alert_text += "\nfor the purpose of a " + res_type_name + " event";
    }
    alert_text += "\nfrom " + start.toLocaleDateString() + " at " + start.toLocaleTimeString();
    alert_text += "\nuntil " + end.toLocaleDateString() + " at " + end.toLocaleTimeString();
    
    var proceed = window.confirm(alert_text);
    
    if(proceed){
      values['comments'] = $('#comments').val();
      var url = base_url + 'scheduling/make_reservation';
      
      var posting = $.post(url, values)
      .done(function(data,status){
        if($('#datepicker:visible') && res_type_name == "REG_USER"){
          var dt = new Date($('#datepicker').datepicker('getFormattedDate'));
          get_availability(dt.getFullYear(), dt.getMonth() + 1);
          $('#datepicker').datepicker('setDate', dt);
        }
        $('#reservation_response').html(data.message).fadeIn().effect('highlight');
        setTimeout(function(){
          var dt = Date.parse($('#reservation_start_time').val());
          var include_date_string = dt.getFullYear() + ((dt.getMonth() + 1) < 10 ? "0":"") + (dt.getMonth() + 1) + (dt.getDate() < 10 ? "0":"") + dt.getDate();
          var cal_url = base_url + 'scheduling/calendar/' + current_instrument_id + '/' + include_date_string;
          window.location = cal_url;
        },3000);
        
      })
      .fail(function(obj,message,error){
        var message_obj = JSON.parse(obj.responseText);
        var message = "<h2 style='color:red;font-size:1.2em;font-weight:bold;'>Error in Creating Reservation</h2>";
        message += message_obj.results;
        
        $('#reservation_response')
          .addClass('error')
          .html(message)
          .fadeIn().effect('highlight');
      })
      .always(function(){
        $('#submit_reservation').disable();
      });
    }else{
      return false;
    }
    
  }
  
  function clear_response_block(){
    $('#reservation_response').fadeOut();
    $('#reservation_response').html();
  }



  function check_ready_to_submit(){
    clear_response_block();
    var values = get_form_values();
    var ready_to_submit = false;
    
    // delete values.reservation_comments;
    if(values.reservation_type_id != "EXT_USER"){
      delete values.ext_eus_user_id;
    }
    if(values.reservation_type_id.indexOf('USER') < 0){
      delete values.eus_proposal_id;
    }
    // if(values.reservation_type_id.indexOf('MAINT') >= 0 || values.reservation_type_id.indexOf('TRAINING') >= 0){
//       
    // }
    

    
    var check_value_obj = {};
    $.each(values, function(value_name, item_value){
      //clear out values we don't care about
      if($('#reservation_edit').children('input[name="' + value_name + '"]').hasClass('req_field') && !(value_name in initial_reservation_info)){
        check_value_obj[value_name] = "";
      }else if(value_name in initial_reservation_info === false){
        delete values[value_name];
      }else{
        check_value_obj[value_name] = initial_reservation_info[value_name];
      }
      
    });
    
    
    if(JSON.stringify(check_value_obj) != JSON.stringify(values)){
      ready_to_submit = true;
    }else{
      ready_to_submit = false;
    }
      
      
        // initial_reservation_info[value_name] = "";
      // }
      // if(item_value != initial_reservation_info[value_name]){
        // ready_to_submit = true;
//         
      // }
    $('input.req_field, select.req_field').each(function(index,field_item){
      if($(field_item).val().length == 0 && ($(field_item).is(':visible') || $(field_item).is('input[type="hidden"]'))){
        ready_to_submit = false;
        return false;
      }
    });


    
    if(ready_to_submit){
      $('#submit_reservation').enable();
    }else{
      $('#submit_reservation').disable();
    }
    console.log('ready_to_submit => ' + (ready_to_submit ? "Yes" : "No"));
    // if(!ready_to_submit){
      console.log(values);
    // }
    $('#reservation_link_' + initial_reservation_info.reservation_id).parents('label').prev('input[type="radio"]').attr('checked','checked');

    return ready_to_submit;
    
  }
  
  
  
  
  function clear_advanced_datepicker(){
    $('#advanced_datetime_picker_container').hide();
    // $('#advanced_datetime_picker_container').html();
  }
  
  
  
  
  function load_advanced_datepicker(){
    // var url = base_url + "scheduling/advanced_datepicker_load";
    // $.get(url, function(data){
      // $('#advanced_datetime_picker_container').html(data);
      get_proposal_list(current_instrument_id);
      $('#advanced_datetime_picker_container').show();
      $('#advanced_time_picker').datepair();
      $('#advanced_time_picker')
        .find('input')
        .unbind("blur")
        .blur(function(){
          get_time_range_from_advanced_picker();
        });
    // });
  }
  
  
  

  function setup_datepicker(dp){
    var today;
    if(page_mode == 'edit'){
      var st = new Date(initial_reservation_info.start_time.split('-').join('/'));
      today = st;
    }else{
      today = new Date();
    }
    var startDate = new Date();
    startDate.setHours(0,0,0,0);
    today.setHours(0,0,0,0);

    $( "#datepicker" ).datepicker({
      startDate: startDate,
      setDate: today,
      beforeShowDay: function(d){
        var class_list = [];
        var ds = $.datepicker.formatDate('yy-mm-dd',d);
        var retval = {enabled:true, classes:"", tooltip:"Available"};
        if($.inArray(ds,availability_data.availability.preferred) >= 0){
          class_list.push("preferred");
        }
        if(d < startDate){
          retval = {enabled:false, classes:"", tooltip:""};
        }else if($.inArray(ds, availability_data.availability.disallowed) >= 0){
          class_list.push("disallowed");
          retval = {enabled:false, classes:class_list.join(' '), tooltip:"Insufficient Reservations Available"};
        }else if($.inArray(ds, availability_data.availability.full) >= 0){
          class_list.push("full");
          retval = {enabled:true, classes:class_list.join(' '), tooltip:"No Time Slots Available"};
        }else if($.inArray(ds, availability_data.availability.partial) >= 0){
          class_list.push("partially_clear");
          retval = {enabled:true, classes:class_list.join(' '), tooltip:"Time Slots Available"};
        }else{
          class_list.push("free");
          retval = {enabled:true, classes:class_list.join(' '), tooltip:"Time Slots Available"};
        }
        return retval;
      }
    });
     $("#datepicker").on("changeMonth", function(event){
      var date = event.date;
      get_availability(date.getFullYear(),date.getMonth());
    });
    $("#datepicker").on("changeDate", function(event) {
      var ds = $("#datepicker").datepicker('getFormattedDate');
      configure_reservation_picker(ds);
      check_ready_to_submit();
    });    
    configure_reservation_picker($.datepicker.formatDate('mm/dd/yy',today));
    $('#time_slot_container').show();
  }
  
  
  
  
  function configure_reservation_picker(selectedDate){
    var d = new Date(selectedDate);
    var ds = $.datepicker.formatDate('yy-mm-dd',d);
    var current_user_id = parseInt($('#user_selector').val(),10);
    var url = base_url + 'scheduling/get_daily_timeslots/' + current_instrument_id + '/' + current_user_id + '/' + ds + '/' + page_mode;
    $.getJSON(url, function(data){
      if(data.status == 'denied'){
        $('#reservation_slot_display').hide();
        $('#insufficient_tokens_display').html(data.html);
        $('#insufficient_tokens_display').show();
      }else{        
        $('#time_slot_block').html(data.html);
        $('#insufficient_tokens_display').hide();
        $('#reservation_slot_display').show();
        $('#proposal_restrictions').html(data.associations_desc);
        if(data.show_warning_indicator){
          $('#proposal_restrictions').addClass('warning');
        }else{
          $('#proposal_restrictions').removeClass('warning');
        }
        get_proposal_list(current_instrument_id, ds);
        
        // $.getJSON(base_url + "eus_access/get_proposal_list/" + current_instrument_id + '/' + ds)
          // .done(function(data){
            // var list_html = '';
            // list_html += "<option></option>";
            // $.each(data.items, function(i, item){
              // list_html += '<option value="' + item.id + '">' + item.text + ' [Proposal# ' + item.id + ']' + '</option>';
            // });
            // $('#proposal_selector').select2('data', null);
            // $('#proposal_selector').html(list_html);
            // if('#proposal_selector_container:visible'){
              // $('#proposal_selector').select2("enable", true);
            // }else{
              // $('#proposal_selector').select2("enable", false);
            // }
            // $('#proposal_count').html(data.total_count + " Proposals Available");
          // });
        
        $('.reservation_slot_info').change(function(item){
          var id = $(item.target).prop('id');
          var time_objects = $(item.target).next().find('time');
          time_objects.each(function(index,item){
            item = $(item);
            $('#reservation_end_time').val();
            
            if(item.hasClass('start')){
              $('#reservation_start_time').val(item.attr('datetime'));
            }
            if(item.hasClass('end')){
              $('#reservation_end_time').val(item.attr('datetime'));
            }
          });
          check_ready_to_submit();
        });
      }
    });
    check_ready_to_submit();
  }


  var get_proposal_list = function(current_instrument_id, datestamp){
    var url = base_url + "eus_access/get_proposal_list/" + current_instrument_id;
    if(datestamp != undefined && datestamp.length > 0){
      url += '/' + datestamp;
    }
    var set_proposal_id = $('#proposal_selector').val().length > 0 ? $('#proposal_selector').val() : initial_reservation_info.eus_proposal_id;
    $.getJSON(url)
      .done(function(data){
        var list_html = '';
        list_html += "<option></option>";
        $.each(data.items, function(i, item){
          list_html += '<option value="' + item.id + '">[' + item.id + ']: ' + item.text + '</option>';
        });
        $('#proposal_selector').select2('data', null);
        $('#proposal_selector').html(list_html);
        $('#proposal_selector').val(set_proposal_id).change();
        if('#proposal_selector_container:visible'){
          $('#proposal_selector').select2("enable", true);
        }else{
          $('#proposal_selector').select2("enable", false);
        }
        $('#proposal_count').html(data.total_count + " Proposals Available");
      });
    
  };

  function get_availability(year,month){
    if(month){
      month++;
    }
    var today = year && month ? new Date(month + '/01/' + year) : new Date();
    var month_out = year && month ? new Date(month + '/01/' + year) : new Date();
    month_out.setMonth(month_out.getMonth() + 1);
    month_out.setDate(month_out.getDate() + 15);
    var start_date_string = $.datepicker.formatDate('yy-mm-dd', today);
    var end_date_string = $.datepicker.formatDate('yy-mm-dd', month_out);
    var current_eus_user_id = parseInt($('#user_selector').val(),10);
    var token_mod = page_mode == 'edit' ? 1 : 0;
    var url = base_url + 'scheduling/get_instrument_availability/' + current_instrument_id + '/' + current_eus_user_id + '/' + start_date_string + '/' + end_date_string + '/' + token_mod;
    $.getJSON(url, function(data){

      availability_data = data;
      if(!$('#datepicker').hasClass('hasDatepicker')){
        setup_datepicker($('#datepicker'));
      }
      if(month){
        $('#datepicker').datepicker('update','');
      }else{
        $('#datepicker').datepicker('update');
      }
    });
  }
  
    
  function get_time_range_from_advanced_picker(){
    var start_date_box = $('#advanced_time_picker .date.start');
    var end_date_box = $('#advanced_time_picker .date.end');
    var start_time_box = $('#advanced_time_picker .time.start');
    var end_time_box = $('#advanced_time_picker .time.end');
    
    var field_types = ['date','time'];
    var field_descriptors = ['start','end'];
    
    var range = {};
    
    $.each(field_descriptors, function(field_index, descriptor_name){
      var d = $('#advanced_time_picker .date.' + descriptor_name).datepicker('getDate');
      var t = $('#advanced_time_picker .time.' + descriptor_name).timepicker('getTime');
      if(d == null || t == null){
        $('#reservation_' + descriptor_name + '_time').val("");
        range = {};
        check_ready_to_submit();
        return false;
      }
      d.setHours(t.getHours(),t.getMinutes(),t.getSeconds());
      time_string = $.datepicker.formatDate('yy-mm-dd',d) + ' ' + ((d.getHours() < 10 ? "0":"") + d.getHours()) + ':' + ((d.getMinutes() < 10 ? "0":"") + d.getMinutes()) + ':00';
      range[descriptor_name + '_date'] = d;
      display_string = $.datepicker.formatDate('DD, d MM yy',d) + ' ' + $('#advanced_time_picker .time.' + descriptor_name).val();
      $('#reservation_' + descriptor_name + '_time').val(time_string);
      $('#reservation_' + descriptor_name + '_time_display').html(display_string);
      $('#reservation_time_display').show();
      check_ready_to_submit();
    });
    
    
    
    return range;
  }
  
  
  function set_datetime_from_advanced_picker(time_element_specifier,selectedDate){
    var time_string;
    var display_string;
    var time_val;
    var date_obj;
    var time_obj;
    var opposite_element_specifier = time_element_specifier == 'start' ? 'end' : 'start';
    time_element = $('#' + time_element_specifier + '_time_picker');
    date_element = $('#' + time_element_specifier + '_date_picker');
    if(date_element.val()){
      if(time_element.val()){
        time_obj = time_element.timepicker('getTime', new Date());
        date_obj = new Date(selectedDate);
        time_string = $.datepicker.formatDate('yy-mm-dd',date_obj) + ' ' + ((time_obj.getHours() < 10 ? "0":"") + time_obj.getHours()) + ':' + ((time_obj.getMinutes() < 10 ? "0":"") + time_obj.getMinutes()) + ':00';
        display_string = $.datepicker.formatDate('DD, d MM yy',date_obj) + ' ' + time_element.val();
        $('#reservation_' + time_element_specifier + '_time').val(time_string);
        $('#reservation_' + time_element_specifier + '_time_display').html(display_string);
        if($('#reservation_' + opposite_element_specifier + '_time_display').html().length > 0){
          $('#reservation_time_display').show();
        }else{
          $('#reservation_time_display').hide();
        }
      }
      
    }
    
    check_ready_to_submit();
  }