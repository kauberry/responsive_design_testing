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
        
        <h1><?= $page_header ?></h1>
        <div class="edit_block">
          <form id="ticket_filter" class="themed">
            <div class="full_width_block">
              <div class="left_block" style="width:47%;">
<?php print(format_filter_block("status", $ticket_counts["state"])); ?>
              </div>
              <div class="right_block" style="width:48%;">
<?php print(format_filter_block("priority", $ticket_counts["priority"])); ?>
              </div>
            </div>
            <div class="full_width_block" style='display:none;'>
              <fieldset id="magres_ticket_filters_by_name" style='margin-top:0.75em;'>
                <legend style='font-size:medium;'>Filter by Contents</legend>
                <div class='search_field' style='margin-bottom:1em;'>
                  <input style='width:100%;' type='search' results='5' autosave='saved_content_searches' name='contents_search' id='contents_search' placeholder='Filter by Contents...' />
                </div>                
              </fieldset>
            </div>
          </form>
        </div>
        <div class='form_container'>
          <div id='table_container'>
            <?= $table_object?>
          </div>
        </div>
      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>


