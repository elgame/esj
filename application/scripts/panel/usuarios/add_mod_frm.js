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

});