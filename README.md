# MVRPlayer
 A Wordpress plugin that handles playback of self-hosted videos.

The plugin is very simple in nature: it grabs videos from a remote location and prepares them for playback within your Wordpress installation. This is useful if you have limited amount of storage space, and yet want to host your own videos on-demand.

## Usage
- Place mvr_player.php in the plugins folder of your Wordpress
- Place mvr_playerframe.php in the root of your Wordpress installation
- Create the directories as specified in the variables and make sure the webserver has read/write rights to these directories
- Edit the variables (remote hosts and paths) as needed in both files
- Start hosting videos and thumbnails of mp4 type in the specified remote location
- Create a post with the [mvr] shortcode, followed by the video you want to display - for example [mvr video=myGamePlayVideo]
- If the video is not yet present in the specified local directory, it will be downloaded upon click of the Play button - progress is being displayed
- Upon completion of the download, the video is playable

## Notes
When a page with the shortcode is requested, the specified local folder of your webserver will be scanned for videos older than 10 days. These, along with the thumbnails, will be purged to free up space. You can change this amount by adjusting the number in the function MVR_cleanUpVideos() on line 42. The last number there is 10 - that's the amount of days to compare the file age to.

The file mvr_playerframe.php contains a function that compares the file size of the local video file with that of the remote destination. If there is no data on the video size, a curl call is done to the remote file to find the file size. This is then stored in small JSON files on your web server. This is done to minimize the amount of these curl calls to your remote server.