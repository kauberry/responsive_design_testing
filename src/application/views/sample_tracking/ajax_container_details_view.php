<div class="container_details_block" style="display:none;">
  <?php foreach($ctd as $ct_id => $ct_info): ?>
    <?php $uniq_id = uniqid("container_details_item_type_{$ct_id}_"); ?>
    <?php 
      // $req_field = $ct_info['is_required'] > 0 ? " required " : "";
      $req_field = " required "; 
    ?>
    <div class="full_width_block container_type_detail_entry">
      <label style="font-size:1.0em;width:20%;" for="<?= $uniq_id ?>"><?= ucwords($ct_info['field_name']) ?></label>
      <?php if($ct_info['field_type'] == 'dropdown'): ?>
        <?php
          $options = array_merge(array("" => ""),json_decode($ct_info['field_values_list'], true));
          $data_placeholder = "Choose a ".ucwords($ct_info['field_name']."...");
        ?>
        <?= form_dropdown($uniq_id, $options, '', 'required class="container_type_dropdown'.$req_field.'" id="'.$uniq_id.'" style="width:70%;margin-top:10px;"') ?>
        <script type="application/javascript">
          $(function(){
            $('#<?= $uniq_id ?>')
              .select2({placeholder:'<?= $data_placeholder ?>'})
          });
        </script>
      <?php elseif($ct_info['field_type'] == 'text'): ?>
        <?php
          $placeholder = $ct_info['field_default_text'] != null ? "{$ct_info['field_default_text']} " : "Enter a value for {$ct_info['field_name']} ";
          $units = $ct_info['field_units'] != null ? " with_units " : "";
        ?>
        <input type="text" placeholder='<?= $placeholder ?>' id="<?= $uniq_id ?>" name="<?= $uniq_id ?>" class="<?= $units ?><?= $req_field ?>" required />
        <?php if($ct_info['field_units'] != null): ?>
        <span class="with_units"><?= $ct_info['field_units'] ?></span>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
