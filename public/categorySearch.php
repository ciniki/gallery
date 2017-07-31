<?php
//
// Description
// -----------
// Search the list of categories.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to search for the exhibition contacts.
// start_needle:        The search string to use.
// 
// Returns
// -------
//
function ciniki_gallery_categorySearch($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search String'), 
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
    $rc = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.categorySearch', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the number of customers in each status for the business, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT DISTINCT category "
        . "FROM ciniki_gallery_albums "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND category <> '' "
        . "AND category LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('category')),
        ));
    return $rc;
}
?>
