<?php
//
// Description
// -----------
// This is an admin function to upgrade the albums from being names in the ciniki_gallery
// table to entries in the ciniki_gallery_albums table.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the image belongs to.
// name:                The name of the image.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_gallery_upgradeAlbums(&$ciniki) {
    //
    // Must be a sysadmin to run this
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.16', 'msg'=>'Access denied'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    $strsql = "SELECT DISTINCT tnid, album "
        . "FROM ciniki_gallery "
        . "ORDER BY tnid, album "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $galleries = $rc['rows'];

    foreach($galleries as $gallery) {
        //
        // Create the album, if it doesn't already exist
        //
        $args = array(
            'name'=>$gallery['album'],
            'permalink'=>ciniki_core_makePermalink($ciniki, $gallery['album']),
            'webflags'=>0,
            'description'=>'',
            );
        
        //
        // Check if album already exists for the tenant
        //
        $strsql = "SELECT id, name "
            . "FROM ciniki_gallery_albums "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $gallery['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.17', 'msg'=>'Album already exists: ' . $args['name']));
        }

        $rc = ciniki_core_objectAdd($ciniki, $gallery['tnid'], 'ciniki.gallery.album', $args, 0x07);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $album_id = $rc['id'];

        //
        // Get the images for the album
        //
        $strsql = "SELECT ciniki_gallery.id "
            . "FROM ciniki_gallery "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $gallery['tnid']) . "' "
            . "AND album = '" . ciniki_core_dbQuote($ciniki, $gallery['album']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $images = $rc['rows'];

        //
        // Update the album
        //
        $album_args = array('album_id'=>$album_id);
        foreach($images as $img) {
            $rc = ciniki_core_objectUpdate($ciniki, $gallery['tnid'], 'ciniki.gallery.image', $img['id'], $album_args, 0x07);
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
