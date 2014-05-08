$(function(){

	$(".transit_chekrs").on('click', function() {
		var vt = $(this);
		msb.confirm('Esta seguro de cambiar el estado?', 'cuentas', this,
			function(obj){
				window.location = base_url+"panel/banco/cambia_entransito?"+vt.val();
			}, function(obj){
				if (vt.attr('data-status') == 'Trans')
					vt.removeAttr("checked");
				else
					vt.attr('checked', 'true');
			});
	});

	$(".no_print.del_operation:first a").remove();

	$("#sb_banco").keyup(function(){
		recalculaSaldo();
	});
	calculaSaldo();

	//conciliar
	$("#verConciliacion").on('click', function(){
		var vthis = $(this);
		vthis.attr("href", vthis.attr("data-href")+"&saldob="+$("#sb_banco").val() );
	});

  var ids = [];
  $('.transit_chekrs').each(function(index, el) {
    if ($(this).is(':checked')) {
      ids.push($(this).attr('data-id'));
    }
  });

  var $btn = $('#cambia-fecha-movi');
  $btn.attr('href', $btn.attr('href') + '&ids=' + ids.join(','));

  // Autocomplete clientes
  // $("#dcliente").autocomplete({
  //   source: function(request, response) {
  //     $.ajax({
  //         url: base_url+'panel/facturacion/ajax_get_clientes/',
  //         dataType: "json",
  //         data: {
  //             term : request.term,
  //             did_empresa : $("#did_empresa").val()
  //         },
  //         success: function(data) {
  //             response(data);
  //         }
  //     });
  //   },
  //   minLength: 1,
  //   selectFirst: true,
  //   select: function( event, ui ) {
  //     $("#did_cliente").val(ui.item.id);
  //     $("#dcliente").css("background-color", "#B0FFB0");
  //   }
  // }).on("keydown", function(event){
  //     if(event.which == 8 || event == 46){
  //       $("#dcliente").css("background-color", "#FFD9B3");
  //       $("#did_cliente").val("");
  //     }
  // });
});

function calculaSaldo() {
	var empresa_real = $("#total_saldo").text();

	$("#sb_empresar").text(empresa_real);
}

function recalculaSaldo() {
	var num = parseFloat($("#sb_banco").val()),
	empresa_real = parseFloat( util.quitarFormatoNum($("#total_saldo").text()) ).toFixed(2),
	dif1 = (num-empresa_real).toFixed(2), dif1 = +dif1 || 0,
	dif2 = dif1-( parseFloat( util.quitarFormatoNum($("#sb_cheque_ncob").text()) ) ).toFixed(2), dif2 = +dif2 || 0;

	$("#sb_dif1").text( util.darFormatoNum(dif1) );
	$("#sb_dif2").text( util.darFormatoNum(dif2) );
}