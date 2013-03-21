<?php
/**
 * Connects to the RefWorks API using apibib_lib apibib class
 * Needs to authenticate like quikbib, but to hardcoded vendor/code
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local/references/apibib
 */

require_once(dirname(__FILE__).'/apibib_lib.php');

//get the 'get' params we need - vendor, filter, style, authID
$vendor = '';
if (isset($_GET['vendor'])) {
    $vendor = $_GET['vendor'];
}
$filter = 'RIS Format';
if (isset($_GET['filter'])) {
    $filter = rawurldecode($_GET['filter']);
}
$style = 'OU Harvard';
if (isset($_GET['style'])) {
    $style = rawurldecode($_GET['style']);
}
$auth= '';
if (isset($_GET['authID'])) {
    $auth = rawurldecode($_GET['authID']);
}
//calculate if authid sent is correct

$sagent = $_SERVER['HTTP_USER_AGENT'];
$tempstring = strtolower('86$T7$3H'.$vendor.$sagent);
$tempstring2 = base64_encode(sha1($tempstring,true));
$authID = $tempstring2;

if ($authID != $auth) {
    header("HTTP 1.1/500 Error");
    echo 'Did not get expected authid. Expecting '.$authID;
    echo ' . user agent: '.$sagent;
    exit;
}
//get the post stream (this is the ref data)

$references ='';
//get the first element from post
$arkey = array_keys($_POST);
if (count($arkey)>0) {
    $references = $_POST[$arkey[0]];
}
if ($references == '') {
    $references = $HTTP_RAW_POST_DATA;
}

$bibarray = apibib::getbib($references, $style, $filter , '', 0, false);
if (!$bibarray) {
    header("HTTP 1.1/500 Error");
    echo '\n\n Error from RefWorks API.';
    echo ' sent references: '.$references;
    exit;
}
//turn array into xml + write to output

$output = '<?xml version="1.0" encoding="utf-8"?>';
$output .= '<FormattedOutput>';
//add each ref to a p tag
foreach ($bibarray as $title) {
    $out = strip_tags($title,'<B><I>');
    if ($out != '') {
        $output .= "\r\n".$out;
    }
}
$output .= "\r\n</FormattedOutput>";
header("HTTP 1.1/200 ok");
echo $output;
?>