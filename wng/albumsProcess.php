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
function ciniki_gallery_wng_albumsProcess(&$ciniki, $tnid, &$request, $section) {

    $blocks = array();
    $s = isset($section['settings']) ? $section['settings'] : array();
// // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.gallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.45', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.46', 'msg'=>"No category specified."));
    }

    //
    // Get the list of albums
    //
    $strsql = "SELECT albums.id, "
        . "albums.name, "
        . "albums.permalink, "
        . "(SELECT ciniki_gallery.image_id "
            . "FROM ciniki_gallery, ciniki_images "
            . "WHERE albums.id = ciniki_gallery.album_id "
            . "AND (ciniki_gallery.webflags&0x01) = 0 "
            . "AND ciniki_gallery.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_gallery.image_id = ciniki_images.id "
            . "ORDER BY (ciniki_gallery.webflags&0x10) DESC, ciniki_gallery.date_added DESC "
            . "LIMIT 1 "
            . ") AS image_id "
        . "FROM ciniki_gallery_albums AS albums "
        . "WHERE albums.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (albums.webflags&0x01) = 0 "
        . "AND albums.name <> '' ";
    if( isset($s['sort-by']) ) {
        if( $s['sort-by'] == 'name-asc' ) {
            $strsql .= "ORDER BY name ";
        } elseif( $s['sort-by'] == 'name-desc' ) {
            $strsql .= "ORDER BY name DESC ";
        } elseif( $s['sort-by'] == 'sequence-asc' ) {
            $strsql .= "ORDER BY sequence ASC, name ";
        } elseif( $s['sort-by'] == 'sequence-desc' ) {
            $strsql .= "ORDER BY sequence DESC, name ";
        } elseif( $s['sort-by'] == 'startdate-asc' ) {
            $strsql .= "ORDER BY start_date, name ";
        } elseif( $s['sort-by'] == 'startdate-desc' ) {
            $strsql .= "ORDER BY start_date DESC, name ";
        }
    }
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'albums', 'fname'=>'id', 
            'fields'=>array('id', 'title'=>'name', 'permalink', 'image-id'=>'image_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.47', 'msg'=>'Unable to load albums', 'err'=>$rc['err']));
    }
    $albums = isset($rc['albums']) ? $rc['albums'] : array();

    foreach($albums as $aid => $album) {
        if( isset($request['uri_split'][1]) && $request['uri_split'][1] == $album['permalink'] ) {
            //
            // Load albums
            //
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
                $images[$iid]['url'] = $request['page']['path'] . ($request['page']['path'] != '/' ? '/' : '') . $album['permalink'] . '/' . $image['permalink'];
                if( isset($request['uri_split'][2]) && $request['uri_split'][2] == $image['permalink'] ) {
                    $blocks[] = array(
                        'type' => 'title',
                        'title' => $request['page']['title'] . ' - ' . $album['title'] . ($image['title'] != '' ? ' - ' . $image['title'] : ''),
                        );
                    $blocks[] = array(
                        'type' => 'image',
                        'image-id' => $image['image-id'],
                        'image-permalink' => $image['permalink'],
                        'image-list' => $images,
                        'content' => $image['content'],
                        'base-url' => $request['page']['path'] . '/' . $album['permalink'],
                        );
                    return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
                }
            }

            $blocks[] = array(
                'type' => 'title',
                'title' => $s['title'] . ' - ' . $album['title'],
                );
            $blocks[] = array(
                'type' => 'gallery',
                'layout' => 'originals',
                'items' => $images,
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        $albums[$aid]['page'] = 0;
        $albums[$aid]['url'] = $request['page']['path'] . '/' . $album['permalink'];
        $albums[$aid]['title-position'] = 'below';
    }
    if( isset($s['title']) && $s['title'] != '' && isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'title',
            'title' => $s['title'],
            'content' => $s['content'],
            );
    } elseif( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title',
            'title' => $s['title'],
            );
    }

    $blocks[] = array(
        'type' => 'imagebuttons',
        'items' => $albums,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
