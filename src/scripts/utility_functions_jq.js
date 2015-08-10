/**
 * @author Ken J. Auberry
 */

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
  this.each(function(key,value){
    if(key in secondObject && secondObject.get(key) == value){
      match = true;
    }else{
      match = false;
      return match;
    }
  });
};
