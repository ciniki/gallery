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
function ciniki_gallery_albumList(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Load the web settings to determine how gallery albums should be sorted
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    $rc =  ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'business_id', $args['business_id'], 'ciniki.web', 'settings', 'page-gallery');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $settings = $rc['settings'];
    
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
    $ac = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.albumList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load the list of albums and image counts
    //
    $strsql = "SELECT ciniki_gallery_albums.id, "
        . "ciniki_gallery_albums.category, "
        . "ciniki_gallery_albums.name, "
        . "ciniki_gallery_albums.sequence, "
        . "ciniki_gallery_albums.start_date, "
        . "COUNT(ciniki_gallery.id) AS count "
        . "FROM ciniki_gallery_albums "
        . "LEFT JOIN ciniki_gallery ON ("
            . "ciniki_gallery_albums.id = ciniki_gallery.album_id "
            . "AND ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($args['category']) ) {
        $strsql .= "AND ciniki_gallery_albums.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
    }
    $strsql .= "GROUP BY ciniki_gallery_albums.id ";
    if( !isset($settings['page-gallery-album-sort']) 
        || $settings['page-gallery-album-sort'] == 'name-asc' ) {
        $strsql .= "ORDER BY category, name ";
    } elseif( $settings['page-gallery-album-sort'] == 'name-desc' ) {
        $strsql .= "ORDER BY category, name DESC ";
    } elseif( $settings['page-gallery-album-sort'] == 'sequence-asc' ) {
        $strsql .= "ORDER BY sequence ASC, name ";
    } elseif( $settings['page-gallery-album-sort'] == 'sequence-desc' ) {
        $strsql .= "ORDER BY sequence DESC, category, name ";
    } elseif( $settings['page-gallery-album-sort'] == 'startdate-desc' ) {
        $strsql .= "ORDER BY start_date DESC, category, name ";
    } elseif( $settings['page-gallery-album-sort'] == 'startdate-desc' ) {
        $strsql .= "ORDER BY start_date DESC, category, name ";
    }
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'albums', 'fname'=>'id', 'fields'=>array('id', 'category', 'name', 'count')), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['albums']) ) {
        $rsp = array('stat'=>'ok', 'albums'=>$rc['albums']);
    } else {
        $rsp = array('stat'=>'ok', 'albums'=>array());
    }

    //
    // Get the list of categories
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.gallery', 0x08) ) {
        $strsql = "SELECT DISTINCT category "
            . "FROM ciniki_gallery_albums "
            . "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY category "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
            array('container'=>'categories', 'fname'=>'category', 'fields'=>array('category')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = $rc['categories'];
    }

    return $rsp;
}
?>
