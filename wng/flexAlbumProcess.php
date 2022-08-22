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
function ciniki_gallery_wng_flexAlbumProcess(&$ciniki, $tnid, &$request, $section) {

    $blocks = array();
    $s = isset($section['settings']) ? $section['settings'] : array();
// // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.gallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.32', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.39', 'msg'=>"No category specified."));
    }

    //
    // Make sure albums specified
    //
    if( !isset($section['settings']['album-id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.40', 'msg'=>"No album specified."));
    }

    $image_permalink = '';
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) ) {
        $image_permalink = $request['uri_split'][($request['cur_uri_pos']+1)];
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
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.gallery.41', 'msg'=>'I\'m sorry, we are unable to find an album by that name.'));
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

        foreach($images as $iid => $image) {
            $images[$iid]['url'] = $request['page']['path'] . ($request['page']['path'] != '/' ? '/' : '') . $image['permalink'];
            if( $image_permalink == $image['permalink'] ) {
                $selected_image = $image;
            }
        }
        
/*        if( isset($album['name']) && $album['name'] != '' 
            && isset($s['title-show']) && $s['title-show'] == 'yes'
            ) {
            $blocks[] = array(
                'type' => 'text',
                'title' => $album['name'],
                'content' => $album['description'],
                );
        } */

        if( isset($selected_image) ) {
            $blocks[] = array(
                'type' => 'title',
                'title' => $request['page']['title'] . ' - ' . $selected_image['title'],
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
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
