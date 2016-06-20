<?php

namespace bermanco\WordpressImageDownload;

class WordpressImageDownload {

	public $external_url;

	public function __construct($url){

		if (filter_var($url,FILTER_VALIDATE_URL)){
			$this->external_url = $url;
		}
		else {
			error_log('Invalid URL passed to WordPressImageDownload constructor');
		}

	}

	public function create_media_attachment(){

		if ($this->external_url){

			$url = $this->external_url;
			$tmp = download_url( $url );

			$file_array = array(
				'name' => basename( $url ),
				'tmp_name' => $tmp
			);

			// Check for download errors
			if ( is_wp_error( $tmp ) ) {
				return $tmp->get_error_message();
			}

			$id = media_handle_sideload( $file_array, 0 );

			// Check for handle sideload errors.
			if ( is_wp_error( $id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $id;
			}

			return $id;

		}

	}

	protected function get_file_extension($file){
		return pathinfo($file['name'], PATHINFO_EXTENSION);
	}

}

