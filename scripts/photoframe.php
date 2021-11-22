<?php
//
// This script is the entry point to the photo frame for gallery albums.
// Similar to the dashboard for qruqsp project.
//
$start_time = microtime(true);

//
// Load ciniki
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
// Some systems don't follow symlinks like others
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}

//
// Initialize Ciniki
//
require_once($ciniki_root . '/ciniki-mods/core/private/loadCinikiConfig.php');
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');

$ciniki = array();
if( ciniki_core_loadCinikiConfig($ciniki, $ciniki_root) == false ) {
    print_error(NULL, 'There is currently a configuration problem, please try again later.');
    exit;
}

//
// Load required functions
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkModuleFlags');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInit');

//
// Initialize Database
//
$rc = ciniki_core_dbInit($ciniki);
if( $rc['stat'] != 'ok' ) {
    print_error($rc, 'There is a currently a problem with the system, please try again later.');
    exit;
}

//
// Parse the GET and POST variables
//
$args = array(
    'path'=>array(),        // Default path is empty, root of photo frame
);
if( isset($_GET) && is_array($_GET) ) {
    foreach($_GET as $k => $v) {
        $args[$k] = $v;
    }
}
if( isset($_POST) && is_array($_POST) ) {
    foreach($_POST as $k => $v) {
        $args[$k] = $v;
    }
}

//
// Split the Request variables
//
$uri = preg_replace('/^\//', '', $_SERVER['REQUEST_URI']);
$u = preg_split('/\?/', $uri);
$args['path'] = preg_split('/\//', $u[0]);
if( !is_array($args['path']) ) {
    $args['path'] = array($args['path']);
}
if( isset($args['path'][0]) && $args['path'][0] == 'photoframe' ) {
    array_shift($args['path']);
}

//
// Default to master tenant
//
$tnid = $ciniki['config']['ciniki.core']['master_tnid'];

//
// Setup the base_url the photoframe is running under
//
$args['base_url'] = '';

//
// Lookup the gallery
//
if( !isset($args['path'][0]) || $args['path'][0] == '' ) {
    print_error($rc, 'No gallery specified');
    exit;
}

$strsql = "SELECT id, uuid, tnid, name "
    . "FROM ciniki_gallery_albums "
    . "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $args['path'][0]) . "' "
    . "AND (webflags&0x10) = 0x10 "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'album');
if( $rc['stat'] != 'ok' ) {
    print_error($rc, 'Error opening gallery');
    exit;
}
if( !isset($rc['album']) ) {
    print_error($rc, 'No gallery found');
    exit;
}
$album = $rc['album'];

// Check the type of device
//
$ciniki['remote_device'] = 'generic';
if( isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'iPad; CPU OS 5') !== false ) {
    $ciniki['remote_device'] = 'ipad1';
}

//
// Setup the session
//
$ciniki['session'] = array(
    'change_log_id' => 'photoframe.' . date('Ymd.His'),
    'user' => array('id'=>'-5'),
    );

//
// Load the image list
//
$strsql = "SELECT id, uuid, image_id, UNIX_TIMESTAMP(last_updated) AS last_updated "
    . "FROM ciniki_gallery "
    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $album['tnid']) . "' "
    . "AND album_id = '" . ciniki_core_dbQuote($ciniki, $album['id']) . "' "
    . "ORDER BY RAND() "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
    array('container'=>'images', 'fname'=>'id', 'fields'=>array('id', 'uuid', 'image_id', 'last_updated')),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.38', 'msg'=>'Unable to load images', 'err'=>$rc['err']));
}
$images = isset($rc['images']) ? $rc['images'] : array();

$cache_dir = $ciniki['config']['ciniki.core']['root_dir'] . '/ciniki-mods/gallery/pfcache/' . $album['uuid'][0] . '/' . $album['uuid'];
if( !file_exists($cache_dir) ) {
    mkdir($cache_dir, 0755, true);
}
$cache_url = '/photoframe-cache/' . $album['uuid'][0] . '/' . $album['uuid'];

//
// Generate images and html
//
$image_html = "<div class='images'>";
$count = 1;
$class = 'hidden';
foreach($images as $idx => $image) {
    
    //
    // Check if cached file exists
    //
    $filename = $cache_dir . '/' . $image['uuid'] . '.jpg';
    if( !file_exists($filename) || filemtime($filename) < $image['last_updated'] ) {
        //
        // Update cache
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $album['tnid'], $image['image_id'], 'original');
        if( $rc['stat'] != 'ok' ) {
            error_log('Unable to process image: ' . $image['image_id']);
            continue;
        } else {
            $img = $rc['image'];
            if( $img->getImageWidth() > 2048 ) {
                $img->scaleImage(2048, 0);
            }
            $h = fopen($filename, 'w');
            if( $h ) {
                $img->setImageFormat('jpeg');
                $img->setImageCompressionQuality(70);
            }
            fwrite($h, $img->getImageBlob());
            fclose($h);
        }
    } 
    if( count($images) == ($idx+1) ) {
        $class = '';
    }
    $image_html .= "<div id='{$count}' class='image {$class}' "
        . "style='background:#000 url({$cache_url}/{$image['uuid']}.jpg) center center; background-size:cover;'>"
        . "</div>";
    $count++;
}
$image_html .= "</images>";
$count--;

$content = "<!DOCTYPE html>\n";
$content .= "<head>";
$content .= "<title>Photoframe - {$album['name']}</title>";
$content .= '<meta content="text/html:charset=UTF-8" http-equiv="Content-Type" />';
$content .= '<meta content="UTF-8" http-equiv="encoding" />';
$content .= '<meta name="apple-mobile-web-app-capable" content="yes" />';
$content .= '<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />';
$content .= "<style>"
    . "body { padding: 0px; margin: 0px; height: 100vh;}"
    . ".image { width: 100%; width: 100vw; height: 100%; height: 100vh; padding: 0; border: 0; margin: 0; "
        . "background-size: cover; "
        . "position: absolute; "
        . "top: 0px; left: 0px; "
        . "}"
    . ".hidden { display: none; }"
    . "img { max-width: 100%; }"
    . "</style>";
$content .= "<script type='text/javascript'>"
    . "var _timer=null;"
    . "var cur={$count};"
    . "var num={$count};"
    . "function init() {"
        . "_timer = setTimeout(next, 30000);"
    . "}"
    . "function next() {"
        . "var p=cur;"
//        . "cur++;"
//        . "if(cur>num){cur=1;}"
        . "cur--;"
        . "if(cur<1){cur={$count};}"
        . "var e=document.getElementById(cur);"
        . "e.className = 'image';"
        . "var e=document.getElementById(p);"
        . "e.className = 'image hidden';"
        . "_timer = setTimeout(next, 10000);"
    . "}"
    . "window.onload=init();"
    . "</script>";
$content .= "</head>";
$content .= "<body>";

$content .= $image_html;
//$content = '<pre>' . print_r($images, true) . '</pre>';

$content .= "</body></html>";

print $content;

//
// Done
//
exit;

//
// Support functions
//
function print_error($rc, $msg) {
print "<!DOCTYPE html>\n";
?>
<html>
<head><title>Error</title></head>
<body style="margin: 0px; padding: 0px; border: 0px;">
<div style="display: table-cell; text-align: middle; width: 100vw; height: 100vh; margin: 0; padding: 0; box-sizing: border-box; vertical-align: middle;">
    <div style="margin: 0 auto; vertical-align: middle; text-align: center; ">
            <p style="font-size: 1.5em;">Error:  <?php echo $msg; ?></p>
            <br/><br/>
<?php
    if( $rc != null && isset($rc['stat']) && $rc['stat'] != 'ok' ) {
        print '<p style="font-size: 1em; "><b>Errors</b><br/>';
        while($rc != null ) {
            print $rc['err']['code'] . ': ' . $rc['err']['msg'] . '<br/><br/>'; 
            if( isset($rc['err']['err']) ) {
                $rc = $rc['err'];
            } else {
                $rc = null;
            }
        }
        print '</p>';
    }
?>
    </div>
</div>
</body>
</html>
<?php
}
?>
