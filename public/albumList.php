<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get gallery images for.
// type:			The type of participants to get.  Refer to participantAdd for 
//					more information on types.
//
// Returns
// -------
//
function ciniki_gallery_albumList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
    $ac = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.albumList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Load the list of albums and image counts
	//
	$strsql = "SELECT ciniki_gallery_albums.id, "
		. "ciniki_gallery_albums.name, "
		. "COUNT(ciniki_gallery.id) AS count "
		. "FROM ciniki_gallery_albums "
		. "LEFT JOIN ciniki_gallery ON (ciniki_gallery_albums.id = ciniki_gallery.album_id "
			. "AND ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "GROUP BY ciniki_gallery_albums.id "
		. "ORDER BY ciniki_gallery_albums.name "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.gallery', array(
		array('container'=>'albums', 'fname'=>'id', 'name'=>'album',
			'fields'=>array('id', 'name', 'count')), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['albums']) ) {
		return array('stat'=>'ok', 'albums'=>array());
	}
	return array('stat'=>'ok', 'albums'=>$rc['albums']);
}
?>
