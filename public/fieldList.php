<?php
//
// Description
// ===========
// This method will return the list of fields used in the gallery which
// are available for mass update.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the list from.
// 
// Returns
// -------
//
function ciniki_gallery_fieldList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('album'), 'name'=>'Field'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
    $rc = ciniki_gallery_checkAccess($ciniki, $args['tnid'], 'ciniki.gallery.fieldList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT DISTINCT " . $args['field'] . " "
        . "FROM ciniki_gallery "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY " . $args['field'] . " COLLATE latin1_general_cs, " . $args['field'] . " "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'items', 'fname'=>$args['field'], 'name'=>'item',
            'fields'=>array('name'=>$args['field'])),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['items']) ) {
        return array('stat'=>'ok', 'items'=>array());
    }
    return array('stat'=>'ok', 'items'=>$rc['items']);
}
?>
