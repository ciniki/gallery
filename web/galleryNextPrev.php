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
function ciniki_gallery_web_galleryNextPrev($ciniki, $settings, $business_id, $args) {
// $permalink, $img, $type) {

	//
	// Get the position of the image in the gallery.
	// Count the number of items before the specified image, then use
	// that number to LIMIT a query
	//
	$strsql = "SELECT COUNT(*) AS pos_num "
		. "FROM ciniki_gallery "
		. "WHERE ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_gallery.webflags&0x01) = 0 ";
	if( $args['type'] == 'album' ) {
		$strsql .= "AND ciniki_gallery.album_id = '" . ciniki_core_dbQuote($ciniki, $args['img']['album_id']) . "' ";
	}
	$strsql .= "AND ciniki_gallery.date_added > '" . ciniki_core_dbQuote($ciniki, $args['img']['date_added']) . "' ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'position');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['position']['pos_num']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'273', 'msg'=>'Unable to load image'));
	}
	$offset = $rc['position']['pos_num'];

	//
	// Get the previous and next photos
	//
	$strsql = "SELECT id, name, permalink "
		. "FROM ciniki_gallery "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (webflags&0x01) = 0 ";
	if( $args['type'] == 'album' ) {
		$strsql .= "AND album_id = '" . ciniki_core_dbQuote($ciniki, $args['img']['album_id']) . "' ";
	}
	$strsql .= "ORDER BY ciniki_gallery.date_added DESC ";
	if( $offset == 0 ) {
		$strsql .= "LIMIT 3 ";
	} elseif( $offset > 0 ) {
		$strsql .= "LIMIT " . ($offset-1) . ", 3";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'277', 'msg'=>'Unable to load image'));
	}
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'next');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$prev = NULL;
	if( $offset > 0 && isset($rc['rows'][0]) && $rc['rows'][0]['permalink'] != $args['permalink'] ) {
		$prev = $rc['rows'][0];
	}
	$next = NULL;
	if( $offset > 0 && isset($rc['rows'][2]) ) {
		$next = $rc['rows'][2];
	} elseif( $offset == 0 && isset($rc['rows'][1]) ) {
		$next = $rc['rows'][1];
	}

	//
	// If the image requested is at the end of the gallery, then
	// get the first image
	//
	if( $rc['num_rows'] < 3 ) {
		$strsql = "SELECT id, name, permalink "
			. "FROM ciniki_gallery "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND (webflags&0x01) = 0 ";
		if( $args['type'] == 'album' ) {
			$strsql .= "AND album_id = '" . ciniki_core_dbQuote($ciniki, $args['img']['album_id']) . "' ";
		}
		$strsql .= "ORDER BY ciniki_gallery.date_added DESC " 	// SORT to get the newest image first
			. "LIMIT 1"
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'next');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['next']) 
			&& $rc['next']['permalink'] != $args['permalink']	// Make sure it's not the same image
			) {
			$next = $rc['next'];
		}
	}

	//
	// If the image is at begining of the gallery, then get the last image
	//
	if( $offset == 0 ) {
		$strsql = "SELECT id, name, permalink "
			. "FROM ciniki_gallery "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND (webflags&0x01) = 0 ";
		if( $args['type'] == 'album' ) {
			$strsql .= "AND album_id = '" . ciniki_core_dbQuote($ciniki, $args['img']['album_id']) . "' ";
		}
		$strsql .= "ORDER BY ciniki_gallery.date_added ASC " 	// SORT to get the oldest image first
			. "LIMIT 1"
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'prev');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['prev']) 
			&& $rc['prev']['permalink'] != $args['permalink']		// Check not a single image, and going to loop
			) {
			$prev = $rc['prev'];
		}
	}

	return array('stat'=>'ok', 'next'=>$next, 'prev'=>$prev);
}
?>
