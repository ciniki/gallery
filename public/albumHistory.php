<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the details for.
//
// Returns
// -------
// <history>
//  <action user_id="2" date="May 12, 2012 10:54 PM" value="photographs" age="2 months" user_display_name="Andrew" />
//  ...
// </history>
//
function ciniki_gallery_albumHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'album_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Album'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
    $rc = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.albumHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.gallery', 'ciniki_gallery_history', $args['business_id'], 'ciniki_gallery_albums', $args['album_id'], $args['field']);
}
?>
