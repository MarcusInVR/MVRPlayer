<?php

/**
 * @package MarcusInVR Video Player
 * @version 1.0
 */
/*
Plugin Name: MarcusInVR Video Player
Plugin URI: http://marcusinvr.de/mvrplayer
Description: A plugin that handles playback of self-hosted video files within Wordpress.
Author: MarcusInVR
Version: 1.0
Author URI: http://marcusinvr.de/
*/

// Define the remote location of our video cache.
$GLOBALS["MVR_VideoServer"] = "http://82.82.86.40/videos/mp4/";
$GLOBALS["MVR_ThumbServer"] = "http://82.82.86.40/videos/thumb/";

// Where on this server we want to store videos and thumbs
$GLOBALS["MVR_LocalVideoPath"] = $_SERVER['DOCUMENT_ROOT']."/player/content/";

// Exposed thumbnail path
$GLOBALS["MVR_Thumbs"] = "/player/content/";

// The text to show to begin video download
$GLOBALS["MVR_VideoPrepareText"] = "Klicken um Video vorzubereiten";


// This function cleans up videos that have been around for a while, to free up space.
function MVR_cleanUpVideos()
{
    $files = scandir($GLOBALS["MVR_LocalVideoPath"]);
    $now   = time();

    // Get array of files in folder
    $files = scandir($GLOBALS["MVR_LocalVideoPath"]);

    // Walk through array and determine which files to purge
    for ($i=0; $i<count($files); $i++)
    {
        if ($now - filemtime($GLOBALS["MVR_LocalVideoPath"].$files[$i]) >= 60 * 60 * 24 * 10)
        {
            $file_parts = pathinfo($GLOBALS["MVR_LocalVideoPath"].$files[$i]);

            if ($file_parts["extension"] == "mp4")
            { unlink($GLOBALS["MVR_LocalVideoPath"].$files[$i]); }
            if ($file_parts["extension"] == "png")
            { unlink($GLOBALS["MVR_LocalVideoPath"].$files[$i]); }
        }
    }
}


// This checks if the requested video file is present on this server.
// If it is not, grab the thumb, store it, and offer the option to "prepare"
// (download) the video from our own in-house repository.
function MVR_doesVideoExist($vid)
{
    $ex = file_exists($GLOBALS["MVR_LocalVideoPath"] . $vid . ".mp4");
    //echo $GLOBALS["MVR_LocalVideoPath"] . $vid . ".mp4";
    return $ex;
}


// Should video not yet exist in our temporary storage, offer the option
// to acquire the video by acquiring the thumbnail, and display it in a
// clickable box (much like YouTube or Twitch embed boxes)
function MVR_offerVideoPreparation($vid)
{
    // Acquire thumb
    $thumb = file_get_contents($GLOBALS["MVR_ThumbServer"] . $vid . ".png");
    file_put_contents($GLOBALS["MVR_LocalVideoPath"] . $vid . ".png", $thumb);

    // Render the offer box
    echo '
    <div id="MVR_VideoOffer" style="width:720px; height: 405px; position: relative;">
        <img src="'.$GLOBALS["MVR_Thumbs"] . $vid .'.png" style="position:absolute; width=720px; height: 405px; margin: 0px;">
        <div style="background-color: rgba(0, 0, 0, 0.65); width: 720px; height: 405px; position: absolute; margin: 0px; z-index: 1;"></div>
        <div style="width: 720px; height: 405px; position: absolute; margin: 0px; z-index: 2;">
        <br><br><br>
        <center>
        <a href="'.$_SERVER['REQUEST_URI'].'?mvr_video='.$vid.'&return='.$_SERVER['REQUEST_URI'].'"><img src="/img/mvr_play.png"></a><br>
        <font style="color: #efefef; background-color: #000000; padding: 10px;"><b>'.$GLOBALS["MVR_VideoPrepareText"].'</b></font>
        </center>
        </div>
    </div><br>
    ';
}


// Render the iframe for partial download or full player
function MVR_renderPlayeriframe($vid)
{
    echo '
    <iframe src="/mvr_playerframe.php?video='.$vid.'" style="width: 720px; height: 405px;" frameborder=0 style="overflow:hidden;" scrolling="no"></iframe>
    ';
}


// Starts the download of needed video. Then leaves.
function MVR_initiateDownload($vid)
{
    system ("wget -q -O ".$GLOBALS["MVR_LocalVideoPath"].$vid.".mp4 -b ".$GLOBALS["MVR_VideoServer"].$vid.".mp4 > /dev/null 2>&1 &");
}


// The main shortcode function
function MVR_Player( $atts = array() )
{
    // Clean up videos no matter what
    MVR_cleanUpVideos();

    // Make sure we are not editing pages
    if (!isset($_GET["action"]) && $_GET["action"] != "edit")
    {
        // set up default parameters
        extract(shortcode_atts(array(
            'video' => 'none'
        ), $atts));

        // Check if we have a video file at all. If not, offer to
        // initiate the video preparation
        if (MVR_doesVideoExist($atts["video"]) == false && !isset($_GET["mvr_video"]))
        { MVR_offerVideoPreparation($atts["video"]); }

        // If a user clicked on the play arrow, we need to either continue
        // the download of the video, or start downloading it. But only if
        // it is the video we want.
        if (isset($_GET["mvr_video"]) && $_GET["mvr_video"] == $atts["video"])
        {
            // First check if the file exists at all
            if (MVR_doesVideoExist($_GET["mvr_video"]) == false)
            {
                MVR_initiateDownload($_GET["mvr_video"]);
                sleep(1);
                echo '<script>window.location.href = "'.$_GET["return"].'"</script>';
            }
        }

        // If the video file does exist, it either means it is downloaded completely, or download is in progress.
        // We need to make sure not to offer playback of the video until it is done.
        // For this we simply render an iframe from here on in inside which the rest happens.
        if (MVR_doesVideoExist($atts["video"]) == true && !isset($_GET["mvr_video"]))
        { MVR_renderPlayeriframe($atts["video"]); }
    }
}


// Register the shortcode
add_shortcode('mvr', 'MVR_Player');

?>