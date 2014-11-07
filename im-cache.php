<?php

include('SimpleImage.php');

/* ----------------- CONFIG ----------------- */

$document_root	= realpath(dirname(__FILE__)); // absolute path where the im-cache script is located - without '/' at the end!
$image_root		= '/home/rocket/ghost'; // absolute path where the original images are located - without '/' at the end!
$cache_path		= "cache"; // where to store the generated re-sized images. Specify from your document root!
$image_quality	= 75; // value between 0-100. 100 mean 100%
$fall_back_width = 660; // fallback parameter value when nothing is set for width
$fall_back_height = null; // fallback parameter value when nothing is set for height

/* ----------------- USAGE ----------------- */
/* 
## URI Path
* the url needs the absolute path to the requested file
  this is done like this: im-cache.php/2014/10/2013-03-21-18-38-12.jpg
  when you use a mod_rewrite the im-cache.php is not needed but you still need the path 

## URI Parameter
* w 	width of resulting image
* h 	height of resulting image

## Documentation
* when width and hight are given it performs a best fit
* when only height or width is given it performs a fit to height/width and keeps aspect ratio
*/
/* ----------------- CODE ----------------- */

$image_folder   = null;
$image_options   = array("width"=>$fall_back_width,"height"=>$fall_back_height,"quality"=>$image_quality);

// read out parameters and path of uri
$url =parse_url(urldecode($_SERVER['REQUEST_URI']));
$image_folder = preg_replace("/^\//", "", str_replace(array(basename($_SERVER["SCRIPT_FILENAME"]), '//'), array('','/'), $url['path']));
if (isset($url['query'])) {
	parse_str($url['query'], $output);
	$image_options["width"] = isset($output["w"]) && is_numeric($output["w"]) ? $output["w"] : $fall_back_width;
	$image_options["height"] = isset($output["h"]) && is_numeric($output["h"]) ? $output["h"] : $fall_back_height;
	$image_options["quality"] = isset($output["q"]) && is_numeric($output["q"]) ? $output["q"] : $image_quality;
}

// cach folder and -file and source-file
$option_folder_name = ($image_options["width"] != null ? $image_options["width"] : "")."x".($image_options["height"] != null ? $image_options["height"] : "");
$cache_file = "$document_root/$cache_path/$option_folder_name/$image_folder";
$source_file = "$image_root/$image_folder";

// does the $cache_path directory exist already?
if (!is_dir("$document_root/$cache_path")) {
	if (!mkdir("$document_root/$cache_path", 0755, true)) {
		if (!is_dir("$document_root/$cache_path")) {
			header("Status: 404 Failed to create cache directory at: $document_root/$cache_path");
 			exit();
		}
	}
}

// check if the file exists at all
if (!file_exists($source_file)) {
	header("Status: 404 $source_file Not Found");
	exit();
}

// create thumb and redirect to it
function handleThumb($thumbSource) {
	global $cache_file, $source_file, $image_options, $document_root;
	
    $cache_file_location = $cache_file;
	$cache_dir = dirname($cache_file);
	
	if ($thumbSource) {
		// does the directory exist already?
		if (!is_dir($cache_dir)) { 
			if (!mkdir($cache_dir, 0755, true)) {
				if (!is_dir($cache_dir)) {
					header("Status: 404 Failed to create cache directory: $cache_dir");
 					exit();
				}
			}
		}
		// check cache folder is writable
		if (!is_writable($cache_dir)) {
			header("Status: 404 The cache directory is not writable: $cache_dir");
 			exit();
		}
		// create thumb related to options
		try {
			$img = new abeautifulsite\SimpleImage($source_file);
			if ($image_options["width"] != null && $image_options["height"] != null) {
				$img->best_fit($image_options["width"], $image_options["height"]);
			} else if ($image_options["width"] != null) {
				$img->fit_to_width($image_options["width"]);
			} else if ($image_options["height"] != null) {
				$img->fit_to_height($image_options["height"]);
			}
			$img->save($cache_file, $image_options["quality"]);
		} catch(Exception $e) {
			header("Status: 404 ImageProcessing: ".$e->getMessage());
			exit();
		}
	} 
	$imgCache = new abeautifulsite\SimpleImage($cache_file_location);
    $imgCache->output();
}

// delete outdated cache-file
if (file_exists($cache_file)) {
	if (filemtime($cache_file) <= filemtime($source_file)) {
		// delete outdated and create new thumb
		unlink($cache_file);
		handleThumb(true);
	}
	// take cache file instead of source_file
	handleThumb(false);
} else {
	// create new thumb
	handleThumb(true);
}