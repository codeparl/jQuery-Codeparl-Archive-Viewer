(function($, window) {
    var namespace = "jquery-codeparl-archive-viewer";
    var prefix = "cav-";
    var self;

    function modal() {
        var m =
            '<div class="modal fade" data-backdrop="static" id="cav-modal" data-show="false" role="dialog"  aria-hidden="true">';
        m += '<div class="modal-dialog modal-lg" role="document">';
        m += '<div class="modal-content">';
        m += '<div class="modal-header">';
        m +=
            '<h5 class="modal-title"></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close">';
        m += '<span aria-hidden="true">&times;</span></button></div>';
        m +=
            '<div class="modal-body"><div id="cav-editor"></div></div></div></div></div> ';
        return m;
    }

    function fetchContent(options) {
        if (options.fetchOnload) {
            $.ajax({
                type: "GET",
                url: options.serverPath,
                data: options.params.load,
                dataType: "json",
                success: function(response) {
                    self.html(response.archiveContent.content);
                },
            });
        }
    }

    function fetchSubContent(options) {
        self.on("click." + namespace, ".list-group-item a", function() {
            var $thisButton = $(this);
            var $parent = $thisButton.closest("li.list-group-item");
            var index = (index = $parent.data("index"));
            var type = $parent.data("type");
            var zipName = self.find("ul[data-entry]").first().data("entry");

            shouldContinue = toggleFolderOpen($parent, $thisButton, type);

            if (shouldContinue) {
                $thisButton.addClass("disabled");
                $parent.append($('<div class="linePreloader"></div>'));

                setTimeout(function() {
                    $.ajax({
                        url: options.serverPath,
                        method: "GET",
                        dataType: "json",
                        data: {
                            "zip-name": zipName + ".zip",
                            "file-index": index,
                            "file-type": type,
                            "file-name": $thisButton.text(),
                            "file-path": $parent.data("path"),
                            "sub-list": 1,
                        },
                    }).then(function(response) {

                        self.trigger('sub-content-loaded');
                        $thisButton.removeClass('disabled');
                        $('.linePreloader').remove();

                        if (type === "folder") {
                            $parent.data("open", true);
                            $parent.find("i").eq(0).toggleClass("fa-minus fa-plus");
                            $parent.find("i").eq(1).toggleClass("fa-folder-open fa-folder");

                            $(response.content).slideDown(100)
                                .insertAfter($thisButton)
                            $parent.data("open", true);
                            return;
                        } else {
                            downloadContent(response, zipName, index, options);
                            previewContent(response, options);
                        }
                    });
                }, 2000);

            } //if
        });
    }

    function toggleFolderOpen($parent, $thisButton, type) {
        var shouldContinue = true;
        if (type === "folder") {
            if ($parent.data("open")) {
                $thisButton.next("ul").slideUp("fast", function() {
                    $parent.data("open", false);
                    $parent.find("i").eq(0).toggleClass("fa-minus fa-plus ");
                    $parent.find("i").eq(1).toggleClass("fa-folder-open fa-folder");
                    $(this).remove();
                });
                shouldContinue = false;
            }
        } //if

        return shouldContinue;
    }

    function previewContent(data, options) {
        if (data.content === null) return;

        //pass data so that the user 
        //can use it in diffrent way
        options.onPreview(data);

        if (data.content !== null && window.ace) {
            $("body").prepend(modal());
            var $modal = $("#cav-modal");
            $("#cav-editor").css("height", options.editor.editorHeight);

            //handle image file types
            if (data.info.isImage) {
                $modal
                    .find(".modal-body")
                    .html(
                        $("<img>")
                        .attr({ alt: data.info.path, src: data.content })
                        .addClass("img-fluid")
                    );
            } else {
                //handle textual content
                var editor = ace.edit("cav-editor");
                editor.setTheme("ace/theme/" + options.editor.theme);
                editor.session.setMode("ace/mode/" + data.info.fileType.toLowerCase());
                editor.session.setValue(data.content);
                editor.setOptions({
                    fontSize: 16,
                    readOnly: true,
                });
            }

            //remove the modal after closing it
            $modal
                .modal("show")
                .on("bs-hidden-modal", function() {
                    $(this).remove();
                })
                .find(".modal-title")
                .text(data.info.path);
        }
    }

    function downloadContent(data, name, index, options) {
        //This is not a displayable file so,
        // allow the user to download it.
        if (data.content === null) {
            var qs = "name=" + name + ".zip&index=" + index;
            var url = window.location.protocol + "//" + window.location.host;
            url += "/" + options.downloadPath + "?" + qs;
            window.open(url, "_top");
            return;
        }
    }
    var methods = {
        init: function(options) {
            self = this;
            options = $.extend({}, $.fn.codeparlArchiveViewer.options, options);
            this.addClass(prefix + "archive-viewer");
            this.css(options.css);

            var p =
                '<div class="cav-preloader" ><div class="lds-dual-ring"></div></div>';
            self.html(p);

            if (options.archiveContent !== null && !options.fetchOnload)
                self.html(options.archiveContent.content);

            fetchContent(options);
            fetchSubContent(options);
        },
    };

    $.fn.codeparlArchiveViewer = function(options) {
        //if the user passed a method name,
        //call that method
        if (methods[options]) {
            var object = this;
            var args = Array.prototype.slice.call(arguments, 1);
            methods[options].apply(object, args);
        } else if ($.type(options) === "object" || !options) {
            //here, the user is not trying to call any method
            //but initializing the archive viewer
            methods.init.apply(this, arguments);
        } else {
            $.error("The method " + options + " does not exist on " + namespace);
        }
    };

    $.fn.codeparlArchiveViewer.options = {
        serverPath: "server/content-server.php",
        downloadPath: "projects/jquery-archive-viewer/server/content-server.php",
        fetchOnload: true,
        archiveContent: null, //must be an object in the form of archiveContent.content

        params: {
            load: "archive=jquery-archive-viewer.zip",
            list: "zip-list",
        },
        css: {
            maxHeight: "550px",
            minHeight: "550px",
        },
        editor: {
            editorHeight: "550px",
            theme: "tomorrow",
        },

        onPreview: function(data) {

            //do something with the data 
            //from the server. may be you don't want 
            //to use ace to handle the content.

            //data has the following properties:
            //data.content and data.info 
        }
    };
})(jQuery, window);