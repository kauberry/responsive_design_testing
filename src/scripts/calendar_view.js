/**
 * @author Ken J. Auberry
 */
var todays_date = new Date();
var update_interval = 120000;
var availability_data = {};

var update_fwdback = function(){
  //setup fwd/back buttons
  $('a.week-select-link').hide();
  $('input.week-select').click(function(el){
    var date_target;
    date_target = $(el.target).hasClass('forward') ? next_week : last_week;
    update_calendar(current_instrument_id,date_target);
  });
  $('input.week-select').show();

};

function update_reservation_links(){
  $('.calendar_entry').each(function(index){
    var link = $(this).find('a.reservation_link').prop('href');
    $(this).click(function(){
      window.open(link,'_blank');
    });
  });
}

function setup_new_reservation_click(){
  return false;
  $('.vertical-day').click(function(el){
    var day = $(el.target);
    var id = day.prop('id');
    var date_matcher = /day_column_((\d{4})(\d{2})(\d{2}))/;
    var m = id.match(date_matcher);
    var date = new Date(m[3] + '/' + m[4] + '/' + m[2]);
    var parentOffset = $(this).parent().offset();
    var myOffset = $(this).offset();
    var relX = el.pageX - parentOffset.left;
    var relY = el.pageY - parentOffset.top;
    var apxTimeOffsetMin = ( relY / $(this).parent().height()) * 24 * 60;
    var offsetHours = parseInt(apxTimeOffsetMin / 60,10);
    var offsetMin = apxTimeOffsetMin - (offsetHours * 60);
    var apxTime = new Date();
    date.setHours(offsetHours,offsetMin,0,0);
    var start_time = m[1].concat(offsetHours,offsetMin,'00');
    window.open(base_url + 'scheduling/reservation/new/' + current_instrument_id + '-00000-' + start_time);
  });
}

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
  
  var url = base_url + 'scheduling/get_instrument_availability/' + current_instrument_id + '/' + eus_user_id + '/' + start_date_string + '/' + end_date_string + '/';
  $.getJSON(url, function(data){
    availability_data = data;  
    setup_datepicker($('#month_calendar'));
    // if(!$('#month_calendar').hasClass('hasDatepicker')){
      // setup_datepicker($('#month_calendar'));
    // }else{
      // $('#month_calendar').datepicker('refresh');
    // }
  });
}


var update_calendar = function(inst_id, include_date){
  var url_date = include_date ? include_date : current_include_date;
    
  var url = base_url + 'scheduling/calendar/' + inst_id + '/' + url_date;
  $.get(url, function(data){
    $('#form_container').html(data);
    $('#date_selector_container').html('Week of ' + first_date + '&ndash;' + last_date + ' ' + cal_year);
    update_reservation_links();
    setup_new_reservation_click();
  });
};

var setup_datepicker = function(){
  var today = new Date();
  today.setHours(0,0,0,0);
  $('#month_calendar').datepicker({
      // startDate: today,
      beforeShowDay: function(d){
        var ds = $.datepicker.formatDate('yy-mm-dd',d);
        var retval = {enabled:true, classes:"", tooltip:"Available"};
        if(d < today){
          retval = {enabled:false, classes:"", tooltip:""};
        }else if($.inArray(ds, availability_data.availability.disallowed) >= 0){
          retval = {enabled:true, classes:"disallowed", tooltip:"Insufficient Reservations Available"};
        }else if($.inArray(ds, availability_data.availability.full) >= 0){
          retval = {enabled:true, classes:"full", tooltip:"No Time Slots Available"};
        }else if($.inArray(ds, availability_data.availability.partial) >= 0){
          retval = {enabled:true, classes:"partially_clear", tooltip:"Time Slots Available"};
        }else{
          retval = {enabled:true, classes:"free", tooltip:"Time Slots Available"};
        }
        return retval;
      }
    }
  );
  $('#month_calendar').on("changeMonth", function(event){
    var today = new Date();
    var date = event.date;
    today.setHours(0,0,0,0);
    date.setDate(1);
    date = date >= today ? date : today;
    get_availability(date.getFullYear(),date.getMonth());
    $('#month_calendar').datepicker('setDate', date);
  });
  
  $('#month_calendar').on("changeDate", function(event){
    var ds = $.datepicker.formatDate('yy-mm-dd',event.date);
    update_calendar(current_instrument_id,ds);
  });
  
  
  $('#month_calendar').datepicker('update');
  // $('.datepicker-days td').mouseover(function(e){
    // var daynum = $(this)
  // });
};


$(function(){
  
  update_fwdback();
  
  setInterval(function() {
    var d = new Date();
    if(d.getDate() != todays_date.getDate()){
      set_today_column();
      todays_date = new Date();
    }
    var minutes = d.getMinutes();
    $('#line_min').html(( minutes < 10 ? "0" : "") + minutes);
    var time_min = d.getHours() * 60 + d.getMinutes();
    $('#timed-scroll').css("top", function(index){
      var new_pos = (time_min / 1440.0) * 100.0;
      return new_pos + "%";
    });
    update_calendar(current_instrument_id);
  },update_interval);
  
  setInterval(function(){
    var hours = new Date().getHours();
    var ampm = ' AM';
    if(hours > 12){
      hours = hours - 12;
      ampm = ' PM';
    }else if(hours == 12){
      ampm = ' PM';
    }
    $('#line_hour').html(hours);
    $('#line_ampm').html(ampm);
    
  },update_interval);
  
  var set_today_column = function(){
    var d = new Date();
    var year = d.getFullYear();
    var month = ((d.getMonth() + 1 < 10) ? "0" : "") + (d.getMonth() + 1);
    var date = ((d.getDate() < 10) ? "0" : "") + d.getDate();
    var date_component = "" + year + month + date + "";
    
    var updateblocks = ['day_', 'daynum_','dayname_','day_column_'];
    
    $.each(updateblocks,function(index,id){
      var today_block = id + date_component;
      $.each($('[id^=' + id + ']'),function(index,el){
        el = $(el);
        if(el.attr('id') == today_block){
          el.addClass('today');
        }else{
          el.removeClass('today');
        }
      });
    });
  };

  var initial_url = base_url + 'scheduling/calendar/' + current_instrument_id + '/' + current_include_date;
  $.get(initial_url, function(data){
    $('#form_container').html(data);
    $('select#instrument_selector').val(current_instrument_id);
    update_reservation_links();
    setup_new_reservation_click();
  });
  
  $('select#instrument_selector').change(function(e){
    var new_inst = $(e.target).val();
    update_calendar(new_inst);
    current_instrument_id = new_inst;
    get_availability();
    // var url = base_url + 'scheduling/calendar/' + new_inst + '/' + current_include_date;
    // $.get(url, function(data){
      // $('#form_container').html(data);
      // current_instrument_id = new_inst;
    // });
  });
  
  get_availability();  
    
  
  
});
