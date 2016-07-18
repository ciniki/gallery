<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to get gallery images for.
// type:            The type of participants to get.  Refer to participantAdd for 
//                  more information on types.
//
// Returns
// -------
//
function ciniki_gallery_imageList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'album_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Album'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
    $ac = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.imageList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

/*  if( !isset($args['album']) ) {
        //
        // Load the list of albums and image counts
        //
        $strsql = "SELECT IF(album='', 'Uncategorized', album) AS album, "
            . "COUNT(*) AS count "
            . "FROM ciniki_gallery "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "GROUP BY album "
            . "ORDER BY album "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.gallery', array(
            array('container'=>'albums', 'fname'=>'album', 'name'=>'album',
                'fields'=>array('name'=>'album', 'count')), 
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['albums']) ) {
            return array('stat'=>'ok', 'album'=>'', 'images'=>array());
        }
        if( count($rc['albums']) > 1 ) {
            return array('stat'=>'ok', 'albums'=>$rc['albums']);
        }
        if( count($rc['albums']) == 0 ) {
            return array('stat'=>'ok', 'albums'=>array());
        }

        // 
        // If there is only one album, go directly and list images
        //
        $args['album'] = $rc['albums'][0]['album']['name'];
    }
*/
    //
    // Load the list of images for a album
    //
    $strsql = "SELECT ciniki_gallery.id, "
        . "ciniki_gallery.name, "
        . "ciniki_gallery.webflags, "
        . "ciniki_gallery.image_id, "
        . "ciniki_gallery.description "
        . "FROM ciniki_gallery "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($args['album_id']) && $args['album_id'] != '' ) {
        $strsql .= "AND album_id = '" . ciniki_core_dbQuote($ciniki, $args['album_id']) . "' ";
    }
/*  if( $args['album'] == 'Uncategorized' ) {
        $strsql .= "AND album = '' ";
    } else {
        $strsql .= "AND album = '" . ciniki_core_dbQuote($ciniki, $args['album']) . "' ";
    } */
    $strsql .= "ORDER BY ciniki_gallery.date_added DESC ";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'images', 'fname'=>'id', 'name'=>'image',
            'fields'=>array('id', 'name', 'webflags', 'image_id', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['images']) ) {
//      return array('stat'=>'ok', 'album'=>$args['album'], 'images'=>array());
        return array('stat'=>'ok', 'images'=>array());
    }
    $images = $rc['images'];

    //
    // Add thumbnail information into list
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
    foreach($images as $inum => $image) {
        if( isset($image['image']['image_id']) && $image['image']['image_id'] > 0 ) {
            $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $image['image']['image_id'], 75);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1501', 'msg'=>'Unable to load image', 'err'=>$rc['err']));
            }
            $images[$inum]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
        }
    }

//  return array('stat'=>'ok', 'album'=>$args['album'], 'images'=>$images);
    return array('stat'=>'ok', 'images'=>$images);
}
?>
