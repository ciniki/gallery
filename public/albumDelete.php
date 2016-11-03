<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_gallery_albumDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'album_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Album'),
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
    $rc = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.albumDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing image information
    //
    $strsql = "SELECT ciniki_gallery_albums.id, ciniki_gallery_albums.uuid, "
        . "COUNT(ciniki_gallery.id) AS num_images "
        . "FROM ciniki_gallery_albums "
        . "LEFT JOIN ciniki_gallery ON (ciniki_gallery_albums.id = ciniki_gallery.album_id "
            . "AND ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_gallery_albums.id = '" . ciniki_core_dbQuote($ciniki, $args['album_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'album');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['album']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.5', 'msg'=>'Gallery album does not exist'));
    }
    $album = $rc['album'];

    if( $album['num_images'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.6', 'msg'=>'The album still has images in it.  Please remove all images before deleting the album.'));
    }
    
    //
    // Remove the album
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    return ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.gallery.album', $args['album_id'], $album['uuid'], 0x07);
}
?>
