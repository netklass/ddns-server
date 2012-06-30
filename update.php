<?
 
$hostname    =    'myHostName';
$pingserver  =    'http://ip.domain.tld/';
$root        =    dirname(__FILE__);
$ttl         =    60;
 
$key         =    'domain.tld';
$secret      =    'SecretKeyAusK*.private';
 
$zones        =    array(
   'domain.tld'    =>    array(
     'pc1',
     'pc2',
     'webcam1',
     'webcam2'
)
);
 
$nameservers    =    array(
'ns1.domain.tld'
);
 
function getIP($link, $key) {
  $session = curl_init($link);
  curl_setopt($session, CURLOPT_HEADER, false);
  curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($session, CURLOPT_USERAGENT, 'DNS Ping '.$key.'/'.time());
  $response = curl_exec($session);
  curl_close($session);
  return $response;
}
 
$current_ip = getIP($pingserver, $hostname);
 
if (file_exists($root."/ip.tmp")) $old_ip = implode("", file($root."/ip.tmp")); else $old_ip = '0';
 
if ($current_ip != $old_ip) {
  echo "IP Change (old: ".$old_ip." / new: ".$current_ip.")\n";
  $nsupdate = popen("/usr/bin/nsupdate -y ".$key.":".$secret, "w");
  foreach($nameservers as $ns) {
    echo "contact ".$ns." ...\n";
    fwrite($nsupdate, "server ".$ns."\n");
    foreach($zones as $zone => $hosts) {
      echo "set zone ".$zone." ...\n";
      fwrite($nsupdate, "zone ".$zone."\n");
      foreach($hosts as $host) {
        echo "update host ".$host.".".$zone." to ".$current_ip." TTL ".$ttl." ...\n";
        fwrite($nsupdate, "update delete ".$host.".".$zone.". A\n");
        fwrite($nsupdate, "update add ".$host.".".$zone.". 60 A ".$current_ip."\n");
      }
      fwrite($nsupdate, "send\n");
    }
  }
  fwrite($nsupdate, "quit");
  echo "update done.\n";
  pclose($nsupdate);
  $fp = fopen($root."/ip.tmp", "w+");
  fwrite($fp, $current_ip);
  fclose($fp);
}
?>
