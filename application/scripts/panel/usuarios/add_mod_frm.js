$(function(){
	//marcar y desmarcar los checks box
	$("#list_privilegios .treeview input:checkbox").on('click', function (){
		var elemento_padre = $($(this).parent().get(0)).parent().get(0);
		var numero_hijos = $("ul", elemento_padre).length;
		
		if($("#dmod_privilegios").length > 0)
			$("#dmod_privilegios").val('si');
		
		if(numero_hijos > 0){
			$("input:checkbox", elemento_padre).attr("checked", ($(this).attr("checked")? true: false));
		}
	});

	// Autocomplete Empresas
	$("#fempresa").autocomplete({
		source: base_url + 'panel/empresas/ajax_get_empresas/',
		minLength: 1,
		selectFirst: true,
		select: function( event, ui ) {
		  $("#did_empresa").val(ui.item.id);
		  $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
		}
	}).keydown(function(e){
		if (e.which === 8) {
			$(this).css({'background-color': '#FFD9B3'});
			$('#did_empresa').val('');
		}
	});

	$("#festa_asegurado").on('change', function(){
		campos_requeridos($(this));
	});
	campos_requeridos($("#festa_asegurado"));

});

function campos_requeridos ($this) {
	if($this.is(':checked'))
	{
		$("#frfc, #fcurp, #ffecha_entrada, #fsalario_diario, #fsalario_diario_real, #fregimen_contratacion").attr("required", "required");
	}else
		$("#frfc, #fcurp, #ffecha_entrada, #fsalario_diario, #fsalario_diario_real, #fregimen_contratacion").removeAttr("required");
}