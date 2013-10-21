$(function(){
	panel.init();
});


var panel = (function($){
	var objr = {};

	function activeMenu(){
		//highlight current / active link
		$('ul.main-menu li a').each(function(){
			//var url = String(window.location).split("?");
			var url = String(window.location).replace(base_url, ''), url1 = url.split('/'), url2 = url.split("?"),
			link = $($(this))[0].href.replace(base_url, ''), link1 = link.split('/');
			if(link==url || (link1[0]==url1[0] && link1[1]==url1[1]) || link==url2[0])
				$(this).parents('li').addClass('active');
		});
		// $('ul.main-menu > li.active > a').click();
		
		var mmdat = $("#menu_dat");
		mmdat.on('click', function(){
			var vthis = $(this);
			if (mycookies.getCookie("menu_collapsi") == null || mycookies.getCookie("menu_collapsi") == 'no') {
				mycookies.setCookie("menu_collapsi", 'si', 1);
			}else{
				mycookies.setCookie("menu_collapsi", 'no', 1);
			}
			tipoMenu(vthis);

			return false;
		});
		tipoMenu(mmdat);
	};

	function tipoMenu(vthis){
		if (mycookies.getCookie("menu_collapsi") == null || mycookies.getCookie("menu_collapsi") == 'no') {
			$("#main_menu").removeClass("chiqui");
			$("#content.span10").removeClass("big");
			$('i', vthis).removeClass("icon-arrow-right").addClass("icon-arrow-left");
		}else{
			$("#main_menu").addClass("chiqui");
			$("#content.span10").addClass("big");
			$('i', vthis).removeClass("icon-arrow-left").addClass("icon-arrow-right");
		}
	}

	function animeMenu(){
		//animating menus on hover
		$('ul.main-menu li:not(.nav-header,.submenu)').hover(function(){
			$(this).animate({'margin-left':'+=5'},300);
		},
		function(){
			$(this).animate({'margin-left':'-=5'},300);
		});
	};

	function boxBtns(){
		$('.btn-close').click(function(e){
			e.preventDefault();
			$(this).parent().parent().parent().fadeOut();
		});
		$('.btn-minimize').click(function(e){
			e.preventDefault();
			var $target = $(this).parent().parent().next('.box-content');
			if($target.is(':visible')) $('i',$(this)).removeClass('icon-chevron-up').addClass('icon-chevron-down');
			else 					   $('i',$(this)).removeClass('icon-chevron-down').addClass('icon-chevron-up');
			$target.slideToggle();
		});
	};

	objr.menu = function(id){
		var obj = $("#subm"+id);
		if (obj.is(".hide"))
			obj.attr('class', 'show');
		else
			obj.attr('class', 'hide');
	};

	objr.init = function(){
		activeMenu();
		// animeMenu();
		boxBtns();
	};

	return objr;
})(jQuery);




/**
 * Obj para crear un loader cuando se use Ajax
 */
var loader = {
	create: function(wrapper){
		var css = 'style="position: fixed;left:40%; top:0px;background-color:#F9EDBE;padding:5px 10px; z-index:600;-webkit-border-bottom-right-radius: 5px;-webkit-border-bottom-left-radius: 5px;-moz-border-radius-bottomright: 5px;-moz-border-radius-bottomleft: 5px;border-bottom-right-radius: 5px;border-bottom-left-radius: 5px;"', 
		html = '<div id="ajax-loader" {css}> <img src="'+base_url+'application/images/bootstrap/ajax-loaders/ajax-loader-9.gif" width="24" height="24"> Cargando...</div>';
		
		if (wrapper){
			$(wrapper).append(html.replace("{css}", ""));
		}else{
			$("body").append(html.replace("{css}", css));
		}
	},
	close: function(){
		$("#ajax-loader").remove();
	}
};


var mycookies = (function(){
	var objr = {};
	function setCookie(c_name,value,exdays)
	{
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + exdays);
		var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
		document.cookie=c_name + "=" + c_value + "; path=/";
	}

	function getCookie(c_name)
	{
		var c_value = document.cookie;
		var c_start = c_value.indexOf(" " + c_name + "=");
		if (c_start == -1){
	  	c_start = c_value.indexOf(c_name + "=");
	  }
		if (c_start == -1){
	  	c_value = null;
	  }else{
		  c_start = c_value.indexOf("=", c_start) + 1;
		  var c_end = c_value.indexOf(";", c_start);
		  if (c_end == -1){
				c_end = c_value.length;
			}
			c_value = unescape(c_value.substring(c_start,c_end));
		}
		return c_value;
	}

	objr.setCookie = setCookie;
	objr.getCookie = getCookie;
	return objr;
})();