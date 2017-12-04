<?php
//
// Description
// ===========
// This method will update a field names in the gallery.  This can be used to
// merge fields.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to the item is a part of.
// field:           The field to change (album)
// old_value:       The name of the old value.
// new_value:       The new name for the value.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_gallery_fieldUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'field'=>array('required'=>'yes', 'blank'=>'yes', 'validlist'=>array('album'), 'name'=>'Field'), 
        'old_value'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Old value'), 
        'new_value'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'New value'), 
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
    $rc = ciniki_gallery_checkAccess($ciniki, $args['tnid'], 'ciniki.gallery.fieldUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.gallery');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Keep track if anything has been updated
    //
    $updated = 0;

    //
    // Get the list of objects which change, so we can sync them
    //
    $strsql = "SELECT id FROM ciniki_gallery "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND " . $args['field'] . " = '" . ciniki_core_dbQuote($ciniki, $args['old_value']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'items');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'ok');
    }
    $items = $rc['rows'];

    $strsql = "UPDATE ciniki_gallery "
        . "SET " . $args['field'] . " = '" . ciniki_core_dbQuote($ciniki, $args['new_value']) . "', "
        . "last_updated = UTC_TIMESTAMP() "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND " . $args['field'] . " = '" . ciniki_core_dbQuote($ciniki, $args['old_value']) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.gallery');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the change logs
    //
    foreach($items as $inum => $item) {
        $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.gallery', 'ciniki_gallery_history', $args['tnid'], 
            2, 'ciniki_gallery', $item['id'], $args['field'], $args['new_value']);
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.gallery');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    if( $updated > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'gallery');

        //
        // Add to the sync queue so it will get pushed
        //
        foreach($items as $inum => $item) {
            $ciniki['syncqueue'][] = array('push'=>'ciniki.gallery.item', 
                'args'=>array('id'=>$item['id']));
        }
    }

    return array('stat'=>'ok');
}
?>

