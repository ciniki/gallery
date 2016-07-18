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
function ciniki_gallery_albumGet($ciniki) {
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
    // Load business timezone info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
//  $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//  $intl_currency = $rc['settings']['intl-default-currency'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
    $rc = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.albumGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_gallery_albums.id, "
        . "ciniki_gallery_albums.name, "
        . "ciniki_gallery_albums.permalink, "
        . "ciniki_gallery_albums.webflags, "
        . "ciniki_gallery_albums.sequence, "
        . "ciniki_gallery_albums.start_date, "
        . "ciniki_gallery_albums.end_date, "
        . "ciniki_gallery_albums.description "
        . "FROM ciniki_gallery_albums "
        . "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_gallery_albums.id = '" . ciniki_core_dbQuote($ciniki, $args['album_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'albums', 'fname'=>'id', 'name'=>'album',
            'fields'=>array('id', 'name', 'permalink', 'webflags', 
                'sequence', 'start_date', 'end_date', 'description'),
            'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'end_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['albums']) ) {
        return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'1704', 'msg'=>'Unable to find image'));
    }
    $album = $rc['albums'][0]['album'];
    
    return array('stat'=>'ok', 'album'=>$album);
}
?>
