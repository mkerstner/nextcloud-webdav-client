<?php

require_once 'nextcloud-webdav-client.php';

// example init
$client = NCWebDavClient::initClient('user', 'pass', 'https://cloud.mydomain.com', true);

// request specific file and stop
if (isset($_GET['file'])) {
	$client->sendFile($_GET['file']);
	exit;
}

// get folder contents
$contents = $client->getFolderContents('test');

// display returned folder contents
foreach ($contents as $k => $v) {
	echo 'Found ' . $v . "\n";
}
