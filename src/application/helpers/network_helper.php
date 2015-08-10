<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

function verify_macaddr_format($macaddr){
  //echo "mac address => ".$macaddr;
  $pattern = '/^([[:xdigit:]]{2})[-:]?([[:xdigit:]]{2})[-:]?([[:xdigit:]]{2})[-:]?([[:xdigit:]]{2})[-:]?([[:xdigit:]]{2})[-:]?([[:xdigit:]]{2})$/i';

  $matches = array();
  $v_macaddr = '';
  $match_results = preg_match($pattern,$macaddr,$matches);
  if($match_results || $match_results > 0) {
    for($i=1;$i<7;$i++){
      if(strLen($v_macaddr)>0) $v_macaddr .= ':'; 
      $v_macaddr .= $matches[$i];
    }
  }else{
    $v_macaddr = false;
  }
  return strtolower($v_macaddr);
}

function verify_ip_address_format($ip){
  $long_ip = ip2long($ip);
  if(!$long_ip) {return false;}
  $v_ip = long2ip($long_ip);
  return $v_ip;
}

function transmit_array_with_json_header($response, $statusMessage = '', $operationSuccessful = true, $status_code = -1) {
  $CI =& get_instance();
  header("Content-type: text/json");
  if($status_code > 0){
      $CI->output->set_status_header($status_code);
  }else{
    if($operationSuccessful){
      $CI->output->set_status_header(200);
    }else{
      $CI->output->set_status_header(500, $statusMessage);
    }
  }
  $headerArray = array();
  $headerArray['status'] = $operationSuccessful ? "ok" : "fail"; 
  $headerArray['message'] = !empty($statusMessage) ? $statusMessage : "";
  header("X-JSON: (".json_encode($headerArray).")");
  
  $response = !is_array($response) ? array('results' => $response) : $response;
  
  if(is_array($response) && sizeof($response) > 0){
    print(json_encode($response));
  }else{
    print("0");
  }
}

function format_array_for_select2($response){
  header("Content-type: text/json");
  
  $results = array();
  
  foreach($response['items'] as $id => $text){
    $results[] = array('id' => $id, 'text' => $text);
  }
  
  $ret_object = array(
    'total_count' => sizeof($results),
    'incomplete_results' => FALSE,
    'items' => $results
  );
  
  print(json_encode($ret_object));
  
}

function format_eus_user_list_for_select2($response){
  header("Content-type: text/json");
  
  $results = array();
  
  foreach($response['names'] as $eus_id => $user_info){
    $results[] = array('id' => $user_info['eus_id'], 'text' => "{$user_info['first_name']} {$user_info['last_name']}");
    $ret_object = array(
      'total_count' => sizeof($results),
      'incomplete_results' => FALSE,
      'items' => $results
    );
  }
  print(json_encode($ret_object));
}

?>