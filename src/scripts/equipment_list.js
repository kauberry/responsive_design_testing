/**
 * @author d3k857
 */
var filter_settings;

document.observe("dom:loaded", function() {
  if (!filter_settings) {
    filter_settings = new Cookies();
  }  
  checkBlockVisibility();
});


function toggleEquipmentBlock(id, state){
  var header = $(id);
  var block = $(header.id + '_block');
  var dc = $(header.id + '_dc');
  
  var cookie_handler = new Cookies();
  
  if(!state){
    if(header.hasClassName('open')){
      state = "closed";
    }else{
      state = "open";
    }
  }

  
  
  if((state == 'closed') && header.hasClassName('open')){
    //block is open, get ready to close
    Effect.SlideUp(block, {
      duration: 0.25,
      afterFinish: function(){
        header.removeClassName('open').addClassName('closed');
        dc.removeClassName('dc_down').addClassName('dc_up');
      }
    });
    cookie_handler.set('magres_' + header.up('div.location_entry_container').id.replace("/","-"),"closed");
  }else if((state == 'open') && header.hasClassName('closed')){
    //block is already closed, so open it
    header.removeClassName('closed').addClassName('open');
    Effect.SlideDown(block, { duration:0.25});
    dc.removeClassName('dc_up').addClassName('dc_down');
    cookie_handler.set('magres_' + header.up('div.location_entry_container').id.replace("/","-"),"open");   
  }else{
    
  }
  
}

function checkBlockVisibility(){
  
  $$('div.location_entry_container').each(function(s){
    var el = $(s);
    var toggleState = filter_settings.get('magres_' + el.id.replace("/","-")) || "closed";
    toggleEquipmentBlock(el.down('h2'),toggleState);
  });

}

