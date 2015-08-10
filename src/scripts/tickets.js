/**
 * @author d3k857
 */
var filter_settings; 

document.observe("dom:loaded", function() {
  $$('#ticket_filter input').each(function(s){
    s.observe('click', update_filters.bindAsEventListener(s));
  });
  
  $$('.more_button').each(function(s){
    s.observe('click', expand_text_block.bindAsEventListener(s));
  });
  if (!filter_settings) {
    filter_settings = new Cookies();
  }
  checkTicketVisibility();
});

var first_load = true;

var expand_text_block = function(id){
  var totalHeight = 0;
  var el = id.findElement();
  var p = el.up('p');
  var up = p.up();
  var ps = up.select('p.ticket_info');
  
  ps.each(function(n) {
    totalHeight += n.getHeight(); 
  });
  
//  up.setStyle({
//    'height' : up.getHeight(),
//    'max-height' : 9999
//  });
  
//  up.morph('height:' + totalHeight + 'px;');
  
  new Effect.Parallel([
    Effect.Fade(p, { duration: 0.5 }),
    new Effect.Morph(up, {
      style: 'height:' + totalHeight + 'px;',
      duration: 1.0
    })
  ]);
  
  return false;
  
};

var update_filters = function(id){
  //find the element that was clicked
  var el = id.findElement();
  var checked = el.checked;
  var type = el.up('fieldset').id.sub('by_','');
  var identifier = 'magres_ticket_filters_' + type + '_' + el.id;
  
  checkTicketVisibility();
  filter_settings.set(identifier, checked ? "1" : "0", 7);


};

function toggleTicketBlock(id,state){
  var header = $(id);
  var header_label = header.down('h2');
  var block = $(header_label.id + '_block');
  var dc = $(header_label.id + '_dc');
  
  var cookie_handler = new Cookies();
  
  if(!state){
    if(header_label.hasClassName('open')){
      state = "closed";
    }else{
      state = "open";
    }
  }
  
  
  if(state == 'closed' && header_label.hasClassName('open')){
    //block is open, get ready to close
    Effect.SlideUp(block, {
      duration: 0.25,
      afterFinish: function(){
        header_label.removeClassName('open').addClassName('closed');
        dc.removeClassName('dc_down').addClassName('dc_up');
      }
    });
    cookie_handler.set('magres_' + header_label.id.replace("/","-"),"closed");
  }else if((state == 'open') && header_label.hasClassName('closed')){
    //block is already closed, so open it
    header_label.removeClassName('closed').addClassName('open');
    Effect.SlideDown(block, { duration:0.25});
    dc.removeClassName('dc_up').addClassName('dc_down');
    cookie_handler.set('magres_' + header_label.id.replace("/","-"),"open");   
  }else{
    
  }
  
}

function checkTicketVisibility(){
  var current_filter_settings = {};
  current_filter_settings.priority = {};
  $$('#by_priority_list input').pluck('id').each(function(s){
       current_filter_settings.priority[s] = $(s).checked;
     });
  current_filter_settings.status = {};     
  $$('#by_status_list input').pluck('id').each(function(s){
       current_filter_settings.status[s] = $(s).checked;
     });
     
  $$('#table_container div.ticket_entry_container').each(function(s){
    var status = $w($(s).down('span.state').className).without('state')[0].sub('state_','').toLowerCase();
    var priority = $w($(s).down('span.priority').className).without('priority')[0].sub('priority_','').toLowerCase();
    if(current_filter_settings.status[status] === false || current_filter_settings.priority[priority] === false){
      $(s).hide();
    }else{
      $(s).show();
    }
  });
  
  $$('div.tix_container').each(function(s){
    var el = $(s);
    var part_array = el.select('.ticket_entry_container').invoke('visible').partition();
    if(part_array[0].length === 0){
      el.hide();
    }else{
      el.show();
    }
    var visible_tix_count = el.down('.visible_tix_count');
    var current_count = parseInt(visible_tix_count.innerHTML,10);
    visible_tix_count.update(part_array[0].length);
    if(current_count != part_array[0].length && !first_load){  
      if(el.visible){
        new Effect.Highlight(el.down('h2'),{ restorecolor : 'rgb(238,238,238)' });
      }
    }else if(first_load){
      var toggleState = filter_settings.get('magres_' + el.down('h2').id.replace("/","-")) || "closed";
      toggleTicketBlock(el,toggleState);
    }
  });
  first_load = false;

}
