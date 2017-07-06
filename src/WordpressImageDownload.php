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

		$image_data = wp_remote_get(
			$this->external_url,
			array(
				'timeout' => 10
			)
		);

		if (is_wp_error($image_data)){
			error_log(print_r($image_data->get_error_messages(), true));
			return false;
		}

		$file_extension = self::get_file_extension_from_mimetype($image_data['headers']['content-type']);

		// We need to optionally add a valid file extension - Wordpress checks for a valid file using the extension only.
		$full_file_name = rtrim($image_name, '.' . $file_extension) . '.' . $file_extension;

		// Create the image file on the server
		$attachment = wp_upload_bits($full_file_name, null, wp_remote_retrieve_body($image_data));

		// Return if errors
		if (!empty($attachment['error'])) {
			error_log(print_r($attachment['error'], true));
			return false;
		}

		$filepath = $attachment['file'];
		$filename = basename($attachment['file']);

		$wp_filetype = wp_check_filetype($filename, null);

		// Set attachment data
		$post_info = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($filename),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		// Create the attachment
		$attach_id = wp_insert_attachment($post_info, $filepath);

		// Set metadata and create thumbnails
		if( !function_exists( 'wp_generate_attachment_data' ) ){
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		}

		$attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
		wp_update_attachment_metadata($attach_id, $attach_data);

		return $attach_id;

	}

	///////////////
	// Protected //
	///////////////

	public static function get_file_extension_from_mimetype($mimetype){

		$extensions = self::get_mime_types();

		if (isset($extensions[$mimetype])){
			return $extensions[$mimetype];
		}

	}

	protected function get_image_data($image_url){

		$curl = new Curl;

		$curl->setOpt(CURLOPT_FOLLOWLOCATION, true); // typically you will want to follow redirects

		if ($this->user_agent){
			$curl->setUserAgent($this->user_agent); // the default WordPress user agent may be rejected by certain servers
		}

		$response = $curl->get($image_url);

		return $response;

	}

	public static function get_mime_types(){

		return array(
			"image/bmp" => "bmp",
			"image/jpeg" => "jpg",
			"image/pict" => "pic",
			"image/png" => "png",
			"image/tiff" => "tiff",
			"image/gif" => "gif",
			"image/svg+xml" => "svg",
		);

	}


}
