<?php 
$user_array = (array)$sample_info->user_list;
$user_list_length = count($user_array);
//var_dump($user_array);
?>
<fieldset>
  <legend>Find or Add a User</legend>
  <div class="full_width_block">
    <div class="left_block">
      <div class="nonfield_label">User List</div>
      <div id="user_list_container" class="framed_container">
        <?php
          $user_list_empty_state = $user_list_length > 0 ? " hidden='hidden'" : "";
          $user_list_populated_state = $user_list_length == 0 ? " hidden='hidden'" : "";
          $selected_user_state = $user_list_length == 0 ? " no_users" : "";
        ?>
        <div id="empty_user_list" class="user_list fieldset_notification_banner_text"<?= $user_list_empty_state ?>>
          No Users Have Been Selected
        </div>
        <div id="populated_user_list" class="user_list"<?= $user_list_populated_state ?>>
          <ul>
          <?php foreach($user_array as $user_identifier => $user): ?>
            <li id="<?= $user_identifier ?>" class="user_name_entry">
              <div class="user_name"><?= $user->full_name ?></div> 
              <div class="user_affiliation"><?= $user->affiliation_name ?></div>
            </li>
          <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <div class="buttons">
        <input type="button" id="add_user" name="add_user" value="Add User" />
      </div>
    </div>
    <div class="right_block">
      <div class="nonfield_label<?= $selected_user_state ?>">Selected User Details</div>
      <div id="user_details" class="framed_container<?= $selected_user_state ?>">
      </div>
    </div>
  </div>
</fieldset>