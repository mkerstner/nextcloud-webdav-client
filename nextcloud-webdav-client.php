<?php

use Sabre\DAV\Client;

include 'vendor/autoload.php';

/**
 * NextCloud WebDAV client. Abstraction layer that uses sabre/webdav for the
 * actual WebDAV communication.
 *
 * In order to pass PHP GET params to specify a requested 'file' via the CLI
 * use php-cgi, e.g.:
 *
 * php-cgi -f path/to/this/file file=some-file
 *
 * @author Matthias Kerstner <matthias@kerstner.at>
 * @version 1.0.0
 * @link http://sabre.io/dav/davclient/
 * @link https://docs.nextcloud.com/server/13/user_manual/files/access_webdav.html
 * @see example.php for usage
 * @requires sabre/webdav ~3.2.0
 */
class NCWebDavClient {

	/* @var Boolean $debug_on Sets debug mode */
	private $debug_on;
	/* @var String $webdav_user WebDAV user name */
	private $webdav_user;
	/* @var String $webdav_pass WebDAV user password */
	private $webdav_pass;
	/* @var String $webdav_domain WebDAV base domain */
	private $webdav_domain;
	/* @var String $webdav_basepath_prefix NextCloud WebDAV base path prefix */
	private $webdav_basepath_prefix = 'remote.php/dav/files/';

	/**
	 * Default constructor.
	 * Disabled by changing scope to private. Use initClient instead.
	 * @see initClient
	 */
	private function __construct() {}

	/**
	 * Initialize client. Replacement for default constructor.
	 * @param String $user @see $webdav_user
	 * @param String $pass @see $webdav_pass
	 * @param String $domain @see $webdav_domain
	 * @param Boolean $debug @see debug_on
	 * @return Object WebDavClient
	 */
	public static function initClient($user, $pass, $domain, $debug = false) {
		$client = new NCWebDavClient();
		$client->webdav_user = $user;
		$client->webdav_pass = $pass;
		$client->webdav_domain = $domain;
		$client->debug_on = $debug;
		return $client;
	}

	/**
	 * Returns NextCloud WebDAV base path.
	 * @return String
	 */
	public function getBasePath() {
		return $this->webdav_basepath_prefix . $this->webdav_user . '/';
	}

	/**
	 * Returns NextCloud WebDAV base URI.
	 * @return String
	 */
	public function getBaseURI() {
		return $this->webdav_domain . '/' . $this->getBasePath();
	}

	/**
	 * Returns NextCloud WebDAV client.
	 * @return Object WebDavClient
	 */
	private function getClient() {
		$settings = array(
			'baseUri' => $this->getBaseURI(),
			'userName' => $this->webdav_user,
			'password' => $this->webdav_pass,
		);

		$client = new Client($settings);

		// optionally show options available for client
		//$options = $client->options();
		//var_dump($options);

		return $client;
	}

	/**
	 * Sends requested $filePath as HTTP response. Automatically sets content-type.
	 * @param String $filePath Path to file based on @see getBasePath
	 */
	public function sendFile($filePath) {

		$client = $this->getClient();

		$contents = $client->request('GET', $filePath);

		if ((int) $contents['statusCode'] !== 200) {
			echo "404\n";
			return;
		}

		if ($this->debug_on) {
			var_dump($contents);
			var_dump($contents['headers']['content-type'][0]);
			var_dump($contents['body']);
		}

		header('Content-Type: ' . $contents['headers']['content-type'][0]);

		//Use Content-Disposition: attachment to specify the filename
		header('Content-Disposition: attachment; filename=' . basename($filePath));

		//No cache
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		//Define file size
		header('Content-Length: ' . $contents['headers']['content-length'][0]);

		echo $contents['body'];
	}

	/**
	 * Returns folder contents as array.
	 * @param String $baseFolder Base folder to recursively iterate through.
	 * @return array
	 */
	public function getFolderContents($baseFolder) {

		$client = $this->getClient();

		$response = $client->propFind($baseFolder, array(
			'{DAV:}displayname',
			'{DAV:}getlastmodified',
			'{DAV:}getcontenttype',
		), 1);

		if ($this->debug_on) {
			var_dump($response);
		}

		$matches = array();

		foreach ($response as $idx => $item) {

			if (str_replace($this->getBasePath() . $baseFolder, '', $idx) == '//') {
				continue; // ignore $baseFolder in list
			}

			$matches[] = $idx; // TODO: handle recursion here
		}

		return $matches;
	}
}
