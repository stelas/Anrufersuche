<?php
header("Content-Type: text/xml; charset=utf-8");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n\r\n";

// ----------------------------------------
// Add area code? true or false
$addAreaCode = true;

// Area code - change to your own area code
$areaCode = "030";
// ----------------------------------------

// Remove &, &nbsp; and leading/trailing whitespace
function tidyString($s) {
  $search = array ("\xc2\xa0", "&");
  $replace = array ( '', '');
  return trim(str_replace($search,$replace, $s));
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

// Split 'Street 1 a, 12345  City' into key-value pairs 'st'=>'Street','nr'=>'1a','zc'=>'12345','ct'=>'City'
function splitAddress($address) {
  $res = array('st' => '', 'nr' => '', 'zc' => '', 'ct' => '');
    if (preg_match('/^((.+?) ?(\d+.*),)? ([0-9]+)  (.+)$/', $address, $m)) {		
    $res['st'] = $m[2];
    $res['nr'] = str_replace(' ', '', $m[3]);
    $res['zc'] = $m[4];
    $res['ct'] = strtok($m[5], ','); # Remove suburb from name
  }
  return $res;
}

// Check if mail value is an email
function checkMail($mail) {
  $res = array('em' => '');
    if (preg_match('/(.+@.+\..+)/', $mail, $m)) {		
    $res['em'] = $m[1];
  }
  return $res;
}

// Check if url value ist an url
function checkURL($url) {
  $res = array('url' => '');
    if (preg_match('/(http.+)/', $url, $m)) {		
    $res['url'] = $m[1];
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
    if (array_key_exists('zc', $entry) && !empty($entry['zc']))
      echo '<zc>' . $entry['zc'] . '</zc>';
    if (array_key_exists('ct', $entry) && !empty($entry['ct']))
      echo '<ct>' . $entry['ct'] . '</ct>';
    if (array_key_exists('em', $entry) && !empty($entry['em']))
      echo '<em>' . $entry['em'] . '</em>';
    if (array_key_exists('url', $entry) && !empty($entry['url']))
      echo '<url>' . $entry['url'] . '</url>';
    if (array_key_exists('hm', $entry) && !empty($entry['hm']))
      echo '<hm>' . $entry['hm'] . '</hm>';
    echo '</entry></list>';
  }
}

// Reverse phone number search on DasOertliche.de
function lookupCaller($number) {
  $dom = new DOMDocument();
  if (!@$dom->loadHTML(file_get_contents('https://www.dasoertliche.de/Controller?form_name=search_inv&ph=' . $number)))
    return; # HTML file unparseable
  $xp = new DomXPath($dom);
  $name = tidyString($xp->evaluate('string(//div[@id="entry_1"]//a[normalize-space(@class)]/span[1])'));
  $addr = tidyString($xp->evaluate('string(//div[@id="entry_1"]//address[1])'));
  $mail = tidyString($xp->evaluate('string(//div[@id="entry_1"]//a[2])'));
  $url = tidyString($xp->evaluate('string(//div[@id="entry_1"]//a[2])'));
  if (!preg_match('/(http.+)/', $url)) {		
  	$url = tidyString($xp->evaluate('string(//div[@id="entry_1"]//a[3]/span[1])'));
  }
  return array_merge(splitName($name), splitAddress($addr), checkMail($mail), checkURL($url), array('hm' => $number));
}

// Begin
if (isset($_GET['hm']) && is_numeric($_GET['hm'])) {
  $hm = $_GET["hm"];
if ($addAreaCode && strncmp($hm,"0",1) != 0) {
    $hm = $areaCode.$hm;
}
  $caller = lookupCaller($hm);
  if (is_array($caller))
	  printResponse($caller);
  else {
	  printError(6); # Service not available
}}
else {
	printResponse(NULL);
}
?>