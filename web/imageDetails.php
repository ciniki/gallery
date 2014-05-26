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

	$strsql = "SELECT ciniki_gallery.id, "
		. "ciniki_gallery.name, ciniki_gallery.permalink, "
		. "ciniki_gallery.image_id, "
		. "ciniki_gallery.album_id, "
		. "ciniki_gallery_albums.name AS album, "
		. "ciniki_gallery_albums.permalink AS album_permalink, "
		. "ciniki_gallery.webflags, ciniki_gallery.description, "
		. "IF((ciniki_gallery.webflags&0x01)=1, 'yes', 'no') AS hidden, "
		. "ciniki_gallery.date_added, ciniki_gallery.last_updated "
		. "FROM ciniki_gallery, ciniki_gallery_albums "
		. "WHERE ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_gallery.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		. "AND ciniki_gallery.album_id = ciniki_gallery_albums.id "
		. "AND ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'image');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['image']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'272', 'msg'=>'Unable to find gallery image'));
	}
	$image = array('title'=>$rc['image']['name'],
		'permalink'=>$rc['image']['permalink'],
		'album_id'=>$rc['image']['album_id'],
		'category'=>$rc['image']['album'],
		'category_permalink'=>$rc['image']['album_permalink'],
		'image_id'=>$rc['image']['image_id'],
		'details'=>'',
		'description'=>$rc['image']['description'],
		'awards'=>'',
		'date_added'=>$rc['image']['date_added'],
		'last_updated'=>$rc['image']['last_updated'],
		);

	return array('stat'=>'ok', 'image'=>$image);
}
?>
