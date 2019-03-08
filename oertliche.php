<?php

// Remove &nbsp; and leading/trailing whitespace
function tidyString($s) {
  return trim(str_replace("\xc2\xa0", '', $s));
}

// Split 'Lastname [Firstname1 [Firstname2]]' into key-value pairs 'fn'=>'Firstname1 Firstname2','ln'=>'Lastname'
function splitName($name) {
  $res = array('fn' => '', 'ln' => '');
  $a = explode(' ', $name, 2);
  $res['ln'] = $a[0];
  if (count($a) > 1)
    $res['fn'] = $a[1];
  return $res;
}

// Split '[Street 1 a,]12345 City' into key-value pairs 'st'=>'Street','nr'=>'1a','ct'=>'City'
function splitAddress($address) {
  $res = array('st' => '', 'nr' => '', 'ct' => '');
  if (preg_match('/^((.+) (\d+.*),)?(\d+) (.+)$/', $address, $m)) {
    $res['st'] = $m[2];
    $res['nr'] = str_replace(' ', '', $m[3]);
    $res['ct'] = strtok($m[5], ','); # Remove suburb from name
  }
  return $res;
}

// Output Gigaset error response
function printError($id) {
  echo '<error repsonse="get_list" type="pb"><errorid>' . $id . '</errorid></error>';
}

// Output Gigaset phonebook search response
function printResponse($entry) {
  echo '<list response="get_list" type="pb" ';
  // No result:
  if (!is_array($entry) || !array_key_exists('ln', $entry) || empty($entry['ln'])) {
    echo 'notfound="hm" total="0"/>';
  }
  // Person found:
  else {
    echo 'total="1" first="1" last="1"><entry>';
    echo '<ln>' . $entry['ln'] . '</ln>';
    if (array_key_exists('fn', $entry) && !empty($entry['fn']))
      echo '<fn>' . $entry['fn'] . '</fn>';
    if (array_key_exists('st', $entry) && !empty($entry['st']))
      echo '<st>' . $entry['st'] . '</st>';
    if (array_key_exists('nr', $entry) && !empty($entry['nr']))
      echo '<nr>' . $entry['nr'] . '</nr>';
    if (array_key_exists('ct', $entry) && !empty($entry['ct']))
      echo '<ct>' . $entry['ct'] . '</ct>';
    if (array_key_exists('hm', $entry) && !empty($entry['hm']))
      echo '<hm>' . $entry['hm'] . '</hm>';
    echo '</entry></list>';
  }
}

// Reverse phone number search on DasOertliche.de
function lookupCaller($number) {
  $dom = new DOMDocument();
  if (!@$dom->loadHTML(file_get_contents('http://www.dasoertliche.de/Controller?form_name=search_inv&ph=' . $number)))
    return;
  $xp = new DomXPath($dom);
  $name = tidyString($xp->evaluate('string(//div[@id="entry_1"]//a[normalize-space(@class)="name"]/span[1])'));
  $addr = tidyString($xp->evaluate('string(//div[@id="entry_1"]//address[1])'));
  return array_merge(splitName($name), splitAddress($addr), array('hm' => $number));
}

header('Content-Type: text/xml; charset=utf-8');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n\r\n";
if (isset($_GET['hm']) && is_numeric($_GET['hm'])) {
  $caller = lookupCaller($_GET['hm']);
  if (is_array($caller))
    printResponse($caller);
  else
    printError(6); # Service not available
}
else {
  printResponse(NULL);
}

?>
