<?php
//header("Content-Type: image/png");

$bg_gray = "../pixmaps/bg_bar_gray.png";
$height = 16;
$width = 59;

$value = (is_numeric($_GET["value"])) ? $_GET["value"] : 0;
$value2 = (is_numeric($_GET["value2"])) ? $_GET["value2"] : -1;
$max = (is_numeric($_GET["max"])) ? $_GET["max"] : 10;
$grrange = ($_GET["range"] == "1") ? true : false;

$bluerange = array(
    "B5CDD7",
    "B5CDD7",
    "B0C8D3",
    "A9C2CE",
    "A1BAC6",
    "98B0BF",
    "8EA8B7",
    "849EAF",
    "7B95A8",
    "738DA0",
    "6C879B"
);
$greenredrange = array(
    "44DC16",
    "44DC16",
    "4FCF15",
    "5EBC14",
    "70A613",
    "858E12",
    "9A7310",
    "AF5A0F",
    "C4410E",
    "D52B0C",
    "E5190C"
);

$w = round(($value / $max) * ($width - 2) , 0);

if ($w > ($width - 2)) $w = $width - 2;
$index = ($value > 10) ? 10 : $value;
$color = ($grrange) ? $greenredrange[$index] : $bluerange[$index];

$red = hexdec(substr($color, 0, 2));
$green = hexdec(substr($color, 2, 2));
$blue = hexdec(substr($color, 4, 2));
/*
$fill = ImageColorAllocate($im, $red, $green, $blue);

//$im = imagecreatefrompng($bg_gray);
*/
if ($value2<0) {
	//ImageFilledRectangle($im, 1, 1, $w, $height - 2, $fill);
	//imagefilledrectangle($im, 1, 1, $w, imagesy($im)-3, $fill);
	
} else {
	$h = ($height - 2)/2;
	//ImageFilledRectangle($im, 1, 1, $w, $h, $fill);
	//imagefilledrectangle($im, 1, 1, $w, imagesy($im)-3, $fill);
	// second bar
	$w2 = round(($value2 / $max) * ($width - 2) , 0);
	if ($w2 > ($width - 2)) $w2 = $width - 2;
	$index2 = ($value2 > 10) ? 10 : $value2;
	$color2 = ($grrange) ? $greenredrange[$index] : $bluerange[$index];
	$red2 = hexdec(substr($color2, 0, 2));
	$green2 = hexdec(substr($color2, 2, 2));
	$blue2 = hexdec(substr($color2, 4, 2));
	//$fill2 = ImageColorAllocate($im, $red2, $green, $blue);
	//ImageFilledRectangle($im, 1, $h+1, $w, $height - 2, $fill2);
	//imagefilledrectangle($im, 1, $h+1, $w, imagesy($im)-3, $fill2);
	//echo "w:$w color:$color w2:$w2 color2:$color2";
	//exit;
}

//imagepng($im);
//imagedestroy($im);

header("Content-Type: image/png");
$im = imagecreatefrompng($bg_gray);
if (empty($_GET['alpha'])) { $_GET['alpha'] = 10; }
$color = imagecolorallocatealpha($im, $red, $green, $blue, $_GET['alpha']);
if ($value2<0) {
	imagefillalpha($im, $color, $w);
} else {
	imagefillalpha($im, $color, $w, 1);
	$color2 = imagecolorallocatealpha($im, $red2, $green2, $blue2, $_GET['alpha']);
	imagefillalpha($im, $color2, $w2, 2);
}

$font = imageloadfont("proggyclean.gdf");
if ($value2<0) {
	$fontcolor = ($value > 5) ? $white : $black;
	$xx = ($value > 9) ? 10 : 3;
	ImageString($im, $font, ($width / 2) - $xx, 2, $value, $fontcolor);
	//ImageString($im, $font, 10, 2, $value."/".$max, $fontcolor);
} else {
	$txt = $value."->".$value2;
	$fontcolor = $black;
	$xx = ($value > 9) ? 20 : 15;
	ImageString($im, $font, ($width / 2) - $xx, 2, $txt, $fontcolor);
}

imagepng($im);
imagedestroy($im);

function ImageFillAlpha($image, $color, $w, $div = 0)
{
	if ($div == 1)
		imagefilledrectangle($image, 1, 1, $w, (imagesy($image)/2)-3, $color);
	elseif ($div == 2)
		imagefilledrectangle($image, 1, (imagesy($image)/2)-2, $w, imagesy($image)-3, $color);
	else
		imagefilledrectangle($image, 1, 1, $w, imagesy($image)-3, $color);
}




/*
$im = ImageCreate($width, $height);
$white = ImageColorAllocate($im, 255, 255, 255);
$black = ImageColorAllocate($im, 0, 0, 0);
$gray = ImageColorAllocate($im, 140, 140, 140);
// Create initial image w/borders
ImageFilledRectangle($im, 0, 0, $width, $height, $white);
ImageRectangle($im, 0, 0, $width - 1, $height - 1, $gray);
// bar
$w = round(($value / $max) * ($width - 2) , 0);
if ($w > ($width - 2)) $w = $width - 2;
$index = ($value > 10) ? 10 : $value;
$color = ($grrange) ? $greenredrange[$index] : $bluerange[$index];
$red = hexdec(substr($color, 0, 2));
$green = hexdec(substr($color, 2, 2));
$blue = hexdec(substr($color, 4, 2));
$fill = ImageColorAllocate($im, $red, $green, $blue);
if ($value2<0) {
	ImageFilledRectangle($im, 1, 1, $w, $height - 2, $fill);
} else {
	$h = ($height - 2)/2;
	ImageFilledRectangle($im, 1, 1, $w, $h, $fill);
	// second bar
	$w = round(($value2 / $max) * ($width - 2) , 0);
	if ($w > ($width - 2)) $w = $width - 2;
	$index = ($value2 > 10) ? 10 : $value2;
	$color = ($grrange) ? $greenredrange[$index] : $bluerange[$index];
	$red = hexdec(substr($color, 0, 2));
	$green = hexdec(substr($color, 2, 2));
	$blue = hexdec(substr($color, 4, 2));
	$fill2 = ImageColorAllocate($im, $red, $green, $blue);
	ImageFilledRectangle($im, 1, $h+1, $w, $height - 2, $fill2);
}
// value
$font = imageloadfont("proggyclean.gdf");
if ($value2<0) {
	$fontcolor = ($value > 5) ? $white : $black;
	$xx = ($value > 9) ? 10 : 3;
	ImageString($im, $font, ($width / 2) - $xx, 2, $value, $fontcolor);
} else {
	$txt = $value."->".$value2;
	$fontcolor = $black;
	$xx = ($value > 9) ? 20 : 15;
	ImageString($im, $font, ($width / 2) - $xx, 2, $txt, $fontcolor);
}
*/
/*
header("Content-type: image/png");
imagepng($im);
ImageDestroy($im);
ImageDestroy($pattern);
*/
?>