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
function ciniki_gallery_wng_albumProcess(&$ciniki, $tnid, &$request, $section) {

    $blocks = array();
    $s = isset($section['settings']) ? $section['settings'] : array();
// // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.gallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.30', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.31', 'msg'=>"No category specified."));
    }

    //
    // Make sure albums specified
    //
    if( !isset($section['settings']['album-id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.33', 'msg'=>"No album specified."));
    }

    
    //
    // Get the details about the albums
    //
    $strsql = "SELECT id, "
        . "name, "
        . "permalink, "
        . "description "
        . "FROM ciniki_gallery_albums "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (webflags&0x01) = 0 "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $section['settings']['album-id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'album');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['album']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.gallery.35', 'msg'=>'I\'m sorry, we are unable to find an album by that name.'));
    }
    $album = isset($rc['album']) ? $rc['album'] : array();

    //
    // Load the images for the albums
    //
    if( isset($album['id']) ) {
        $strsql = "SELECT ciniki_gallery.id, "
            . "ciniki_gallery.name AS title, "
            . "ciniki_gallery.permalink, "
            . "ciniki_gallery.image_id, "
            . "ciniki_gallery.description AS content "
            . "FROM ciniki_gallery "
            . "WHERE ciniki_gallery.album_id = '" . ciniki_core_dbQuote($ciniki, $album['id']) . "' "
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.34', 'msg'=>'Unable to load images', 'err'=>$rc['err']));
        }
        $images = isset($rc['images']) ? $rc['images'] : array();
        
        if( isset($album['name']) && $album['name'] != '' 
            && isset($s['title-show']) && $s['title-show'] == 'yes'
            ) {
            $blocks[] = array(
                'type' => 'text',
                'title' => $album['name'],
                'content' => $album['description'],
                );
        }
        
        $blocks[] = array(
            'type' => 'carousel',
            'sequence' => $section['sequence'],
            'thumbnails' => 'yes',
            'titles' => 'yes',
            'items' => $images,
            'speed' => isset($s['speed']) ? $s['speed'] : 0,
            'padded' => isset($s['padded']) && $s['padded'] == 'yes' ? 'yes' : 'no',
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
