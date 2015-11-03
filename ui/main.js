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
	this.init = function() {
		//
		// The panel to list the albums
		//
		this.albums = new M.panel('Albums',
			'ciniki_gallery_main', 'albums',
			'mc', 'medium', 'sectioned', 'ciniki.gallery.main.albums');
		this.albums.data = {};
		this.albums.sections = {
			'albums':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'addTxt':'New Album',
				'addFn':'M.ciniki_gallery_main.editAlbum(\'M.ciniki_gallery_main.showAlbums();\',0);',
				},
		};
		this.albums.sectionData = function(s) {
			return this.data.albums;
		};
		this.albums.cellValue = function(s, i, j, d) {
			return d.album.name + '<span class="count">' + d.album.count + '</span>';
		};
		this.albums.rowFn = function(s, i, d) {
			return 'M.ciniki_gallery_main.showList(\'M.ciniki_gallery_main.showAlbums();\',\'' + d.album.id + '\',\'' + escape(d.album.name) + '\');';
		};
		this.albums.addButton('add', 'Add', 'M.ciniki_gallery_main.editAlbum(\'M.ciniki_gallery_main.showAlbums();\',0);');
		this.albums.addClose('Back');

		//
		// The edit panel for an album
		//
		this.album = new M.panel('Edit Album',
			'ciniki_gallery_main', 'album',
			'mc', 'medium', 'sectioned', 'ciniki.gallery.main.album');
		this.album.default_data = {};
		this.album.data = {};
		this.album.album_id = 0;
		this.album.sections = {
			'info':{'label':'Album Details', 'type':'simpleform', 'fields':{
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
				'delete':{'label':'Remove Album', 'fn':'M.ciniki_gallery_main.deleteAlbum();'},
			}},
		};
		this.album.fieldValue = function(s, i, d) { 
			if( this.data[i] != null ) { return this.data[i]; } 
			return ''; 
		};
		this.album.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.gallery.albumHistory', 'args':{'business_id':M.curBusinessID, 
				'album_id':this.album_id, 'field':i}};
		}
		this.album.addButton('save', 'Save', 'M.ciniki_gallery_main.saveAlbum();');
		this.album.addClose('Cancel');

		//
		// The panel to list the images by album
		//
		this.list = new M.panel('Album',
			'ciniki_gallery_main', 'list',
			'mc', 'wide', 'sectioned', 'ciniki.gallery.main.list');
		this.list.data = {};
		this.list.album = '';
		this.list.sections = {
			'images':{'label':'', 'type':'simplethumbs'},
			};
		this.list.noData = function(s) {
			return this.sections[s].noData;
		};
		this.list.sectionData = function(s) {
			return this.data;
		};
		this.list.thumbFn = function(s, i, d) {
			return 'M.ciniki_gallery_main.showEdit(\'M.ciniki_gallery_main.showList();\',\'' + d.image.id + '\');';
		};
		this.list.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.gallery.imageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'album_id':M.ciniki_gallery_main.list.album_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.list.addDropImageRefresh = function() {
			M.ciniki_gallery_main.showList();
		};
		this.list.addButton('add', 'Add', 'M.ciniki_gallery_main.showEdit(\'M.ciniki_gallery_main.showList();\',0,M.ciniki_gallery_main.list.album_id);');
//		this.list.addButton('tools', 'Tools', 'M.ciniki_gallery_main.tools.show(\'M.ciniki_gallery_main.showList();\');');
		this.list.addButton('edit', 'Edit', 'M.ciniki_gallery_main.editAlbum(\'M.ciniki_gallery_main.showList();\',M.ciniki_gallery_main.list.album_id);');
		this.list.addClose('Back');

		//
		// The panel to display the edit form
		//
		this.edit = new M.panel('Edit Image',
			'ciniki_gallery_main', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.gallery.main.edit');
		this.edit.default_data = {};
		this.edit.data = {};
		this.edit.sections = {
			'_image':{'label':'Photo', 'type':'imageform', 'fields':{
				'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
			}},
			'info':{'label':'Information', 'type':'simpleform', 'fields':{
				'name':{'label':'Title', 'type':'text'},
//				'album':{'label':'Album', 'type':'text', 'livesearch':'yes'},
				'album_id':{'label':'Album', 'type':'select', 'options':{}},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
			}},
			'_description':{'label':'Description', 'type':'simpleform', 'fields':{
				'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_gallery_main.saveImage();'},
				'delete':{'label':'Remove Image', 'fn':'M.ciniki_gallery_main.deleteImage();'},
			}},
		};
		this.edit.fieldValue = function(s, i, d) { 
			if( this.data[i] != null ) { return this.data[i]; } 
			return ''; 
		};
//		this.edit.liveSearchCb = function(s, i, value) {
//			if( i == 'album' ) {
//				var rsp = M.api.getJSONBgCb('ciniki.gallery.imageAlbumSearch', 
//					{'business_id':M.curBusinessID, 
//					'start_needle':value, 'limit':25},
//					function(rsp) { 
//						M.ciniki_gallery_main.edit.liveSearchShow(s, i, M.gE(M.ciniki_gallery_main.edit.panelUID + '_' + i), rsp.albums); 
//					});
//			}
//		};
//		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
//			if( f == 'album' ) { return d.album.name; }
//			return '';
//		};
//		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
//			if( f == 'album' ) {
//				return 'M.ciniki_gallery_main.edit.updateField(\'' + s + '\',\'album\',\'' + escape(d.album.name) + '\');';
//			}
//		};
//		this.edit.updateField = function(s, fid, result) {
//			M.gE(this.panelUID + '_' + fid).value = unescape(result);
//			this.removeLiveSearch(s, fid);
//		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.gallery.imageHistory', 'args':{'business_id':M.curBusinessID, 
				'gallery_image_id':this.gallery_image_id, 'field':i}};
		}
		this.edit.addDropImage = function(iid) {
			this.setFieldValue('image_id', iid);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_gallery_main.saveImage();');
		this.edit.addClose('Cancel');

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
	};

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
			alert('App Error');
			return false;
		}
	
		this.album.sections.info.fields.sequence.active = 'no';
		this.album.sections.dates.active = 'no';
		this.album.sections.dates.fields.start_date.active = 'no';
		this.album.sections.dates.fields.end_date.active = 'no';
		if( M.curBusiness.modules['ciniki.gallery'] != null ) {
			if( (M.curBusiness.modules['ciniki.gallery'].flags&0x02) > 0 ) {
				this.album.sections.dates.active = 'yes';
				this.album.sections.dates.fields.start_date.active = 'yes';
			}
			if( (M.curBusiness.modules['ciniki.gallery'].flags&0x04) > 0 ) {
				this.album.sections.dates.active = 'yes';
				this.album.sections.dates.fields.end_date.active = 'yes';
			}
		}
	
		if( args.add != null && args.add == 'yes' ) {
			this.showEdit(cb, 0, args.album);
		} else if( args.img_id != null && args.img_id > 0 ) {
			this.showEdit(cb, args.img_id);
		} else {
			this.list.album = null;
			this.showAlbums(cb);
		}
		return true;
	};

	this.showAlbums = function(cb) {
		M.api.getJSONCb('ciniki.gallery.albumList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_gallery_main.albums;
				p.data.albums = rsp.albums;
				p.refresh();
				p.show(cb);
			});
	};


	this.editAlbum = function(cb, aid) {
		if( aid != null ) { this.album.album_id = aid; }
		if( this.album.album_id > 0 ) {
			M.api.getJSONCb('ciniki.gallery.albumGet', 
				{'business_id':M.curBusinessID, 'album_id':this.album.album_id}, function(rsp) {
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
					{'business_id':M.curBusinessID, 
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
			var rsp = M.api.postJSONFormData('ciniki.gallery.albumAdd', {'business_id':M.curBusinessID}, c,
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
		if( confirm('Are you sure you want to delete this album?') ) {
			var rsp = M.api.getJSONCb('ciniki.gallery.albumDelete', 
				{'business_id':M.curBusinessID, 'album_id':this.album.album_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_gallery_main.album.destroy();
					M.ciniki_gallery_main.list.close();
				});
		}
	};

	this.showList = function(cb, aid, aname) {
		if( aid != null ) { this.list.album_id = aid; } 
		if( aname != null ) { 
			this.list.album_name = unescape(aname); 
			this.list.title = unescape(aname);
		}
		M.api.getJSONCb('ciniki.gallery.imageList', {'business_id':M.curBusinessID, 
			'album_id':this.list.album_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_gallery_main.list;
				p.data = rsp.images;

				p.refresh();
				p.show(cb);
			});
	};

	this.showEdit = function(cb, iid, aid) {
		if( iid != null ) { this.edit.gallery_image_id = iid; }
		if( this.edit.gallery_image_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.gallery.imageGet', 
				{'business_id':M.curBusinessID, 'gallery_image_id':this.edit.gallery_image_id}, function(rsp) {
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
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.reset();
			this.edit.data = {};
			if( aid != null ) {
				this.edit.data['album_id'] = aid;
			}
			M.api.getJSONCb('ciniki.gallery.albumList', {'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_gallery_main.edit;
				p.sections.info.fields.album_id.options = {};
				if( rsp.albums != null ) {
					for(i in rsp.albums) {
						p.sections.info.fields.album_id.options[rsp.albums[i].album.id] = rsp.albums[i].album.name;
					}
				}
				p.refresh();
				p.show(cb);
			});
//			this.edit.refresh();
//			this.edit.show(cb);
		}
	};

	this.saveImage = function() {
		if( this.edit.gallery_image_id > 0 ) {
			var c = this.edit.serializeFormData('no');
			if( c != '' ) {
				var rsp = M.api.postJSONFormData('ciniki.gallery.imageUpdate', 
					{'business_id':M.curBusinessID, 
					'gallery_image_id':this.edit.gallery_image_id}, c,
						function(rsp) {
							if( rsp.stat != 'ok' ) {
								M.api.err(rsp);
								return false;
							} 
							M.ciniki_gallery_main.edit.close();
						});
			} else {
				M.ciniki_gallery_main.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONFormData('ciniki.gallery.imageAdd', {'business_id':M.curBusinessID}, c,
				function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_gallery_main.edit.close();
				});
		}
	};

	this.deleteImage = function() {
		if( confirm('Are you sure you want to delete this image?') ) {
			var rsp = M.api.getJSONCb('ciniki.gallery.imageDelete', 
				{'business_id':M.curBusinessID, 'gallery_image_id':this.edit.gallery_image_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_gallery_main.edit.close();
				});
		}
	};
}
