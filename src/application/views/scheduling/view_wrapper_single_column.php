<?php
  $this->load->view('pnnl_template/view_header'); 
?>
<body class="col2">
  <?php $this->load->view('pnnl_template/intranet_banner'); ?>
  <div id="page">
    <?php $this->load->view('pnnl_template/top',$navData['current_page_info']); ?>
    <div id="container">
      <div id="main">
        <div style="position:relative;">
        <?php
          $first_date = new DateTime($calendar_format[0]['date_info']);
          $last_date = new DateTime($calendar_format[6]['date_info']);
          $last_week = clone $first_date;
          $last_week->modify('-7 days');
          $next_week = clone $first_date;
          $next_week->modify('+7 days');
        ?>
          <div style="position:relative;left:50px;">
            <h2 class="underline">
              <a class="week-select-link back" id="week-select_backwards-link" title="Back 1 Week" href="<?= base_url() ?>scheduling/calendar/<?= $current_instrument_id ?>/<?= $last_week->format('Ymd') ?>">&lt;</a>
              <input type="button" class="week-select back" id="week-select-backwards" style="display:none;" alt="Back 1 Week" />
              <span class="date_selector_container" id="date_selector_container">Week of <?= $first_date->format('j M') ?>&ndash;<?= $last_date->format('j M') ?> <?= $first_date->format('Y') ?></span>
              <input type="button" class="week-select forward" id="week-select-forwards" style="display:none;" alt="Ahead 1 Week" />
              <a class="week-select-link back" id="week-select_backwards-link" title="Back 1 Week" href="<?= base_url() ?>scheduling/calendar/<?= $current_instrument_id ?>/<?= $next_week->format('Ymd') ?>">&gt;</a>
            </h2>
          </div>
          <div class="instrument_picker_container" style="position:absolute;right:125px;top:0;">
            <select id="instrument_selector" style="width:200px;" name="instrument_selector">
            <?php foreach($instrument_list as $inst_id => $inst_entry): ?>
              <?php $defaulter = $inst_id == $current_instrument_id ? " selected=\"selected\"" : ""; ?>
              <option value="<?= $inst_id ?>"<?= $defaulter ?>><?= $inst_entry['friendly_name'] ?></option>
            <?php endforeach; ?>
            </select>
          </div>
          <div class="make_res_button buttons" style="position:absolute;top:0;right:0;">
            <a href="<?= base_url() ?>scheduling/reservation" style="font-size:0.75em;">New Reservation</a>
          </div>
        </div>
        <div class="form_container" id="form_container">
        <?php if(!empty($view_name)) $this->load->view($view_name, $this->page_data); ?>
        </div>
      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
<script type="text/javascript">
  var current_include_date = '<?= $include_date ?>';
  var formatted_include_date = '<?= $formatted_include_date ?>';
  var current_instrument_id = '<?= $current_instrument_id ?>';
  var base_url = '<?= base_url() ?>';
</script>
</html>
