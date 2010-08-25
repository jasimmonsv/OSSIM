<?php
/**
* Class and Function List:
* Function list:
* - replace_numbers()
* - check_fontmap()
* - check_font()
* Classes list:
*/
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/
include ("base_conf.php");
include ("$BASE_path/includes/base_constants.inc.php");
include ("$BASE_path/includes/base_state_common.inc.php");
include ("$BASE_path/base_graph_common.php");
require_once ('Image/Graph.php');
// One more time: A workaround for the inability of PEAR::Image_Canvas-0.3.1
// to deal with strings as x-axis labels in a proper way in the case
// of a logarithmic y-axis.
function replace_numbers($value) {
    GLOBAL $xdata;
    $str = $xdata[$value][0];
    return $str;
}
function check_fontmap($font_name, $fontmap) {
    GLOBAL $debug_mode;
    $ok = 0;
    if (file_exists($fontmap))
    // then we ASSUME, that this is the correct fontmap.txt file. Not necessarily true, though.
    {
        if (is_readable($fontmap)) {
            $fd = file($fontmap);
            foreach($fd as $line) {
                list($map_fontname, $fontfiles_str) = explode(',', $line);
                $map_fontname = trim($map_fontname);
                if (strcmp($font_name, $map_fontname) == 0)
                // this line of fontmap.txt countains our font
                // Now is there also a corresponding font file?
                {
                    $fontfiles_str = trim($fontfiles_str);
                    if ($debug_mode > 1) {
                        error_log("fontfiles_str = \"" . $fontfiles_str . "\"");
                    }
                    $filenames_array = explode(',', $fontfiles_str);
                    foreach($filenames_array as $single_filename) {
                        $single_filename = trim($single_filename);
                        if ($debug_mode > 0) {
                            error_log("single_filename = \"" . $single_filename . "\"");
                        }
                        if (file_exists($single_filename)) {
                            if (is_readable($single_filename)) {
                                $ok = 1;
                                break;
                            }
                        }
                        // trying to imitate fontMap() in Image_Canvas-0.3.1/Canvas/Tool.php in a simplified way
                        $image_canvas_system_font_path = ".";
                        if (array_key_exists("IMAGE_CANVAS_SYSTEM_FONT_PATH", $_SESSION)) {
                            $image_canvas_system_font_path = $_SESSION['IMAGE_CANVAS_SYSTEM_FONT_PATH'];
                        } elseif (array_key_exists("IMAGE_CANVAS_SYSTEM_FONT_PATH", $_SERVER)) {
                            $image_canvas_system_font_path = $_SERVER['IMAGE_CANVAS_SYSTEM_FONT_PATH'];
                        } elseif (array_key_exists("IMAGE_CANVAS_SYSTEM_FONT_PATH", $_ENV)) {
                            $image_canvas_system_font_path = $_ENV['IMAGE_CANVAS_SYSTEM_FONT_PATH'];
                        }
                        if (file_exists($image_canvas_system_font_path . '/' . $single_filename)) {
                            if (is_readable($image_canvas_system_font_path . '/' . $single_filename)) {
                                $ok = 1;
                                break;
                            }
                        }
                    }
                    if ($ok == 1) {
                        break;
                    }
                } // strcmp()
                if ($ok == 1) {
                    if ($debug_mode > 0) {
                        error_log("File for font " . $font_name . " found!");
                    }
                    break;
                }
            }
        } else {
            error_log(__FILE__ . ":" . __LINE__ . ": ERROR: \"$fontmap\" does exist, but it is NOT READABLE.<BR>\n");
            return 0;
        }
    }
    if ($ok == 1) {
        if ($debug_mode > 0) {
            error_log("Found! File for font " . $font_name . " could be found!");
        }
    }
    return $ok;
}
function check_font($font_name) {
    GLOBAL $debug_mode;
    $ok = 0;
    $php_path = ini_get('include_path');
    $php_path_array = explode(':', $php_path);
    if ($debug_mode > 1) {
        error_log("Where is fontmap.txt?");
    }
    foreach($php_path_array as $single_path) {
        $where_is_it = "$single_path/Image/Canvas/Fonts/fontmap.txt";
        if ($debug_mode > 1) {
            error_log($where_is_it);
        }
        if (file_exists($where_is_it)) {
            if (is_readable($where_is_it)) {
                if ($debug_mode > 0) {
                    error_log("fontmap is located in " . $where_is_it);
                }
                $rv = check_fontmap($font_name, $where_is_it);
                if ($debug_mode > 0) {
                    error_log("check_fontmap() returned " . $rv);
                }
                if ($rv == 1) {
                    $ok = 1;
                }
                break;
            } else {
                error_log($where_is_it . " does exist, but is not readable.");
            }
        }
    }
    return $ok;
}
$xdata = $_SESSION['xdata'];
$width = ImportHTTPVar("width", VAR_DIGIT);
$height = ImportHTTPVar("height", VAR_DIGIT);
$pmargin0 = ImportHTTPVar("pmargin0", VAR_DIGIT);
$pmargin1 = ImportHTTPVar("pmargin1", VAR_DIGIT);
$pmargin2 = ImportHTTPVar("pmargin2", VAR_DIGIT);
$pmargin3 = ImportHTTPVar("pmargin3", VAR_DIGIT);
$title = ImportHTTPVar("title", VAR_ALPHA | VAR_SPACE);
$xaxis_label = ImportHTTPVar("xaxis_label", VAR_ALPHA | VAR_SPACE);
$yaxis_label = ImportHTTPVar("yaxis_label", VAR_ALPHA | VAR_SPACE);
$yaxis_scale = ImportHTTPVar("yaxis_scale", VAR_DIGIT);
$xaxis_grid = ImportHTTPVar("xaxis_grid", VAR_DIGIT);
$yaxis_grid = ImportHTTPVar("yaxis_grid", VAR_DIGIT);
$rotate_xaxis_lbl = ImportHTTPVar("rotate_xaxis_lbl", VAR_DIGIT);
$style = ImportHTTPVar("style", VAR_ALPHA);
$chart_type = ImportHTTPVar("chart_type", VAR_DIGIT);
if ($chart_type == 15 || $chart_type == 17) {
    // Number of alerts spread over a worldmap: width and height
    // MUST be constant. At least as of Image_Graph-0.7.2
    // Otherwise the coordinates file must be regenerated. And this
    // is NOT possible during runtime (as of version 0.7.2)
    $Graph = & Image_Graph::factory('graph', array(
        1800,
        913
    ));
    //$Graph =& Image_Graph::factory('graph', array(600, 300));
    
} elseif (($yaxis_scale == 1) && ($style != 'pie')) {
    // the old form of instantiation does not seem to work
    // any more with PEAR::Image_Canvas-0.3.1 with logarithmic
    // y-axes. So factory-method is required.
    $Graph = & Image_Graph::factory('graph', array(
        $width,
        $height
    ));
} else {
    // Create the graph area, legends on bottom -- Alejandro
    $Graph = & new Image_Graph(array(
        'driver' => 'gd',
        'width' => $width,
        'height' => $height
    ));
}
if ($chart_type == 15 || $chart_type == 17)
// then a worldmap is to be drawn.
{
    $Graph->add(Image_Graph::vertical(Image_Graph::factory('title', array(
        $title,
        35
    )) , Image_Graph::vertical(
    // create the plotarea
    $Plotarea = Image_Graph::factory('Image_Graph_Plotarea_Map', 'world_map6') , $Legend = Image_Graph::factory('legend') , // legend does not work, yet.
    90) , 10));
} elseif ($yaxis_scale == 1)
// then a logarithmic y axis has been requested
{
    if ($style == "pie")
    // in this case we ignore logarithm
    {
        $Graph->add(Image_Graph::vertical(Image_Graph::factory('title', array(
            $title,
            16
        )) , Image_Graph::horizontal($Plotarea = Image_Graph::factory('plotarea') , $Legend = Image_Graph::factory('legend') , 80) , 10));
    } else
    // bar, line
    {
        $Graph->add(Image_Graph::vertical(Image_Graph::factory('title', array(
            $title,
            16
        )) , Image_Graph::vertical($Plotarea = Image_Graph::factory('plotarea', array(
            'axis',
            'axis_log'
        )) , $Legend = Image_Graph::factory('legend') , 80
        // 85
        ) , 10));
    }
} else
// linear y-axis
{
    if ($style == "pie") {
        $Graph->add(Image_Graph::vertical(Image_Graph::factory('title', array(
            $title,
            16
        )) , Image_Graph::horizontal($Plotarea = Image_Graph::factory('plotarea') , $Legend = Image_Graph::factory('legend') , 80
        // 85
        ) , 10));
    } else
    // bar, line
    {
        $Graph->add(Image_Graph::vertical(Image_Graph::factory('title', array(
            $title,
            16
        )) , Image_Graph::vertical($Plotarea = Image_Graph::factory('plotarea') , $Legend = Image_Graph::factory('legend') , 85) , 10));
    }
}
$rv = ini_get("safe_mode");
if ($rv != 1)
// normal mode
{
    $font_name = "Verdana";
    if (check_font($font_name)) {
        $Font = & $Graph->addNew('font', $font_name);
    } else {
        $Font = & $Graph->addNew('Image_Graph_Font');
        error_log(__FILE__ . ":" . __LINE__ . ": WARNING: " . $font_name . " could not be resolved into a readable font file. Check \"Image/Canvas/Fonts/fontmap.txt\" in your PEAR directory. This directory can be found by pear 'config-show | grep \"PEAR directory\"'. Falling back to default font without the possibility to adjust any font sizes");
    }
    if (($chart_type == 15) || ($chart_type == 17))
    // worldmap
    {
        $Font->setSize(8);
    } else
    // all the other chart types
    {
        $Font->setSize(8);
    }
    $Graph->setFont($Font);
} else
// safe_mode
{
    $Font = & $Graph->addNew('Image_Graph_Font');
    $Font->setSize(8); // has no effect!
    error_log(__FILE__ . ":" . __LINE__ . ": WARNING: safe_mode: Falling back to default font without the possibility to adjust any font sizes.");
}
// Configure plotarea
if (($chart_type == 15) || ($chart_type == 17)) {
    //  PHP Fatal error:  Allowed memory size of 104857600 bytes exhausted (tried to allocate 37 bytes) in /usr/share/pear/Image/Canvas.php on line 179
    //  ini_set("memory_limit", "100M");
    //  $Legend->setPlotarea($Plotarea);
    
} elseif ($style == "pie") {
    $Legend->setPlotarea($Plotarea);
} else {
    $Plotarea->setAxisPadding(30, 'top');
    $Plotarea->setAxisPadding(30, 'bottom');
    $Plotarea->setAxisPadding(10, 'left');
    $Plotarea->setAxisPadding(10, 'right');
}
$AxisX = & $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
$AxisY = & $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
if (($style != "pie") && ($chart_type != 15) && ($chart_type != 17)) {
    // Arrows
    $AxisX->showArrow();
    $AxisY->showArrow();
    // Grid lines for y-axis requested?
    if ($yaxis_grid == 1) {
        $GridY = & $Plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y);
        $GridY->setFillStyle(Image_Graph::factory('gradient', array(
            IMAGE_GRAPH_GRAD_VERTICAL,
            'white',
            'lightgrey'
        )));
    }
    // Grid lines for x-axis requested?
    if ($xaxis_grid == 1) {
        $Plotarea->addNew('line_grid', true, IMAGE_GRAPH_AXIS_X);
    }
}
// Create the dataset -- Alejandro
$Dataset = & Image_Graph::factory('dataset');
for ($i = 0; $i < count($xdata); $i++) {
    if ($debug_mode > 1) {
        error_log($i . ": \"" . $xdata[$i][0] . "\" - " . $xdata[$i][1]);
    }
    if (($chart_type == 15) || ($chart_type == 17)) {
        $tmp = $xdata[$i][0];
        $tmp_lower = strtolower($tmp);
        if ($debug_mode > 1) {
            error_log("to be looked up: '$tmp', '$tmp_lower' ###");
        }
        // special case '"I0" => "private network (rfc 1918)"' and
        // '"** (private network) " => "private network (rfc 1918)"'
        if (ereg("rfc 1918", $tmp, $substring) || (ereg("[*][*] \(private network\) ", $tmp_lower, $substring))) {
            $Dataset->addPoint("private network (rfc 1918)", $xdata[$i][1]);
        }
        // special case '?? (Not Found) ' => 'unknown'
        elseif (ereg("[?][?][ \t]+\(Not Found\)[ \t]*", $tmp, $substring)) {
            $Dataset->addPoint("unknown", $xdata[$i][1]);
        }
        // anything inside parentheses, following a 2-letter TLD:
        elseif (ereg("^[-a-zA-Z0-9]{2}[ \t]\((.+)\)[ \t]*$", $tmp, $substring)) {
            $Dataset->addPoint($substring[1], $xdata[$i][1]);
        }
        // anything after two-letter top level domain names and after one space or tab:
        elseif (ereg("[ \t]*[-a-zA-Z0-9]{2}[ \t]([-a-zA-Z0-9]+[-a-zA-Z0-9 ]*)", $tmp, $substring)) {
            $Dataset->addPoint($substring[1], $xdata[$i][1]);
        }
        // two-letter top level domain names right at the beginning:
        elseif (ereg("[ \t]*([-a-zA-Z0-9]{2})[ \t]", $tmp_lower, $substring)) {
            $Dataset->addPoint($substring[1], $xdata[$i][1]);
        } else {
            $Dataset->addPoint($tmp, $xdata[$i][1]);
        }
    } elseif (($yaxis_scale == 1) && ($style != 'pie'))
    // Logarithmic y-axis with PEAR::Image_Canvas-0.3.1 seems to be buggy:
    // It does not work with strings as x-axis labels. So a workaround
    // is necessary - once again.
    {
        $Dataset->addPoint($i, $xdata[$i][1]);
    } else {
        $Dataset->addPoint($xdata[$i][0], $xdata[$i][1]);
    }
}
$number_elements = $i;
if ($debug_mode > 1) {
    error_log("number_elements = $number_elements");
}
// Design plot: Should it be a bar, line or a pie chart?
if (($chart_type == 15) || ($chart_type == 17)) {
    $Plot = & $Plotarea->addNew('Image_Graph_Plot_Dot', array(&$Dataset
    ));
} elseif ($style == "line")
// then we correct this style and replace it by "area":
{
    $Plot = & $Plotarea->addNew('area', $Dataset);
} else {
    $Plot = & $Plotarea->addNew($style, $Dataset);
}
// What about the axes?
if (($chart_type == 15) || ($chart_type == 17)) {
    // Well, nothing to do here.
    
} elseif ($style == "pie") {
    // We don't need any axes
    $Plotarea->hideAxis();
    $Plot->explode(10);
} else {
    /*
    $ArrayData =& Image_Graph::factory('Image_Graph_DataPreprocessor_Array',$Dataset);
    
    // Prepare x-axis labels
    $AxisX->setDataPreprocessor($ArrayData);
    */
    // Part of that workaround for PEAR::Image_Canvas being unable to
    // deal with strings as x-axis lables in a proper way
    if ($yaxis_scale == 1) {
        $AxisX->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'replace_numbers'));
    }
    // Should they be rotated by 90 degress?
    /* One possibility: We could make the decision here:
    *
    if (
    ($number_elements > 1) &&
    (
    ($xaxis_label == _CHRTTIME) ||
    ($xaxis_label == _CHRTSIP) ||
    ($xaxis_label == _CHRTDIP) ||
    ($xaxis_label == _CHRTCLASS)
    )
    )
    */
    // Another possibility: Let the user decide:
    if ($rotate_xaxis_lbl == 1) {
        // affects x-axis title and labels:
        $AxisX->setFontAngle('vertical');
        // x-axis title
        if ((isset($xaxis_label)) && (strlen($xaxis_label) > 0)) {
            $AxisX->setTitle($xaxis_label, array(
                'angle' => 0,
                'size' => 10
            ));
        }
        // x-axis labels:
        // Workaround according to
        // http://pear.php.net/bugs/bug.php?id=8675
        $AxisX->setLabelOption('showOffset', true);
        if ($chart_type <= 1) {
            // For time labels:
            $AxisX->setLabelOption('offset', 130);
        } else if ($chart_type <= 3) {
            // For days:
            $AxisX->setLabelOption('offset', 45);
        } else if ($chart_type <= 5) {
            // For months:
            $AxisX->setLabelOption('offset', 30);
        } else if ($chart_type <= 7) {
            // for ip addresses:
            $AxisX->setLabelOption('offset', 60);
        } else if ($chart_type <= 11) {
            // for port numbers
            $AxisX->setLabelOption('offset', 18);
        } else if ($chart_type == 12) {
            // for classifications
            $AxisX->setLabelOption('offset', 210);
        } else if ($chart_type == 13) {
            // for host names of sensors
            $AxisX->setLabelOption('offset', 70);
        } else if ($chart_type <= 15) {
            // 2-letter contry name plus complete country name
            $AxisX->setLabelOption('offset', 110);
        } else {
            $AxisX->setLabelOption('offset', 70);
        }
    } else {
        // x-axis title if no rotation is required
        if ((isset($xaxis_label)) && (strlen($xaxis_label) > 0)) {
            $AxisX->setTitle($xaxis_label, array(
                'size' => 10
            ));
        }
    }
    // Prepare y-axis title
    if ((isset($yaxis_label)) && (strlen($yaxis_label) > 0)) {
        $AxisY->setTitle($yaxis_label, array(
            'angle' => 90,
            'size' => 10
        ));
    }
}
// Set markers (small rectangular labels inside the plot)
if ($chart_type == 15 || $chart_type == 17) {
    $Marker = & $Plot->setMarker(Image_Graph::factory('Image_Graph_Marker_Bubble'));
    $ValueMarker = & Image_Graph::factory('Image_Graph_Marker_Value', IMAGE_GRAPH_VALUE_X);
    // Image_Graph_Marker_Pointing_Angular or Image_Graph_Marker_Pointing_Radial? Both of them are not perfect.
    $Marker->setSecondaryMarker(Image_Graph::factory('Image_Graph_Marker_Pointing_Radial', array(
        40, &$ValueMarker
    )));
} else {
    $Marker = & $Plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_PCT_Y_TOTAL);
    $PointingMarker = & $Plot->addNew('Image_Graph_Marker_Pointing_Angular', array(
        20, &$Marker
    ));
    $Plot->setMarker($PointingMarker);
    $Marker->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', '%0.1f%%'));
}
// background of the whole drawing board:
$Graph->setBackground(Image_Graph::factory('gradient', array(
    IMAGE_GRAPH_GRAD_VERTICAL,
    'silver@0.5',
    'white'
)));
$Graph->setBorderColor('black');
$Graph->setPadding(10);
// background of the plotarea only:
if (($chart_type != 15) && ($chart_type != 17)) {
    $Plotarea->setBackgroundColor('white');
} else
// worldmap:
{
    $Plotarea->setFillColor('white');
    $FillArray2 = & Image_Graph::factory('Image_Graph_Fill_Array');
    $FillArray2->addColor('white');
    $Plotarea->setFillStyle($FillArray2);
}
$Plotarea->setBorderColor('black');
$Plotarea->setPadding(20);
$Plotarea->showShadow();
// and now all the filling tasks (gradients and the like) of the plot:
if (($chart_type == 15) || ($chart_type == 17)) {
    // set a line color
    $Plot->setLineColor('gray');
    // set a standard fill style
    $FillArray = & Image_Graph::factory('Image_Graph_Fill_Array');
    $Marker->setFillStyle($FillArray);
    $FillArray->addColor('orange@0.5');
    $FillArray->addColor('green@0.5');
    $FillArray->addColor('blue@0.5');
    $FillArray->addColor('yellow@0.5');
    $FillArray->addColor('red@0.5');
    $FillArray->addColor('black@0.5');
} elseif ($style == "bar") {
    $Plot->setFillStyle(Image_Graph::factory('gradient', array(
        IMAGE_GRAPH_GRAD_VERTICAL,
        'white',
        'red'
    )));
} elseif ($style == "line") {
    $Plot->setFillStyle(Image_Graph::factory('gradient', array(
        IMAGE_GRAPH_GRAD_VERTICAL,
        'orange',
        'lightyellow'
    )));
} elseif ($style == "pie")
// colours are each time determined randomly:
// TODO:
// While each colour name is taken only once rather than twice
// or multiple times, the colours for two different colour names
// may appear on the screen as if they were identical. Some names
// may also simply be aliases. This can only be solved by removing
// the corresponding colour names from this list:
{
    $mycolors = "aliceblue aquamarine azure beige bisque black blanchedalmond blue blueviolet brown burlywood cadetblue chocolate coral cornflowerblue cornsilk crimson cyan darkcyan darkgoldenrod darkgray darkgreen darkkhaki darkmagenta darkolivegreen darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray darkviolet deeppink deepskyblue dimgray dodgerblue firebrick forestgreen fuchsia gainsboro gold goldenrod gray green greenyellow honeydew hotpink indianred indigo khaki lavender lawngreen lemonchiffon lightblue lightcoral lightcyan lightgoldenrodyellow lightgreen lightgrey lightpink lightsalmon lightseagreen lightslategray lightsteelblue lightyellow lime limegreen linen magenta maroon mediumaquamarine mediumorchid mediumpurple mediumseagreen mediumslateblue mediumspringgreen mediumturquoise mediumvioletred mistyrose navy oldlace olive olivedrab orange orangered orchid palegoldenrod palegreen paleturquoise palevioletred papayawhip peru pink powderblue purple red rosybrown royalblue saddlebrown salmon sandybrown seagreen sienna silver skyblue slateblue slategray springgreen steelblue tan teal thistle tomato violet wheat white yellow yellowgreen";
    // removed:
    // darkblue,
    // plum,
    // chartreuse,
    // antiquewhite, blanchedalmond, navajowhite, moccasin, peachpuff
    // aqua, darkturquoise, lavenderblush, turquoise
    // lightskyblue, mediumturquoise, paleturquoise
    // mediumblue, midnightblue
    // floralwhite, ghostwhite, ivory, mintcream, snow, whitesmoke, seashell
    $color_array = explode(" ", $mycolors);
    $num_colors = count($color_array);
    shuffle($color_array);
    $FillArray = & Image_Graph::factory('Image_Graph_Fill_Array');
    for ($n = 0, $array_index = 0; $n < $number_elements; $n++, $array_index++) {
        if ($array_index >= $num_colors)
        // then restart from the beginning
        {
            $array_index = 0;
        }
        $color_to_use = $color_array[$array_index];
        $FillArray->addNew('gradient', array(
            IMAGE_GRAPH_GRAD_RADIAL,
            'white',
            $color_to_use
        ));
    }
    // If there are a lot elements, we need some more space at the bottom:
    // (not really a good solution)
    /*
    if ($number_elements >= 10)
    {
    $Graph->setPadding(90);
    }
    */
    $Plot->setFillStyle($FillArray);
    $Plot->Radius = 2;
} else {
    error_log("$style is an unsupported chart style");
}
// Show time! -- Alejandro
$Graph->done();
// vim:shiftwidth=2:tabstop=2:expandtab

?>
