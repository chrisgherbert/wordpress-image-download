# wordpress-image-download
Class to help create a WordPress attachment from a URL. Example use:

```php

use chrisgherbert\WordpressImageDownload\WordpressImageDownload;

$downloader = new WordpressImageDownload($image_url);

$attachment_id = $downloader->create_media_attachment();

if ($attachment_id){
	set_post_thumbnail($wordpress_post_id, $attachment_id);
}

```
