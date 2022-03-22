<?php

// Define the remote location of our video cache.
$GLOBALS["MVR_VideoServer"] = "http://82.82.86.40/videos/mp4/";
$GLOBALS["MVR_ThumbServer"] = "http://82.82.86.40/videos/thumb/";

// Where on this server we want to store videos and thumbs
$GLOBALS["MVR_LocalVideoPath"] = $_SERVER['DOCUMENT_ROOT']."/player/content/";
$GLOBALS["MVR_LocalDataPath"] = $_SERVER['DOCUMENT_ROOT']."/player/data/";

$GLOBALS["MVR_Thumbs"] = "/player/content/";

function MVR_getVideoFileSize()
{
    $curl = curl_init($GLOBALS["MVR_VideoServer"].$_GET["video"].".mp4");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_exec($curl);
    $fileSize = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

    $data = array(
        "video" => $_GET["video"],
        "filesize" => $fileSize
    );
    $json = json_encode($data);
    file_put_contents($GLOBALS["MVR_LocalDataPath"].$_GET["video"].".json", $json);
}


function MVR_renderDownloadBar($full, $local, $vid)
{
    $percent = $local / $full;
    $barpix = $percent * 500;

    echo '
    <div id="MVR_VideoOffer" style="width:720px; height: 405px; position: relative;">
        <img src="'.$GLOBALS["MVR_Thumbs"] . $vid .'.png" style="position:absolute; width=720px; height: 405px; margin: 0px;">
        <div style="background-color: rgba(0, 0, 0, 0.65); width: 720px; height: 405px; position: absolute; margin: 0px; z-index: 1;"></div>
        <div style="width: 720px; height: 405px; position: absolute; margin: 0px; z-index: 2;">
        <br><br><br><br><br><br>
        <center>
        <div style="width: 500px; height: 20px; z-index: 3; position: relative;">
            <div style="position: absolute; top: 0px; left: 0px; width: '.$barpix.'px; height: 20px; background-color: #fefefe; z-index: 4"></div><br>
            <font face="Arial" size=4 style="color:#fefefe;" <b>'.round(($percent * 100), 2).'% abgeschlossen</b></font>
        </div>
        </center>
        </div>
    </div>
    ';
}


function MVR_renderPlayer()
{
    echo '
    <video width="720" height="405" controls><source src="/player/content/'.$_GET['video'].'.mp4" type="video/mp4">Your browser does not support the video tag.
    </video>
    ';
}


function MVR_renderCheck()
{
    if (file_exists($GLOBALS["MVR_LocalVideoPath"].$_GET["video"].".mp4") == true)
    {
        if (file_exists($GLOBALS["MVR_LocalDataPath"].$_GET["video"].".json") == false)
        { MVR_getVideoFileSize(); }

        $json = file_get_contents($GLOBALS["MVR_LocalDataPath"].$_GET["video"].".json");
        $arr = json_decode($json, true);
        $fullSize = $arr["filesize"];
        $localSize = filesize($GLOBALS["MVR_LocalVideoPath"].$_GET["video"].".mp4");

        // Depending on the result of the next condition, we decide what to render.
        if ($localSize < $fullSize)
        { header("Refresh:2"); MVR_renderDownloadBar($fullSize, $localSize, $_GET["video"]); }

        if ($localSize == $fullSize)
        { MVR_renderPlayer(); }
    }
}

MVR_renderCheck();

?>