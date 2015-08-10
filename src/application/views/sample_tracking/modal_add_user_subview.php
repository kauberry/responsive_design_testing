<div class="dialog_content themed form_container">
  <fieldset style="background-color:#efefef;">
    <legend>Available Users</legend>
    <div class="full_width_block">
      <table cellpadding="0" cellspacing="0" border="0" class="display" id="user_list">
        <thead>
          <tr>
            <th width="1%"></th>
            <th width="20%">Name</th>
            <th width="30%">Email</th>
            <th width="50%">Affiliation</th>
          </tr>
        </thead>
        <tbody>
  
        </tbody>
      </table>
    </div>
  </fieldset>
  <div class="ui-dialog-buttonset" id="add_buttons_set" style="text-align: center;padding:1em;"></div>
  <fieldset style="background-color:#efefef;">
    <legend>Users Associated with this Sample</legend>
    <div class="full_width_block">
      <table cellpadding="0" cellspacing="0" border="0" class="display" id="sample_users_list">
        <thead>
          <tr>
            <th width="1%"></th>
            <th width="20%">Name</th>
            <th width="30%">Email</th>
            <th width="50%">Affiliation</th>
          </tr>
        </thead>
        <tbody>
  
        </tbody>
      </table>
    </div>
  </fieldset>

</div>
<div id="new_user_form" title="Create a New User">
  <p></p>
</div>
<script type="text/javascript" charset="utf-8">
  $(function(){
    $('#user_list tbody').click(function(event){
      $(oTable.fnSettings().aoData).each(function(){
        $(this.nTr).removeClass('row_selected');
      });
      $(event.target.parentNode).addClass('row_selected');
    });
    oTable = $('#user_list').dataTable({
      "bProcessing" : true,
      "sAjaxSource" : base_url + "sample_tracking_users/full_user_list/" + sample_id
    });
    
    $('#sample_users_list tbody').click(function(event){
      $(sTable.fnSettings().adData).each(function(){
        $(this.nTr).removeClass('row_selected');
      });
      $(event.target.parentNode).addClass('row_selected');
    });
    sTable = $('#sample_users_list').dataTable({
      "bProcessing" : true,
      "sAjaxSource" : base_url + "sample_tracking_users/sample_user_list/" + sample_id
    });
    
    var add_button_el = $('#add_buttons_set');
    var add_button_id = 'add_user_button';
    var new_button_id = 'new_user_button';
    
    add_button_el.append('<span class="ui-button-text dynamic-button" id="' + new_button_id + '" style="margin:0 5px;">Create a New User</span>');
    add_button_el.append('<span class="ui-button-text dynamic-button" id="' + add_button_id + '" style="margin:0 5px;">Add Selected User to Sample</span>');
    
    var new_user_dialogOpt = {
      modal:true,
      autoOpen: false,
      dialogClass: "dialogWithDropShadow",
      width: 500,
      show: "showDown",
      hide: "hideUp",
      open: function(event){
        $(this).load(base_url + "sample_tracking_users/load_new_user_dialog");
      },
      buttons: [
        {
          text: "Create User",
          click: function(){
            alert("hey create user got clicked!")
          }
        }
      ]
    };
    
    $('#' + add_button_id).button().click(function(){ alert("hey I got clicked!!!"); });
    $('#' + new_button_id).button().click(function(){
      $("#new_user_form").dialog("open");
    });
    
    $("#new_user_form").dialog(new_user_dialogOpt);
    
  });
      // Safari reports success of list attribute, so doing ghetto detection instead
    yepnope({
      test : (!Modernizr.input.list || (parseInt($.browser.version) > 400)),
      yep : [
          '/resources/scripts/modernizr/jquery.relevant-dropdown.js',
          '/resources/scripts/modernizr/load-fallbacks.js'
      ]
    });

  
  function fnGetSelected(oTableLocal){
    var aReturn = new Array();
    var aTrs = oTableLocal.fnGetNodes();
    for(var i=0; i<aTrs.length; i++){
      if($(aTrs[i]).hasClass('row_selected')){
        aReturn.push(aTrs[i]);
      }
    }
    return aReturn;
  }
</script>
