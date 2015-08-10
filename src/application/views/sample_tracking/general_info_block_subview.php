<?php
$general_info = $sample_info->general;
?>
<fieldset>
  <legend>General Sample Info</legend>
  <div id="general_info_edit_block" class="edit_container">
    <div class="full_width_block">
      <label for="sample_name">Sample Name</label>
      <input 
        type="text" 
        id="sample_name" 
        name="sample_name" 
        style="width:100%;font-size:1.25em;" 
        placeholder="Enter a name for this sample..." 
        value="<?= $general_info->sample_name ?>" 
      />
    </div>
    <div class="full_width_block">
      <label for="sample_description">Sample Description</label>
      <textarea 
        id="sample_description" 
        name="sample_description" 
        style="width:100%;" 
        rows="4" 
        placeholder="Describe this sample..."><?= $general_info->sample_description ?></textarea>
    </div>
  </div>
</fieldset>