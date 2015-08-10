<?php
  $container_type_options = $this->sample->get_container_types_list();
?>
<div id='sample_container_x' class='sample_container_frame'>
  <div id='sample_container_header_x' class='ticket_entry_header' style='position:relative;min-height:2em;'>
    <span><em>New Sample Container</em></span>
    <span style="position:absolute;right:3px;">Created: <?= date('j F Y g:ia')?></span>
  </div>
  <div id="sample_container_body_x" class="edit_container sample_container_body">
    <div class="full_width_block" id="container_info_holder">
      <label for="container_name_x">Container Name</label>
      <input type="text" class="required" id="container_name_x" placeholder="Name this container" name="container_name_x" style="width:100%;" />
      <label for="container_description_x">Container Description</label>
      <textarea id="container_description_x" name="container_description_x" style="width:100%" rows="3" placeholder="Describe this sample container"></textarea>
      <label for="container_type_x">Container Type</label>
      <?= form_dropdown('container_type_x',$container_type_options,'','data-placeholder="Select a Container Type..." class="container_type_dropdown required" id="container_type_x" style="width:100%;"') ?>
      <div class="container_options_block"></div>
    </div>
    <div class="buttons" style="margin-top:10px;height:20px;">
      <input type="button" disabled="disabled" class="disabled_button" id="create_sample_container" name="create_sample_container" value="Create Container" />
    </div>
    
  </div>
</div>
