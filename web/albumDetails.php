<?php
//
// Description
// -----------
// This function will return a list of categories for the web galleries
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
// type:			The list to return, either by category or year.
//
//					- category
//					- year
//
// type_name:		The name of the category or year to list.
//
// Returns
// -------
// <images>
//		[title="Slow River" permalink="slow-river" image_id="431" 
//			caption="Based on a photograph taken near Slow River, Ontario, Pastel, size: 8x10" sold="yes"
//			last_updated="1342653769"],
//		[title="Open Field" permalink="open-field" image_id="217" 
//			caption="An open field in Ontario, Oil, size: 8x10" sold="yes"
//			last_updated="1342653769"],
//		...
// </images>
//
function ciniki_gallery_web_albumDetails($ciniki, $settings, $business_id, $args) {
	//
	// Get the gallery information
	//
	if( isset($args['type']) && $args['type'] == 'gallery' ) {
		$strsql = "SELECT ciniki_gallery_albums.name, "
			. "ciniki_gallery_albums.permalink, "
			. "ciniki_gallery_albums.description "
			. "FROM ciniki_gallery_albums "
			. "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND (ciniki_gallery_albums.webflags&0x01) = 0 "
			. "AND ciniki_gallery_albums.permalink = '" . ciniki_core_dbQuote($ciniki, $args['type_name']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'album');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['album']) ) {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'1712', 'msg'=>'I\'m sorry, we are unable to find an album by that name.'));
		}
		$album = $rc['album'];
	} else {
		$album = array('name'=>'');
	}

	return array('stat'=>'ok', 'album'=>$album);
}
?>
