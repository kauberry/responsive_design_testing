<!DOCTYPE html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<html>
  <head>
<?php
  $page_header = isset($page_header) ? $page_header : "Untitled Page";
  $title = isset($title) ? $title : $page_header;
?>
    <title>Test Site</title>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <meta  name="description" content="" />
    <meta name="keywords" content="" />
<?php
  $form_object = isset($form_object) ? $form_object : '';
  $table_object = isset($table_object) ? $table_object : "";
  $this->load->view('pnnl_template/globals');
  if(isset($script_uris) && sizeof($script_uris) > 0){
    foreach($script_uris as $uri) {
      echo "  <script type=\"text/javascript\" src=\"{$uri}\"></script>\n";
    }
    echo "\n";
  }

   if(isset($css_uris) && sizeof($css_uris) > 0){
    foreach($css_uris as $css) {
      echo "  <link rel=\"stylesheet\" type=\"text/css\" href=\"{$css}\" />";
    }
    echo "\n";
  }
?>
    <script type="text/javascript">
      var base_url = "<?= base_url() ?>";
    </script>
  </head>
