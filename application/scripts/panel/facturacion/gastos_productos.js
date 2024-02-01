// lista de ids de los productos que son gastos en las facturas
$gastosProductos = [49, 50, 51, 52, 53, 236, 237, 238, 239, 1299, 1601, 1602, 1603, 1610];
$(function(){
  if($('#isBodegaGdl').val() == '1'){
    $gastosProductos = [49, 50, 51, 52, 53, 236, 237, 238, 239, 188];
  }
});

function searchGastosProductos(val) {
  return ($gastosProductos.find(function (item) {
    return item == val;
  })? true: false);
}