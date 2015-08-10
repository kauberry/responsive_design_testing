<?php
  $form_fields = array(
    "title" => array("label" => "Title", "type" => "select", "required" => true),
    "first_name" => array("label" => "First Name", "type" => "text", "required" => true),
    "middle_initial" => array("label" => "Middle Initial", "type" => "text", "required" => false),
    "last_name" => array("label" => "Last Name", "type" => "text", "required" => true),
    "email" => array("label" => "email", "type" => "text", "required" => true),
    "affiliation" => array("label" => "affiliation", "type" => "affiliation_list", "required" => true),
    "street_address_1" => array("label" => "Street Address (1)", "type" => "text", "required" => false),
    "street_address_2" => array("label" => "Street Address (2)", "type" => "text", "required" => false),
    "postal_code" => array("label" => "Postal Code", "type" => "postal_code", "required" => true),
    "city" => array("label" => "City", "type" => "text", "required" => true),
    "state_province" => array("label" => "State/Province", "type" => "states_list", "required" => true),
    "country" => array("label" => "Country", "type" => "text", "required" => true)
  );
  $title_list = array(
    "dr" => "Dr.",
    "mr" => "Mr.",
    "ms" => "Ms."
  );
  $state_province_list = array(
      'AL'=>"Alabama",  
      'AK'=>"Alaska",  
      'AZ'=>"Arizona",  
      'AR'=>"Arkansas",  
      'CA'=>"California",  
      'CO'=>"Colorado",  
      'CT'=>"Connecticut",  
      'DE'=>"Delaware",  
      'DC'=>"District Of Columbia",  
      'FL'=>"Florida",  
      'GA'=>"Georgia",  
      'HI'=>"Hawaii",  
      'ID'=>"Idaho",  
      'IL'=>"Illinois",  
      'IN'=>"Indiana",  
      'IA'=>"Iowa",  
      'KS'=>"Kansas",  
      'KY'=>"Kentucky",  
      'LA'=>"Louisiana",  
      'ME'=>"Maine",  
      'MD'=>"Maryland",  
      'MA'=>"Massachusetts",  
      'MI'=>"Michigan",  
      'MN'=>"Minnesota",  
      'MS'=>"Mississippi",  
      'MO'=>"Missouri",  
      'MT'=>"Montana",
      'NE'=>"Nebraska",
      'NV'=>"Nevada",
      'NH'=>"New Hampshire",
      'NJ'=>"New Jersey",
      'NM'=>"New Mexico",
      'NY'=>"New York",
      'NC'=>"North Carolina",
      'ND'=>"North Dakota",
      'OH'=>"Ohio",  
      'OK'=>"Oklahoma",  
      'OR'=>"Oregon",  
      'PA'=>"Pennsylvania",  
      'RI'=>"Rhode Island",  
      'SC'=>"South Carolina",  
      'SD'=>"South Dakota",
      'TN'=>"Tennessee",  
      'TX'=>"Texas",  
      'UT'=>"Utah",  
      'VT'=>"Vermont",  
      'VA'=>"Virginia",  
      'WA'=>"Washington",  
      'WV'=>"West Virginia",  
      'WI'=>"Wisconsin",  
      'WY'=>"Wyoming");
  $datalist = "";
  $script_content = "";
?>
<div id="create_new_user_block">
  <form class="themed">
    <fieldset>
      <legend>Create a New User</legend>
      <?php foreach($form_fields as $field_name => $field_info): ?>
      <?php
        switch($field_info['type']){
          case "text":
            $field_data = array(
              'name' => $field_name,
              'id' => $field_name
            );
            $generated_field = form_input($field_data);
            break;
          case "select":
            $dd_name = "{$field_name}_list";
          	$generated_field = form_dropdown($field_name, ${$dd_name}, "id='{$field_name}'");
          	break;
          case "states_list":
            $generated_field = "<input id='{$field_name}' />
            <input type='hidden' id='{$field_name}-id' />";
            $states_list = array();
            foreach($state_province_list as $state_abbrev => $full_state_name){
              $states_list[] = "{
                value: '{$state_abbrev}',
                label: '{$full_state_name}'
              }";
            }
            $script_content .= "
            var states_list = [". implode(',',$states_list) ."];
            $('#{$field_name}').autocomplete({
              minLength: 0,
              source: function(request,response){
                var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), 'i');
                response($.grep(states_list, function(value){
                  return matcher.test(value.value) || matcher.test(value.label);
                }));
              },
              focus: function(event, ui){
                $('#{$field_name}').val(ui.item.label);
                return false;
              },
              select: function(event,ui){
                $('#{$field_name}').val(ui.item.label);
                $('#{$field_name}-id').val(ui.item.value);
                return false;
              }
            })
            .data('ui-autocomplete')._renderItem = function(ul, item){
              return $('<li>')
                .append('<a>' + item.label + '</a>')
                .appendTo(ul);
            };
            ";
            break;
            case "postal_code":
              $generated_field = "<input id='{$field_name}' />
              <input type='hidden' id='{$field_name}-id' />";
              $script_content .= "
              $('#{$field_name}').autocomplete({
                source: function(request,response){
                  $.ajax({
                    url: 'http://api.geonames.org/postalCodeSearchJSON',
                    dataType: 'jsonp',
                    data: {
                      username: 'kauberry',
                      maxRows: 12,
                      country: 'US',
                      postalcode: request.term
                    },
                    success: function(data){
                      response($.map(data.postalCodes, function(item){
                        return {
                          label: item.placeName + (item.adminName1 ? ', ' + item.adminName1 : '') + ', ' + item.countryCode,
                          city: item.placeName,
                          state: item.adminName1,
                          state_abbrev: item.adminCode1,
                          postal_code: item.postalCode,
                          country: item.countryCode
                        }
                      }));
                    }
                  });
                },
                minLength: 5,
                select: function(event,ui){
                  // tran$('#postal_code').val(ui.item.postal_code);
                  $('#city').val(ui.item.city).effect('highlight','slow');
                  $('#state_province').val(ui.item.state).effect('highlight','slow');
                  $('#country').val(ui.item.country).effect('highlight','slow');
                  $('#state_province-id').val(ui.item.state_abbrev);
                  return false;
                },
                open: function(){
                  $(this).removeClass('ui-corner-all').addClass('ui-corner-top');
                },
                close: function(){
                  $(this).removeClass('ui-corner-top').addClass('ui-corner-all');
                },
                focus: function(event,ui){
                  event.preventDefault();
                  $('#postal_code').val(ui.item.postal_code);
                }
              });";
              break;
          case "affiliation_list":
            $generated_field = "<input id='{$field_name}' />
            <input type='hidden' id='{$field_name}-id' />";
            $script_content .= "
            $('#{$field_name}').autocomplete({
              source: function(request,response){
                $.ajax({
                  url: base_url + 'sample_tracking_users/get_affiliations_list/' + request.term,
                  dataType: 'json',
                  success: function(data){
                    response($.map(data.affiliations, function(item){
                      return {
                        label: item.name,
                        code: item.code,
                        name: item.name,
                        id: item.id
                      }
                    }));
                  }
                });
              },
              minLength: 0,
              select: function(event,ui){
                $('#{$field_name}-id').val(ui.item.id);
              },
              open: function(){
                $(this).removeClass('ui-corner-all').addClass('ui-corner-top');
              },
              close: function(){
                $(this).removeClass('ui-corner-top').addClass('ui-corner-all');
              }
            });
            ";
            break;
          default:
        }
        
      ?>
      <div class='full_width_block'>
        <div class='left_block' style='text-align:right;width:29%;'><label for='<?= $field_name ?>'><?= $field_info['label'] ?></label></div>
        <div class='right_block' style='width:60%;'><?= $generated_field ?></div>
      </div>
      <?php endforeach; ?>
    </fieldset>
  </form>
</div>
<?php if(strlen($script_content) > 0): ?>
<script type="text/javascript">
  $(function(){
    <?= $script_content ?>
  });
</script>
<?php endif; ?>
