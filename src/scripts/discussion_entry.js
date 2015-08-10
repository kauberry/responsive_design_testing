var last_comment_id = 0;

function submit_comment(button_id){
  var f = $('#' + button_id).closest('form');
  var data_object = f.serializeFormJSON();
  
  var url = base_url + 'discussion_entry/create/' + data_object.ticket_id;
  
  var posting = $.ajax({
    url:url,
    beforeSend:function(){
      $('#' + button_id).disable();
      f.attr("disable","disable");
    },
    data:JSON.stringify(data_object),
    type:"POST"
  });
  
  posting.done(function(data){
    update_comments(data);
    $('#' + button_id).enable();
    f.removeAttr("disable");
  });
  
  // new Ajax.Request(url, {
    // method: 'post',
    // contentType: 'application/json',
    // postBody: Object.toJSON(data_object),
    // onCreate: function(){
      // disable_element($(button_id));
      // f.disable();
    // },
    // onSuccess: function(transport){
      // update_comments(transport.responseJSON);
      // enable_element($(button_id));
    // },
    // onFailure: function(err){
//       
    // }
  // });
}

var check_comment_post_button_active = function(){
  var callcount = 0;
  var action = function(){
    if($('#comment_text').val().length > 0){
      //enable post button
      $('#comment_post_button').enable();
      // enable_element($('comment_post_button'));
    }else{
      //disable it
      $('#comment_post_button').disable();
      // disable_element($('comment_post_button'));
    }
  };
    var delayAction = function(action, time){
        var expectcallcount = callcount;
        var delay = function(){
            if(callcount == expectcallcount){
                action();
            }
        };
        setTimeout(delay, time);
    };
    return function(eventtrigger){
        ++callcount;
        delayAction(action, 1200);
    };
}();



var update_comments = function(comment_obj){
  //grab the id of the latest comment retrieved
  var last_id = this.last_comment_id;
  
  //grab the current ticket_id
  var ticket_id = parseInt($('#ticket_editor').serializeFormJSON().id, 10);
  
  
  //retrieve comments from backend through ajax
  if(!comment_obj){
    var url = base_url + 'discussion_entry/get/' + ticket_id + '/latest/' + last_id;
    
    var jqxhr = $.get(url, function(data){
      if(!isEmpty(data)){
        format_retrieved_comments(data);
      }
    });
    
    
    
    // new Ajax.Request(url, {
      // method: 'get',
      // onSuccess: function(transport){
        // format_retrieved_comments(transport.responseJSON);
      // }
    // });  
  }else{
    format_retrieved_comments(comment_obj);
  }
};

function delete_comment(post_id){
  var url = base_url + 'discussion_entry/delete/' + post_id;
  $.get(url, function(data){
    update_comments();
  });
  
  // new Ajax.Request(url, {
    // method: 'get',
    // onSuccess: function(transport){
      // update_comments();
    // }
  // });
};

function format_retrieved_comments(comments_obj){
  var comment_entry;
  var comment_header;
  var comment_body;
  var comment_footer;
  
  var comment_div = $('<div>');
  var counter = 0;
  
  $.each(comments_obj, function(){
    if(counter === 0){
      this.last_comment_id = this.id;
      counter++;
    }
    comment_entry = $('<div>')
      .attr("id",'comment_' + this.id)
      .addClass('comment_entry_container');
      
    var friendly_date = $.format.date(Date.parse(this.created_at), "ddd MMM dd yyyy h:mm:ss a");
    var comment_header = $('<div>', {class:"comment_header"})
      .append($('<div>', {id:'comment_' + this.id + '_title'})
        .append(this.title)
        .append($('<div>', { style:'font-size:0.85em;font-style:italic' })
          .append(this.display_name + ': ' + this.elapsed)
            .append($('<span>', {style:'float:right;'})
              .append(friendly_date.toString())
            )
          )
        );
        
    var comment_body = $('<div>', { class:'comment_body' })
      .append($('<span>', { id:'comment_' + this.id + '_text' })
        .append(this.content));
        
    var comment_footer = $('<div>', { id: 'comment_' + this.id + '_footer', class: 'comment_footer' })
      .append($('<div>', { class: 'ticket_buttons' }));
      
    var owner_delete;
    
    if(my_user_id == this.author_id){
      owner_delete = $('<input>', { type:'button', 'value': 'Delete' })
        .click(function(){ delete_comments(this.id); });
    }
    comment_entry
      .append(comment_header)
      .append(comment_body)
      .append(comment_footer);
    comment_div.append(comment_entry);
    
  });
  $('#retrieved_comments').html(comment_div);
  $('#comment_text').html = "";
  $('#comment_box').enable();
  
  
}


/*
 *     var owner_delete;
    if(my_user_id == s.author_id){
      owner_delete = Builder.node('input',{
          type: 'button',
          onclick: 'delete_comment(s.id);',
          value: 'Delete'
      });
      //comment_footer.down('div').appendChild(owner_delete);
    }
    
    comment_entry.appendChild(comment_header);
    comment_entry.appendChild(comment_body);
    comment_entry.appendChild(comment_footer);
    
    comment_div.appendChild(comment_entry);
 * 
 */


// function format_retrieved_comments(comments_obj){
//   
  // var comment_entry;
  // var comment_header;
  // var comment_body;
  // var comment_footer;
//   
  // var comment_div = Builder.node('div');
  // var counter = 0;
//     
  // comments_obj.each(function(s){
    // if(counter === 0){
      // this.last_comment_id = s.id;
      // counter++;
    // }
    // comment_entry = Builder.node('div', {
      // id: 'comment_' + s.id,
      // className: 'comment_entry_container'
    // });
//        
    // var friendly_date = Date.parse(s.created_at);
//     
//         
    // var comment_header = Builder.node('div', {
      // className: 'comment_header'
    // }, [
      // Builder.node('div', {
        // id: 'comment_' + s.id + '_title'
      // }, [s.title,
        // Builder.node('div', {
          // style: 'font-size:0.85em;font-style:italic;'  
        // }, [s.display_name + ': ' + s.elapsed, Builder.node('span',{ style:'float:right;' }, friendly_date.toString())])])
    // ]);
//     
    // var comment_body = Builder.node('div', {
      // className: 'comment_body'
    // },[
      // Builder.node('span', {
        // id: 'comment_' + s.id + '_text'
      // }, s.content)
    // ]);
//     
    // var comment_footer = Builder.node('div', {
      // id: 'comment_' + s.id + '_footer',
      // className: 'comment_footer'
    // }, [
      // Builder.node('div', {
        // className: 'ticket_buttons'
      // })
    // ]);
//     
    // var owner_delete;
    // if(my_user_id == s.author_id){
      // owner_delete = Builder.node('input',{
          // type: 'button',
          // onclick: 'delete_comment(s.id);',
          // value: 'Delete'
      // });
      // //comment_footer.down('div').appendChild(owner_delete);
    // }
//     
    // comment_entry.appendChild(comment_header);
    // comment_entry.appendChild(comment_body);
    // comment_entry.appendChild(comment_footer);
//     
    // comment_div.appendChild(comment_entry);
  // });
  // $('retrieved_comments').update(comment_div);
  // $('comment_text').clear();
  // $('comment_box').enable();
//   
// }