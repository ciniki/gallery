<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the image to.
// gallery_image_id:	The ID of the image to get.
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
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the main information
	//
	$strsql = "SELECT ciniki_gallery_albums.id, "
		. "ciniki_gallery_albums.name, "
		. "ciniki_gallery_albums.permalink, "
		. "ciniki_gallery_albums.webflags, "
		. "ciniki_gallery_albums.description "
		. "FROM ciniki_gallery_albums "
		. "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_gallery_albums.id = '" . ciniki_core_dbQuote($ciniki, $args['album_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.gallery', array(
		array('container'=>'albums', 'fname'=>'id', 'name'=>'album',
			'fields'=>array('id', 'name', 'permalink', 'webflags', 'description')),
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
