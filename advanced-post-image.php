<?php
/*
Plugin Name: Advanced Post Image
Version: 0.21
Plugin URI: http://fairyfish.net/2007/10/16/advanced-post-image-plugin/
Description: Advanced Post Images plugin can generate thumbnails of the first image of latest (number is set by user) posts and link back to the them.
Author: Denis
Author URI: http://fairyfish.net/
*/


$thumn_path = ABSPATH . "wp-content/uploads/thumb/";
$prefix = "thumb_";

function get_post_image_list($image_number = 10,$thumb_width = 50,$thumb_height = 50,$type="recent") {
	global $wpdb;
	$sql = "SELECT ID, post_title, post_content FROM $wpdb->posts WHERE post_content LIKE ('%<img%') AND post_status = 'publish' AND post_type ='post' ";
	if($type=="recent"){
		$sql .= "ORDER BY post_date DESC ";
	}elseif($type=="random"){
		$sql .= "ORDER BY RAND() ";
	}
	$sql .= "LIMIT $image_number";
	
	$posts = $wpdb->get_results($sql);	
	
	if( $posts ) { 
		echo '<ul class="post-image-list">';
		foreach( $posts as $post ) {
			$content = $post-> post_content;
			
			preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post->post_content, $matches);			
			
			$img_src = $matches[1][0]; //first photo
			$img_name = get_img_name($img_src);
			$img_thumb = get_thumb_img($img_name);
			$img_link = str_replace(ABSPATH, get_settings('siteurl')."/", $img_thumb);

			@$fp = fopen($img_thumb,'r');  
			if   (!$fp && generate_thumb_image($img_src,$thumb_width,$thumb_height) == 0){
				$img = '<img src="' . $img_src . '" alt="'. wptexturize($post->post_title) . '" height="'.$thumb_height.'" />';
			} else {
				$img = '<img src="' . $img_link . '" alt="'. wptexturize($post->post_title) . '" />';
			}
			echo '<li><a href="'.get_permalink($post->ID).'" title="'. wptexturize($post->post_title).'">'. $img .'</a></li>';
		} 
		echo '</ul>';
	}
}
	
function generate_thumb_image($img_src, $thumb_width, $thumb_height){
	$img_name = get_img_name($img_src);
	$img_thumb = get_thumb_img($img_name);
	
	if(file_exists($img_thumb) ) {
        return 1;
	}
	
	list($im_width, $im_height, $type) = @getimagesize($img_src);
	
	switch ($type){
		case 1:
			$im = @imageCreateFromGIF($img_src);
			break;
		case 2:
			$im = @imageCreateFromJPEG($img_src);
			break;
		case 3:
			$im = @imageCreateFromPNG($img_src);
			break;
		default:
			return 0;
	}
	
	if($thumb_width == 0 && $thumb_height == 0){
		$thumb_height == 50;
		$thumb_width = $thumb_height / $im_height * $im_width;
	} elseif($thumb_width == 0){
		$thumb_width = $thumb_height / $im_height * $im_width;
	} elseif( $thumb_height == 0){
		$thumb_height = $thumb_width / $im_width * $im_height;
	}	
	
	$to = imagecreatetruecolor($thumb_width, $thumb_height);
    if(!$to) {
        return 0;
	}             
            
    if(!imagecopyresampled($to, $im, 0, 0, 0, 0, $thumb_width, $thumb_height, $im_width, $im_height)) {
        return 0;
    }

	switch ($type){
		case 1:
			imageGIF($to, $img_thumb);
			break;
		case 2:
			imageJPEG($to, $img_thumb);
			break;
		case 3:
			imagePNG($to, $img_thumb);
			break;
		default:
			return 0;
	}
	
	imageDestroy($im);
	
	return 1;
}

function get_thumb_img($img_name){
	global $thumn_path, $prefix;
	return $thumn_path.$prefix.$img_name;
}

function get_img_name($img_src){
	return substr($img_src,strrpos($img_src,"/")+1);
}
?>