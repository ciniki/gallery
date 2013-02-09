<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_gallery_web_imageDetails($ciniki, $settings, $business_id, $permalink) {

	$strsql = "SELECT ciniki_gallery.id, name, permalink, image_id, "
		. "album, webflags, description, "
		. "IF((ciniki_gallery.webflags&0x01)=1, 'yes', 'no') AS hidden, "
		. "date_added, last_updated "
		. "FROM ciniki_gallery "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'image');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['image']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'272', 'msg'=>'Unable to find gallery image'));
	}
	$image = array('title'=>$rc['image']['name'],
		'category'=>$rc['image']['album'],
		'image_id'=>$rc['image']['image_id'],
		'details'=>'',
		'description'=>$rc['image']['description'],
		'awards'=>'',
		'date_added'=>$rc['image']['date_added'],
		'last_updated'=>$rc['image']['last_updated']);

	return array('stat'=>'ok', 'image'=>$image);
}
?>
