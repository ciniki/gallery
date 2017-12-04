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
function ciniki_gallery_sync_objects($ciniki, &$sync, $tnid, $args) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'objects');
    return ciniki_gallery_objects($ciniki);
}
?>
