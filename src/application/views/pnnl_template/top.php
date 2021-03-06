<?php
  $basedir = $this->config->item('base_dir');
  $uri_string = $this->uri->uri_string();
  // $banner_file = $uri == rtrim("./{$basedir}","/") ? $this->config->item("site_{$this->site_id}_main_banner_filename") : $this->config->item("site_{$this->site_id}_small_banner_filename");
  $banner_file = empty($uri_string) ? $this->config->item("site_{$this->site_id}_main_banner_filename") : '';
  $site_label = $this->config->item('site_label');
  $banner_path = site_url()."images/".$banner_file;
  if(!empty($banner_file)){
    $banner_size_info = getimagesize(dirname($_SERVER['SCRIPT_FILENAME'])."/images/".$banner_file);
    $banner_dimensions = $banner_size_info[3];
  }
?>
<div id="topBanner">
  <a id="pnnlLogo" href="http://www.pnl.gov/">Pacific Northwest National Laboratory</a>
  <a id="skipNav" href="#main"><span>Skip to Main Content</span></a>
  <a id="doeLink" href="http://www.energy.gov/">U.S. Department of Energy</a>
  
  <div id="siteNav">
    <form method="get" action="https://search.pnl.gov/search" name="pnnlSearch" id="pnnlSearch">    
          <input type="hidden" name="as_sitesearch" value="" />
          <input type="hidden" name="access" value="a" />
          <input type="hidden" name="output" value="xml_no_dtd" />
          <input type="hidden" name="client" value="default_frontend" />
          <input type="hidden" name="site" value="default_collection" />
          <input type="hidden" name="proxystylesheet" value="default_frontend" />
          <input type="hidden" name="as_dt" value="i" />    

      <label for="q">Search PNNL Intranet</label>
      <input type="search" name="q" id="q" maxlength="2047" value="" />
      <!-- <button class="copper">Contact Us</button> -->
      <input type="image" id="searchSubmit" src="/resources/images/search_button@2x.png" width="23px" height="22px" alt="Search" />
    </form>
    
    <ul id="pnnlNav">
      <li><a href="http://labweb.pnl.gov/default.aspx" id="home">LabWeb</a></li>
      <li><a href="http://labweb.pnl.gov/topicindex/" id="topicindex">Topic Index</a></li>
      <li><a href="http://sbms.pnl.gov" id="sbms">SBMS</a></li>
      <li><a href="http://www.pnl.gov" id="pnnl_home">PNNL External</a></li>
    </ul>
  </div>
</div>                
<div class='buttons' style="position:relative;z-index:100;">
  <div style="float:right"><input type="button" value="Back" id="back_button" onclick="javascript:history.go(-1);"/></div>
</div>
<div id="subBanner" style="position:relative;">
  <?php if(empty($banner_file)): ?>
  <div class="banner_bar_background">
    <div class="banner_bar banner_bar_left banner_bar_<?= $this->site_color ?>">
      <div class='user_login_info'>Signed in as: <?= $logged_in_user ?></div>
    </div>
    <div class="banner_bar banner_bar_right banner_bar_grey">
      <div id="site_label"><?= $site_label ?></div>
    <?php if($_SERVER["SERVER_NAME"] == "wfdev30c.pnl.gov"): ?>
      <div id="site_status_notification">Development Version</div>
    <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
    <div class='user_login_info'>Signed in as: <?= $logged_in_user ?></div>
    <img src="<?=$banner_path ?>" <?=$banner_dimensions?> alt="<?= $this->site_info['description'] ?>" />    
  <?php endif; ?>
</div>