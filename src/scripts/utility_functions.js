/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Utility functions
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
if(window.Prototype){
  function disable_element(el){
    if(el){
      el.setAttribute('disabled', 'disabled');
      if (!el.hasClassName('disabled_button')) {
          el.addClassName('disabled_button');
      }    
    }
  }
  
  function enable_element(el){
    if(el){
      el.removeAttribute('disabled');
      if (el.hasClassName('disabled_button')) {
          el.removeClassName('disabled_button');
      }    
    }
  }
}

if(window.jQuery && jQuery.fn.jquery > "1.8"){
  
  $.fn.disable = function(){
    this.attr("disabled","disabled");
    this.addClass('disabled_button');
  };
  $.fn.enable = function(){
    this.removeAttr("disabled");
    this.removeClass('disabled_button');
  };
  
  $.fn.objectIsEqual = function(secondObject){
    var match = false;
    $.each(this,function(index,collectionObject){
      $.each(collectionObject, function(key,value){
        if(key in secondObject && secondObject[key] == value){
          match = true;
        }else{
          match = false;
          return match;
        }
      });
    });
    return match;
  };
  
    
  $.fn.up = function(type_desired){
    var parents = this.parents(type_desired);
    return parents[0];
  };
  
  $.fn.bodysnatch = function() {
    var collection = this;
    return collection.each(function(a,b) {
      var element = $(this);
      var clone = element.clone();
      
      w = element.width();
      h = element.height();
      if ( w && h)
      {
        clone.attr('style', window.getComputedStyle(element[0]).cssText);
        clone.css({
          position: 'absolute',
          top: element.offset().top,
          left: element.offset().left,
          width: element.width(),
          height: element.height(),
          margin:0
          //padding: 0
        });
      }
      else //probably images without a width and height yet
      {
        clone.css({
          position: 'absolute',
          top: element.offset().top,
          left: element.offset().left,
          margin:0
          //padding: 0
        });  
      }
      $('body').append(clone);
      if(element[0].id) {
          element[0].id=element[0].id+'_snatched';
      }
      element.addClass('snatched');
      clone.addClass('bodysnatcher');
      //stop audio and videos
      element.css('visibility','hidden');
      if(element[0].pause){
        element[0].pause();
        element[0].src='';
      }
      collection[a]=clone[0];
    });
  };
  
  $.fn.serializeFormJSON = function() {
  
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
      this.value = $.trim(this.value);
      if (o[this.name] !== undefined) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]];
        }
        o[this.name].push(this.value || '');
        } else {
          o[this.name] = this.value || '';
        }
      }
    );
      return o;
    };  
  
  $.fn.labelOver = function(overClass) {
      return this.each(function() {
          var label = $(this);
          var f = label.attr('for');
          if (f) {
              var input = $('#' + f);
  
              this.hide = function() {
                  label.css({
                      textIndent: -10000 
                  });
              };
  
              this.show = function() {
                  if (input.val() == '') 
                      label.css({
                          textIndent: 0 
                      });
              };
  
              // handlers
              input.focus(this.hide);
              input.blur(this.show);
              label.addClass(overClass).click(function() {
                  input.focus();
              });
  
              if (input.val() != '') 
                  this.hide();
          }
      });
  };
}

function isEmpty(str) {
    return (!str || 0 === str.length);
}

function isBlank(str) {
    return (!str || /^\s*$/.test(str));
}



/*  PNNL Javascript
    Author: Geoff Elliott
*/

//  items executed when the DOM is ready, before the page is displayed
$(function() {
  $('label[for=q]').labelOver('over-apply');
});
