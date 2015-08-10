<?php
  $this->load->view('pnnl_template/view_header'); 
  $level_list = $this->sched->get_user_classifications(TRUE);
?>
<body class="col2">
  <?php $this->load->view('pnnl_template/intranet_banner'); ?>
  <div id="page">
    <?php $this->load->view('pnnl_template/top',$navData['current_page_info']); ?>
    <div id="container">
      <div id="main">        
        <h1 class="underline"><?= $page_header ?></h1>
        <div class="form_container">
          <table>
            <tr>
              <th>Name</th>
              <th>PNNL Network ID</th>
              <th>EMSL User ID</th>
              <th>Access Level</th>
            </tr>
          <?php foreach($user_list as $eus_id => $info): ?>
            <tr id="user_info_<?= $eus_id ?>">
              <td><?= $info['first_name'] ?> <?= $info['last_name'] ?></td>
              <td><?= $info['pnnl_network_id'] ?></td>
              <td><?= $info['eus_user_id'] ?></td>
              <?php if($this->scheduling_access_level > 1400): ?>
              <td><?= form_dropdown("access_level_{$info['eus_user_id']}", $level_list, $info['user_classification_level'], "id='access_level_{$info['eus_user_id']}'") ?></td>
              <?php else: ?>
              <td><?= $info['user_classification'] ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          </table>
          <?php if($this->scheduling_access_level > 400): ?>
          <div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php $this->load->view('pnnl_template/view_footer'); ?>
  </div>
</body>
</html>
