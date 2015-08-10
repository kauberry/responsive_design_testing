/**
 * @author d3k857
 */
var filter_settings; 

document.observe("dom:loaded", function() {
  if (!filter_settings) {
    filter_settings = new Cookies();
  }
});

var first_load = true;

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