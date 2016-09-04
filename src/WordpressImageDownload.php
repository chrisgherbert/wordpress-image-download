<?php

namespace bermanco\WordpressImageDownload;

use Curl\Curl;

class WordpressImageDownload {

	public $external_url;
	public $user_agent;

	public function __construct($url){

		if (filter_var($url,FILTER_VALIDATE_URL)){
			$this->external_url = $url;
		}
		else {
			error_log('Invalid URL passed to WordPressImageDownload constructor');
		}

	}

	public function set_user_agent($user_agent){
		$this->user_agent = $user_agent;
	}

	public function create_media_attachment(){

		$image_name = basename($this->external_url);
		$upload_dir = wp_upload_dir();
		$image_data = $this->get_image_data($this->external_url);
		$unique_file_name = wp_unique_filename($upload_dir['path'], $image_name);
		$filename = basename($unique_file_name);

		// Check folder permission and define file location
		if( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}

		// Create the image file on the server
		file_put_contents($file, $image_data);

		$wp_filetype = wp_check_filetype($filename, null);

		// Set attachment data
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($filename),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		// Create the attachment
		$attach_id = wp_insert_attachment($attachment, $file);

		return $attach_id;

	}

	///////////////
	// Protected //
	///////////////

	protected function get_image_data($image_url){

		$curl = new Curl;

		$curl->setOpt(CURLOPT_FOLLOWLOCATION, true); // typically you will want to follow redirects

		if ($this->user_agent){
			$curl->setUserAgent($this->user_agent); // the default WordPress user agent may be rejected by certain servers
		}

		$response = $curl->get($image_url);

		return $response;

	}

}
