var skeleditor = {
	editor: null,
	options: {
		lineNumbers: true,
		autoCloseBrackets: true,
		autoCloseTags: true,
		matchBrackets: true,
		styleActiveLine: true,
		matchTags: {
			bothTags: true
		},
		lineWrapping: false,
		foldGutter: true,
		gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
		theme: "default solarized dark material",
		extraKeys: {
			"Alt-F": "findPersistent",
			"Ctrl-J": "toMatchingTag",
			"Ctrl-Q": function (cm){
				cm.foldCode(cm.getCursor());
			},
			"F11": function (cm){
				cm.setOption("fullScreen", !cm.getOption("fullScreen"));
			},
			"Esc": function (cm){
				if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
			}
		}
	},
	init: function (){
		if (cm_mode.length){
			skeleditor.options.mode = cm_mode;
		}
		if (document.getElementById('code').readOnly){
			skeleditor.options.readOnly = true;
		}
		skeleditor.editor = CodeMirror.fromTextArea(document.getElementById('code'), skeleditor.options);
		var res = emmetCodeMirror(skeleditor.editor);
	},

	getNextCursor: function(text) {
		var cursor = skeleditor.editor.getSearchCursor(text, skeleditor.editor.getCursor());
		if (cursor.findNext()) {
			return cursor;
		}
		cursor = skeleditor.editor.getSearchCursor(text);
		if (cursor.findNext()) {
			return cursor;
		}

		return null;
	},

	search: function (text){
		if (!text) return;
		var cursor = skeleditor.getNextCursor(text);

		if (cursor) {
			skeleditor.editor.setSelection(cursor.from(), cursor.to());
		}

	},

	replace: function (text, replace){
		if (!text) return;

		// si la selection courante n'est pas le texte recherche, highlighter la prochaine occurence et c'est tout
		if (skeleditor.editor.getSelection() !== text) {
			skeleditor.search(text);
			return;
		}

		skeleditor.editor.replaceSelection(replace);
		skeleditor.search(text);
	},

	toggleFullScreen: function() {
		if (skeleditor.editor.getOption('fullScreen')) {
			skeleditor.editor.setOption('fullScreen', false);
			jQuery('body').removeClass('skeleditor_fullscreen');
		}
		else {
			skeleditor.editor.setOption('fullScreen', true);
			jQuery('body').addClass('skeleditor_fullscreen');
		}

	}

}

jQuery(skeleditor.init);
