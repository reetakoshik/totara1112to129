<?php

function theme_golearningzone_pluginfile(
    $course, 
    $cm, 
    $context, 
    $filearea, 
    $args, 
    $forcedownload, 
    array $options = []
) {
    $theme = theme_config::load('golearningzone');
    return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
}

/**
 * Makes our changes to the CSS
 *
 * @param string $css
 * @param theme_config $theme
 * @return string
 */
function theme_golearningzone_process_css($css, $theme) 
{
	$css = str_replace('[[setting:red_r]]', 212, $css);
	$css = str_replace('[[setting:red_g]]', 20, $css);
	$css = str_replace('[[setting:red_b]]', 90, $css);

	$css = str_replace('[[setting:green_r]]', 52, $css);
	$css = str_replace('[[setting:green_g]]', 199, $css);
	$css = str_replace('[[setting:green_b]]', 177, $css);

	$css = str_replace('[[setting:blue_r]]', 0, $css);
	$css = str_replace('[[setting:blue_g]]', 175, $css);
	$css = str_replace('[[setting:blue_b]]', 248, $css);

	$css = str_replace('[[setting:orange_r]]', 251, $css);
	$css = str_replace('[[setting:orange_g]]', 176, $css);
	$css = str_replace('[[setting:orange_b]]', 59, $css);

	if (isset($theme->settings->themecustompalette)) {
		$customeColor = hex2rgb($theme->settings->themecustompalette);
		$css = str_replace('[[setting:custom_r]]', $customeColor[0], $css);
		$css = str_replace('[[setting:custom_g]]', $customeColor[1], $css);
		$css = str_replace('[[setting:custom_b]]', $customeColor[2], $css);
	}
	
    return $css;
}

function hex2rgb($hex) 
{
    $hex = str_replace("#", "", $hex);

	if (strlen($hex) == 3) {
		$r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
		$g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
		$b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
	} else {
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
	}

	$rgb = array($r, $g, $b);
	//return implode(",", $rgb); // returns the rgb values separated by commas
	return $rgb; // returns an array with the rgb values
}
