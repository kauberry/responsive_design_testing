<?php
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
          <ul id="instrument_listing" style="font-size:1.3em;">            
        <?php foreach($instrument_list as $eus_instrument_id => $inst_info): ?>
            <li><a href="<?= base_url() ?>scheduling/calendar/<?= $eus_instrument_id ?>"><?= $inst_info['friendly_name'] ?></a></li>
        <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>
