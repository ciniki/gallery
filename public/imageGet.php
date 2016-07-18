<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to add the image to.
// gallery_image_id:    The ID of the image to get.
//
// Returns
// -------
//
function ciniki_gallery_imageGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'gallery_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Image'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
    $rc = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.imageGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_gallery.id, "
        . "ciniki_gallery.name, "
        . "ciniki_gallery.permalink, "
        . "ciniki_gallery.album_id, "
        . "ciniki_gallery.webflags, "
        . "ciniki_gallery.image_id, "
        . "ciniki_gallery.description "
        . "FROM ciniki_gallery "
        . "WHERE ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_gallery.id = '" . ciniki_core_dbQuote($ciniki, $args['gallery_image_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'images', 'fname'=>'id', 'name'=>'image',
            'fields'=>array('id', 'name', 'permalink', 'album_id', 'webflags', 'image_id', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['images']) ) {
        return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'263', 'msg'=>'Unable to find image'));
    }
    $image = $rc['images'][0]['image'];

    //
    // Get the list of albums
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_gallery_albums "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'albums', 'fname'=>'id', 'name'=>'album',
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['albums']) ) {
        $albums = array();
    } else {
        $albums = $rc['albums'];
    }
    
    return array('stat'=>'ok', 'image'=>$image, 'albums'=>$albums);
}
?>
