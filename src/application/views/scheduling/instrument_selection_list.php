<?php
  $table_object = !empty($table_object) ? $table_object : "";
  $this->load->view('pnnl_template/view_header'); 
?>
<body class="col2">
  <?php $this->load->view('pnnl_template/intranet_banner'); ?>
  <div id="page">
    <?php $this->load->view('pnnl_template/top',$navData['current_page_info']); ?>
    <div id="container">
      <div id="main">        
        <h1 class="underline"><?= $page_header ?></h1>
        <div class="form_container">
          <?php foreach(array_keys($instrument_list) as $inst_cat): ?>
          <div class="location_entry_container" id="category_<?= strtolower(str_replace(" " , "_", trim($inst_cat))) ?>_container">
            <h2 class="location_header open" id="category_<?= strtolower(str_replace(" " , "_", trim($inst_cat))) ?>"><?= $inst_cat ?></h2>
            <div class="location_entry" id="category_<?= strtolower(str_replace(" " , "_", trim($inst_cat))) ?>_block">
              <div>
                <ul class="equipment_entry">
                  <?php foreach(array_keys($instrument_list[$inst_cat]) as $inst_id): ?>
                  <?php $inst_info = $instrument_list[$inst_cat][$inst_id]; ?>
                  <li id="instrument_id_<?= $inst_id ?>">
                    <a href="<?= base_url() ?>scheduling/instrument_details/<?= $inst_id ?>"><?= $inst_info['instrument_description'] ?></a>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>
