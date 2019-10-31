// lista de ids de los productos que son gastos en las facturas
$gastosProductos = [49, 50, 51, 52, 53, 236, 237, 238, 239];

function searchGastosProductos(val) {
  return ($gastosProductos.find(function (item) {
    return item == val;
  })? true: false);
}