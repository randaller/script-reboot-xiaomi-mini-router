<?php
$pwd   = "my_xiaomi_mini_admin_password";	  // Change to yours xiaomi mini admin password
$key   = "a2ffa5c9be07488bbb04a3a47d3c5f6a";	  // Get this key: go to router login page - view source - on http://192.168.31.1
$mt    = ceil( microtime( TRUE ) );
$nonce = "0_ab:3d:cd:02:ef:84_" . $mt . "_8615";  // ab:3d:...:84 MAC-address of computer where this script running - change it to yours
$pass  = sha1( $nonce . sha1( $pwd . $key ) );

// mine router at 192.168.31.1 - change it to yours everywhere in file (Ctrl+R)

$query = "POST http://192.168.31.1/cgi-bin/luci/api/xqsystem/login HTTP/1.1
Host: 192.168.31.1
Connection: keep-alive
Content-Length: 126
Accept: */*
Origin: http://192.168.31.1
X-Requested-With: XMLHttpRequest
User-Agent: Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36
Content-Type: application/x-www-form-urlencoded; charset=UTF-8
Referer: http://192.168.31.1/cgi-bin/luci/web/home
Accept-Encoding: gzip, deflate
Accept-Language: en-US,en;q=0.9,ru;q=0.8
Cookie: psp=admin|||2|||0

username=admin&password={$pass}&logtype=2&nonce=" . urlencode( $nonce );

echo "[" . date( "r" ) . "] Sending reboot command...\r\n";

$fp = fsockopen( "192.168.31.1", 80, $errno, $errstr, 30 );
if ( !$fp )
{
	echo "$errstr ($errno)\r\n";
	die( 0 );
}

// send login query
$result = "";
fwrite( $fp, $query );
while ( !feof( $fp ) )
{
	$result .= fgets( $fp, 128 );
}
fclose( $fp );

// decode LUCI token
preg_match_all( "/{.*}/siU", $result, $matches );
$json  = $matches[0][0];
$json  = json_decode( $json );
$token = $json->token;

echo "[" . date( "r" ) . "] Token: " . $token . "\r\n";

// wait while openwrt works with new issued token
sleep( 3 );

$query = "GET http://192.168.31.1/cgi-bin/luci/;stok={$token}/api/xqsystem/reboot?client=web HTTP/1.1
Host: 192.168.31.1
Connection: keep-alive
Accept: application/json, text/javascript, */*; q=0.01
X-Requested-With: XMLHttpRequest
User-Agent: Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36
Referer: http://192.168.31.1/cgi-bin/luci/;stok=$token/web/home
Accept-Encoding: gzip, deflate
Accept-Language: en-US,en;q=0.9,ru;q=0.8
Cookie: psp=admin|||2|||0\r\n\r\n";

$fp = fsockopen( "192.168.31.1", 80, $errno, $errstr, 30 );
if ( !$fp )
{
	echo "$errstr ($errno)\r\n";
	die( 0 );
}

// send reboot query
$result = "";
fwrite( $fp, $query );
while ( !feof( $fp ) )
{
	$result .= fgets( $fp, 128 );
}
fclose( $fp );

echo "\r\n" . $result;

// watch for success in manual mode
sleep( 2 );
