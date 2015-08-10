<fieldset>
  <legend>Sample Container Info</legend>
  <div id="container_info_block_div" style="min-height:120px;">
  <?php if(empty($sample_info->containers)): ?>
    <div id="no_sample_containers_notification" class="fieldset_notification_banner">
      <span id="no_sample_containers_notification_text" class="fieldset_notification_banner_text">No Sample Containers have been created for this sample</span>
      <div class="buttons">
        <input type="button" id="add_sample_container" value="Create a New Sample Container"/>    
      </div>
    </div>
    <div id="sample_containers" style='display:none;'>
      <?php $this->load->view('sample_tracking/new_container_subview') ?>
    </div>
  <?php else: ?>
    <div id="sample_containers">
    <?php foreach($sample_info->containers as $container_id => $container): ?>
      <div id="sample_container_<?= $container_id ?>" class="sample_container_entry ticket_entry_container">
        <div class="sample_container_header">
          <span>Container Type: <span class="container_type_entry"><?= ucwords($container["container_type"]) ?></span></span>
          <span style="float:right;">Added: <span class="timestamp"><?php echo strftime('%a, %#d %b %Y %H:%M %Z', strtotime($container["last_modified"])) ?></span></span>
        </div>
        <div class="ticket_body sample_container_body" style="background-color:#fff;">
          <div class="sample_container_details_holder">
          <?php foreach($container['container_details'] as $details_id => $details): ?>
            <div class="details_container">
              <span class="detail_type"><?= $details['field_name'] ?>:</span> 
              <span class="detail_value"><?= $details['value'] ?></span> 
              <span class="detail_units"><?= $details['field_units'] ?></span> 
            </div>
          <?php endforeach; ?>
          </div>          
          <div class="sample_container_buttons">
            <a href="<?= base_url() ?>sample_container/delete/<?= $container_id ?>">Delete</a>
            <a href="<?= base_url() ?>sample_container/edit/<?= $container_id ?>">Edit</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
  </div>
</fieldset>