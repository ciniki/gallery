//
// The app to add/edit gallery images
//
function ciniki_gallery_main() {
    this.uploadCount = 0;
    this.webFlags = {
        '1':{'name':'Hidden'},
        '5':{'name':'Album Highlight'},
        };
    this.albumWebFlags = {
        '1':{'name':'Hidden'},
        };

    //
    // The panel to list the albums
    //
    this.albums = new M.panel('Albums', 'ciniki_gallery_main', 'albums', 'mc', 'medium', 'sectioned', 'ciniki.gallery.main.albums');
    this.albums.category = '';
    this.albums.data = {};
    this.albums.sections = {
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.modFlagSet('ciniki.gallery', 0x08); },
            },
        'albums':{'label':'Albums', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'addTxt':'New Album',
            'addFn':'M.ciniki_gallery_main.editAlbum(\'M.ciniki_gallery_main.albums.open();\',0);',
            },
    };
//    this.albums.sectionData = function(s) {
//        return this.data.albums;
//    };
    this.albums.cellValue = function(s, i, j, d) {
        if( s == 'categories' ) {
            if( d.category == '' ) { return 'Uncategorized'; }
            return d.category;
        }
        if( s == 'albums' ) {
            return d.name + '<span class="count">' + d.count + '</span>';
        }
    };
    this.albums.rowFn = function(s, i, d) {
        if( s == 'categories' ) {
            return 'M.ciniki_gallery_main.albums.open(null,\'' + escape(d.category) + '\');';
        }
        if( s == 'albums' ) {
            return 'M.ciniki_gallery_main.list.open(\'M.ciniki_gallery_main.albums.open();\',\'' + d.id + '\',\'' + escape(d.name) + '\');';
        }
    };
    this.albums.open = function(cb, cat) {
        if( cat != null ) { this.category = cat; }
        var args = {'tnid':M.curTenantID};
        if( M.modFlagOn('ciniki.gallery', 0x08) ) {
            args['category'] = this.category;
            this.size = 'medium narrowaside';
        } else {
            this.size = 'medium';
        }
        M.api.getJSONCb('ciniki.gallery.albumList', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_gallery_main.albums;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.albums.addButton('add', 'Add', 'M.ciniki_gallery_main.editAlbum(\'M.ciniki_gallery_main.albums.open();\',0);');
    this.albums.addClose('Back');

    //
    // The edit panel for an album
    //
    this.album = new M.panel('Edit Album', 'ciniki_gallery_main', 'album', 'mc', 'medium', 'sectioned', 'ciniki.gallery.main.album');
    this.album.default_data = {};
    this.album.data = {};
    this.album.album_id = 0;
    this.album.sections = {
        'info':{'label':'Album Details', 'type':'simpleform', 'fields':{
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                'active':function() { return M.modFlagSet('ciniki.gallery', 0x08); },
                },
            'name':{'label':'Title', 'type':'text'},
            'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.albumWebFlags},
            'sequence':{'label':'Sequence', 'active':'no', 'type':'text', 'size':'small'},
        }},
        'dates':{'label':'Album Dates', 'type':'simpleform', 'fields':{
            'start_date':{'label':'Start', 'active':'no', 'type':'date'},
            'end_date':{'label':'End', 'active':'no', 'type':'date'},
        }},
        '_description':{'label':'Description', 'type':'simpleform', 'fields':{
            'description':{'label':'', 'type':'textarea', 'size':'medium', 'hidelabel':'yes'},
        }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_gallery_main.saveAlbum();'},
            'delete':{'label':'Remove Album', 'visible':function() {return (M.ciniki_gallery_main.album.album_id>0?'yes':'no');}, 'fn':'M.ciniki_gallery_main.deleteAlbum();'},
        }},
    };
    this.album.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) { return this.data[i]; } 
        return ''; 
    };
    this.album.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.gallery.albumHistory', 'args':{'tnid':M.curTenantID, 
            'album_id':this.album_id, 'field':i}};
    }
    this.album.liveSearchCb = function(s, i, value) {
        if( i == 'category' ) {
            var rsp = M.api.getJSONBgCb('ciniki.gallery.categorySearch', {'tnid':M.curTenantID, 'start_needle':value}, function(rsp) { 
                M.ciniki_gallery_main.album.liveSearchShow(s, i, M.gE(M.ciniki_gallery_main.album.panelUID + '_' + i), rsp.categories); 
            });
        }
    };
    this.album.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'category' ) { return d.category; }
        return '';
    };
    this.album.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.ciniki_gallery_main.album.updateField(\'' + s + '\',\'category\',\'' + escape(d.category) + '\');';
    };
    this.album.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
    this.album.addButton('save', 'Save', 'M.ciniki_gallery_main.saveAlbum();');
    this.album.addClose('Cancel');

    //
    // The panel to list the images by album
    //
    this.list = new M.panel('Album', 'ciniki_gallery_main', 'list', 'mc', 'full', 'sectioned', 'ciniki.gallery.main.list');
    this.list.data = {};
    this.list.album = '';
    this.list.nplist = [];
    this.list.sections = {
        'images':{'label':'', 'imgsize':'medium', 'type':'simplethumbs'},
        };
    this.list.noData = function(s) {
        return this.sections[s].noData;
    };
    this.list.sectionData = function(s) {
        return this.data;
    };
    this.list.thumbFn = function(s, i, d) {
        return 'M.ciniki_gallery_main.edit.open(\'M.ciniki_gallery_main.list.open();\',\'' + d.image.id + '\',null,M.ciniki_gallery_main.list.nplist);';
    };
    this.list.addDropImage = function(iid) {
        var rsp = M.api.getJSON('ciniki.gallery.imageAdd',
            {'tnid':M.curTenantID, 'image_id':iid, 'album_id':M.ciniki_gallery_main.list.album_id});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    };
    this.list.addDropImageRefresh = function() {
        M.ciniki_gallery_main.list.open();
    };
    this.list.open = function(cb, aid, aname) {
        if( aid != null ) { this.album_id = aid; } 
        if( aname != null ) { 
            this.album_name = unescape(aname); 
            this.title = unescape(aname);
        }
        M.api.getJSONCb('ciniki.gallery.imageList', {'tnid':M.curTenantID, 
            'album_id':this.album_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_gallery_main.list;
                p.data = rsp.images;
                p.nplist = (rsp.nplist != null ? rsp.nplist : null);
                p.refresh();
                p.show(cb);
            });
    };
    this.list.addButton('add', 'Add', 'M.ciniki_gallery_main.edit.open(\'M.ciniki_gallery_main.list.open();\',0,M.ciniki_gallery_main.list.album_id);');
//      this.list.addButton('tools', 'Tools', 'M.ciniki_gallery_main.tools.show(\'M.ciniki_gallery_main.list.open();\');');
    this.list.addButton('edit', 'Edit', 'M.ciniki_gallery_main.editAlbum(\'M.ciniki_gallery_main.list.open();\',M.ciniki_gallery_main.list.album_id);');
    this.list.addClose('Back');

    //
    // The panel to display the edit form
    //
    this.edit = new M.panel('Edit Image', 'ciniki_gallery_main', 'edit', 'mc', 'xlarge', 'sectioned', 'ciniki.gallery.main.edit');
    this.edit.default_data = {};
    this.edit.gallery_image_id = 0;
    this.edit.data = {};
    this.edit.nplist = [];
    this.edit.sections = {
        '_image':{'label':'Photo', 'type':'imageform', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
        }},
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
//              'album':{'label':'Album', 'type':'text', 'livesearch':'yes'},
            'album_id':{'label':'Album', 'type':'select', 'options':{}},
            'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
        }},
        '_description':{'label':'Description', 'type':'simpleform', 'fields':{
            'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
        }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_gallery_main.edit.save();'},
            'delete':{'label':'Remove Image', 'fn':'M.ciniki_gallery_main.deleteImage();'},
        }},
    };
    this.edit.imageURL = function(s, i, field, img_id, mN) {
        if( M.modFlagOn('ciniki.gallery', 0x10) ) {
            return M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 
                'image_id':img_id, 'version':'original', 'maxwidth':0, 'maxheight':1200});
        } else {
            return M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 
                'image_id':img_id, 'version':'original', 'maxwidth':0, 'maxheight':600});
        }
    }
    this.edit.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) { return this.data[i]; } 
        return ''; 
    };
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.gallery.imageHistory', 'args':{'tnid':M.curTenantID, 
            'gallery_image_id':this.gallery_image_id, 'field':i}};
    }
    this.edit.addDropImage = function(iid) {
        this.setFieldValue('image_id', iid);
        return true;
    };
    this.edit.open = function(cb, iid, aid, list) {
        if( iid != null ) { this.gallery_image_id = iid; }
        if( list != null ) { this.nplist = list; }
        if( this.gallery_image_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.gallery.imageGet', 
                {'tnid':M.curTenantID, 'gallery_image_id':this.gallery_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_gallery_main.edit;
                    p.data = rsp.image;
                    p.sections.info.fields.album_id.options = {};
                    if( rsp.albums != null ) {
                        for(i in rsp.albums) {
                            p.sections.info.fields.album_id.options[rsp.albums[i].album.id] = rsp.albums[i].album.name;
                        }
                    }
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.sections._buttons.buttons.delete.visible = 'no';
            this.reset();
            this.data = {};
            if( aid != null ) {
                this.data['album_id'] = aid;
            }
            M.api.getJSONCb('ciniki.gallery.albumList', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_gallery_main.edit;
                p.sections.info.fields.album_id.options = {};
                if( rsp.albums != null ) {
                    for(i in rsp.albums) {
                        p.sections.info.fields.album_id.options[rsp.albums[i].id] = rsp.albums[i].name;
                    }
                }
                p.refresh();
                p.show(cb);
            });
        }
    }
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_gallery_main.edit.close();'; }
        if( this.gallery_image_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.gallery.imageUpdate', {'tnid':M.curTenantID, 'gallery_image_id':this.gallery_image_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONFormData('ciniki.gallery.imageAdd', {'tnid':M.curTenantID}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    eval(cb);
                });
        }
    }
    this.edit.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.gallery_image_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_gallery_main.edit.save(\'M.ciniki_gallery_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.gallery_image_id) + 1] + ');\');';
        }
        return null;
    }
    this.edit.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.gallery_image_id) > 0 ) {
            return 'M.ciniki_gallery_main.edit.save(\'M.ciniki_gallery_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.gallery_image_id) - 1] + ');\');';
        }
        return null;
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_gallery_main.edit.save();');
    this.edit.addClose('Cancel');
    this.edit.addButton('next', 'Next');
    this.edit.addLeftButton('prev', 'Prev');

    //
    // The tools available to work on customer records
    //
    this.tools = new M.panel('Gallery Tools',
        'ciniki_gallery_main', 'tools',
        'mc', 'narrow', 'sectioned', 'ciniki.gallery.main.tools');
    this.tools.data = {};
    this.tools.sections = {
        'tools':{'label':'Adjustments', 'list':{
            'categories':{'label':'Edit Album Names', 'fn':'M.startApp(\'ciniki.gallery.fieldupdate\', null, \'M.ciniki_gallery_main.tools.show();\',\'mc\',{\'field\':\'album\',\'fieldname\':\'Album Names\'});'},
            }},
        };
    this.tools.addClose('Back');

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_gallery_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        this.edit.size = M.modFlagOn('ciniki.gallery', 0x10) ? 'xlarge' : 'medium';
    
        this.album.sections.info.fields.sequence.active = 'no';
        this.album.sections.dates.active = 'no';
        this.album.sections.dates.fields.start_date.active = 'no';
        this.album.sections.dates.fields.end_date.active = 'no';
        if( M.curTenant.modules['ciniki.gallery'] != null ) {
            if( (M.curTenant.modules['ciniki.gallery'].flags&0x02) > 0 ) {
                this.album.sections.dates.active = 'yes';
                this.album.sections.dates.fields.start_date.active = 'yes';
            }
            if( (M.curTenant.modules['ciniki.gallery'].flags&0x04) > 0 ) {
                this.album.sections.dates.active = 'yes';
                this.album.sections.dates.fields.end_date.active = 'yes';
            }
        }
    
        if( args.add != null && args.add == 'yes' ) {
            this.edit.open(cb, 0, args.album);
        } else if( args.img_id != null && args.img_id > 0 ) {
            this.edit.open(cb, args.img_id, null, null);
        } else {
            this.list.album = null;
            this.albums.open(cb, null);
        }
        return true;
    };



    this.editAlbum = function(cb, aid) {
        if( aid != null ) { this.album.album_id = aid; }
        if( this.album.album_id > 0 ) {
            M.api.getJSONCb('ciniki.gallery.albumGet', 
                {'tnid':M.curTenantID, 'album_id':this.album.album_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_gallery_main.album;
                    p.data = rsp.album;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.album.reset();
            this.album.data = {};
            this.album.refresh();
            this.album.show(cb);
        }
    };

    this.saveAlbum = function() {
        if( this.album.album_id > 0 ) {
            var c = this.album.serializeFormData('no');
            if( c != '' ) {
                // Set the panel title if name changed on the album list
                var new_name = this.album.formFieldValue(this.album.formField('name'), 'name');
                if( this.album.data.name != new_name ) {
                    M.ciniki_gallery_main.list.title = new_name;
                }
                var rsp = M.api.postJSONFormData('ciniki.gallery.albumUpdate', 
                    {'tnid':M.curTenantID, 
                    'album_id':this.album.album_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } 
                            M.ciniki_gallery_main.album.close();
                        });
            } else {
                M.ciniki_gallery_main.album.close();
            }
        } else {
            var c = this.album.serializeForm('yes');
            var rsp = M.api.postJSONFormData('ciniki.gallery.albumAdd', {'tnid':M.curTenantID}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_gallery_main.album.close();
                });
        }
    };

    this.deleteAlbum = function() {
        M.confirm('Are you sure you want to delete this album?',null,function() {
            var rsp = M.api.getJSONCb('ciniki.gallery.albumDelete', 
                {'tnid':M.curTenantID, 'album_id':M.ciniki_gallery_main.album.album_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_gallery_main.album.destroy();
                    M.ciniki_gallery_main.list.close();
                });
        });
    };




    this.deleteImage = function() {
        M.confirm('Are you sure you want to delete this image?',null,function() {
            var rsp = M.api.getJSONCb('ciniki.gallery.imageDelete', 
                {'tnid':M.curTenantID, 'gallery_image_id':M.ciniki_gallery_main.edit.gallery_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_gallery_main.edit.close();
                });
        });
    };
}
