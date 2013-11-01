$(function(){
	supermodal.init();
});

var supermodal = (function($){
	var objr = {},
	objmodal;

	function init(){
		createBox();

		on("a[rel^=superbox]");
	}

	function eventClick(){
		var vthis = $(this),
		settings = vthis.attr("rel").split("-");
		settings[1] = settings[1].split("x"),
    callback = vthis.attr('data-supermodal-callback'), // callback que se ejecuta antes de mostrar en supermodal.
    autoshow = vthis.attr('data-supermodal-autoshow'); // data attribute para indicar si el modal se auto abrira o no. Valores | true o false.

    if (typeof callback !== 'undefined' && callback !== false) {
      window[vthis.attr('data-supermodal-callback')](vthis, objmodal);
    }

		setIframe(vthis.attr("href"), settings);

    objmodal.find('.modal-body').css({height: settings[1][1]+"px"});

		objmodal.css({
			width: settings[1][0]+"%",
			height: settings[1][1]+"px",
			left: ((100-settings[1][0])/2)+"%",
			marginLeft: "0px",
		});

    if (typeof autoshow === 'undefined' || autoshow === 'true') {
		  objmodal.modal("show");
    }

		return false;
	}
	function setIframe(href, settings){
		var body = $(".modal-body", objmodal);
		body.html('<iframe src="'+href+'" name="'+href+'" style="width:100%;height:99%" frameborder="0" scrolling="auto" hspace="0"></iframe>');
	}

	function createBox(){
		if (objmodal == undefined) {
			var html = '<div id="supermodal" class="modal hide fade">'+
			  '<div class="modal-body nopadd" style="max-height:550px;">'+
			  '</div>'+
			'</div>';
			$("body").append(html);
			objmodal = $("#supermodal");
		}
	}

	function closeBox(){
		objmodal.modal("hide");
	}

	function on(selector){
		$(selector).on("click", eventClick);
	}

	objr.init  = init;
	objr.on    = on;
	objr.close = closeBox;

	return objr;
})(jQuery);