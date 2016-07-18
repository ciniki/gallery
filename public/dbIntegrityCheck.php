<?php
//
// Description
// -----------
// This function will go through the history of the ciniki.customers module and add missing history elements.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_gallery_dbIntegrityCheck($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'fix'=>array('required'=>'no', 'default'=>'no', 'name'=>'Fix Problems'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
    $rc = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.dbIntegrityCheck', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectRefFix');

    if( $args['fix'] == 'yes' ) {
        //
        // Load objects file
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'objects');
        $rc = ciniki_gallery_objects($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $objects = $rc['objects'];

        //
        // Check any references for the objects
        //
        foreach($objects as $o => $obj) {
            $rc = ciniki_core_objectRefFix($ciniki, $args['business_id'], 'ciniki.gallery.'.$o, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        //
        // Update the history for ciniki_gallery
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.gallery', $args['business_id'],
            'ciniki_gallery', 'ciniki_gallery_history', 
            array('uuid', 'name', 'permalink', 'album', 'webflags', 'image_id', 
                'description'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Check for items missing a UUID
        //
        $strsql = "UPDATE ciniki_gallery_history SET uuid = UUID() WHERE uuid = ''";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.gallery');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Remote any entries with blank table_key, they are useless we don't know what they were attached to
        //
        $strsql = "DELETE FROM ciniki_gallery_history WHERE table_key = ''";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.gallery');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
