$(function(){
	$(".fecha1, .fecha2").datepicker({
     dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
     //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
     changeMonth: true, //permite modificar los meses (true o false)
     changeYear: true, //permite modificar los años (true o false)
     //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
     numberOfMonths: 1 //muestra mas de un mes en el calendario, depende del numero
   });

	//productos menos vendidos
	$("#frmmenos_vendidos").submit(function(){
		loader.create();
		$.get(base_url+'panel/reportes/productos_vendidos', 
			{fecha1: $('.fecha1', this).val(), fecha2: $('.fecha2', this).val(), tipo: '0'}, 
			function(data){
				$("#product_menos_vend").html(data);
		}).complete(function(){
			loader.close();
		});
		return false;
	});
	//Productos mas vendidos
	$("#frmmas_vendidos").submit(function(){
		loader.create();
		$.get(base_url+'panel/reportes/productos_vendidos', 
			{fecha1: $('.fecha1', this).val(), fecha2: $('.fecha2', this).val(), tipo: '1'}, 
			function(data){
				$("#product_mas_vend").html(data);
		}).complete(function(){
			loader.close();
		});
		return false;
	});
});