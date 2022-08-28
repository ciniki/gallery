<?php
//
// Description
// -----------
// This function will return the b
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_gallery_wng_flexAlbumsProcess(&$ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    $blocks = array();
    $s = isset($section['settings']) ? $section['settings'] : array();
// // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.gallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.42', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.43', 'msg'=>"No category specified."));
    }

    $image_permalink = '';
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) ) {
        $image_permalink = $request['uri_split'][($request['cur_uri_pos']+1)];
    }

    $album_ids = array();
    for($i = 1; $i <= 10; $i++) {
        if( isset($s["album-{$i}-id"]) && $s["album-{$i}-id"] > 0 ) {
            $album_ids[] = $s["album-{$i}-id"];
        }
    }

    if( count($album_ids) == 0 ) {
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }
    
    //
    // Load the images for the albums
    //
    $strsql = "SELECT ciniki_gallery.id, "
        . "ciniki_gallery.name AS title, "
        . "ciniki_gallery.permalink, "
        . "ciniki_gallery.image_id, "
        . "ciniki_gallery.description AS content "
        . "FROM ciniki_gallery "
        . "WHERE ciniki_gallery.album_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $album_ids) . ") "
        . "AND ciniki_gallery.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_gallery.image_id > 0 "
        . "AND (ciniki_gallery.webflags&0x01) = 0 "
        . "ORDER BY ciniki_gallery.date_added DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'images', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'permalink', 'image-id'=>'image_id', 'content'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.44', 'msg'=>'Unable to load images', 'err'=>$rc['err']));
    }
    $images = isset($rc['images']) ? $rc['images'] : array();

    foreach($images as $iid => $image) {
        $images[$iid]['url'] = $request['page']['path'] . ($request['page']['path'] != '/' ? '/' : '') . $image['permalink'];
        if( $image_permalink == $image['permalink'] ) {
            $selected_image = $image;
        }
    }
        
    if( isset($selected_image) ) {
        $blocks[] = array(
            'type' => 'title',
            'title' => $request['page']['title'] . ($selected_image['title'] != '' ? ' - ' . $selected_image['title'] : ''),
            );
        $blocks[] = array(
            'type' => 'image',
            'image-id' => $selected_image['image-id'],
            'image-permalink' => $selected_image['permalink'],
            'image-list' => $images,
            'content' => $selected_image['content'],
            'base-url' => $request['page']['path'],
            );

        return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
    } else {
        $blocks[] = array(
            'type' => 'gallery',
            'layout' => 'originals',
            'items' => $images,
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
