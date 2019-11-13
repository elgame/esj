<?php
// namespace Catalogos;
// use Illuminate\Support\Collection;

class Monedas {
  use Catalogos;

  private $collectionMonedas;

  private $monedasOld = [
    'M.N.' => ['key' => 'M.N.', 'value' => 'Peso mexicano', 'decimales' => 2, 'variacion' => '35'],
  ];

  private $monedas = [
    'MXN' => ['key' => 'MXN', 'value' => 'Peso Mexicano', 'decimales' => 2, 'variacion' => '35'],
    'USD' => ['key' => 'USD', 'value' => 'Dolar americano', 'decimales' => 2, 'variacion' => '35'],
    'EUR' => ['key' => 'EUR', 'value' => 'Euro', 'decimales' => 2, 'variacion' => '40'],
    'GBP' => ['key' => 'GBP', 'value' => 'Libra Esterlina', 'decimales' => 2, 'variacion' => '29'],
    'CNY' => ['key' => 'CNY', 'value' => 'Yuan Renminbi', 'decimales' => 2, 'variacion' => '28'],
    'MXV' => ['key' => 'MXV', 'value' => 'México Unidad de Inversión (UDI)', 'decimales' => 2, 'variacion' => '35'],
    'USN' => ['key' => 'USN', 'value' => 'Dólar estadounidense (día siguiente)', 'decimales' => 2, 'variacion' => '35'],
    'AED' => ['key' => 'AED', 'value' => 'Dirham de EAU', 'decimales' => 2, 'variacion' => '35'],
    'AFN' => ['key' => 'AFN', 'value' => 'Afghani', 'decimales' => 2, 'variacion' => '35'],
    'ALL' => ['key' => 'ALL', 'value' => 'Lek', 'decimales' => 2, 'variacion' => '35'],
    'AMD' => ['key' => 'AMD', 'value' => 'Dram armenio', 'decimales' => 2, 'variacion' => '35'],
    'ANG' => ['key' => 'ANG', 'value' => 'Florín antillano neerlandés', 'decimales' => 2, 'variacion' => '35'],
    'AOA' => ['key' => 'AOA', 'value' => 'Kwanza', 'decimales' => 2, 'variacion' => '35'],
    'ARS' => ['key' => 'ARS', 'value' => 'Peso Argentino', 'decimales' => 2, 'variacion' => '63'],
    'AUD' => ['key' => 'AUD', 'value' => 'Dólar Australiano', 'decimales' => 2, 'variacion' => '44'],
    'AWG' => ['key' => 'AWG', 'value' => 'Aruba Florin', 'decimales' => 2, 'variacion' => '35'],
    'AZN' => ['key' => 'AZN', 'value' => 'Azerbaijanian Manat', 'decimales' => 2, 'variacion' => '35'],
    'BAM' => ['key' => 'BAM', 'value' => 'Convertibles marca', 'decimales' => 2, 'variacion' => '35'],
    'BBD' => ['key' => 'BBD', 'value' => 'Dólar de Barbados', 'decimales' => 2, 'variacion' => '35'],
    'BDT' => ['key' => 'BDT', 'value' => 'Taka', 'decimales' => 2, 'variacion' => '35'],
    'BGN' => ['key' => 'BGN', 'value' => 'Lev búlgaro', 'decimales' => 2, 'variacion' => '35'],
    'BHD' => ['key' => 'BHD', 'value' => 'Dinar de Bahrein', 'decimales' => 3, 'variacion' => '35'],
    'BIF' => ['key' => 'BIF', 'value' => 'Burundi Franc', 'decimales' => 0, 'variacion' => '35'],
    'BMD' => ['key' => 'BMD', 'value' => 'Dólar de Bermudas', 'decimales' => 2, 'variacion' => '35'],
    'BND' => ['key' => 'BND', 'value' => 'Dólar de Brunei', 'decimales' => 2, 'variacion' => '35'],
    'BOB' => ['key' => 'BOB', 'value' => 'Boliviano', 'decimales' => 2, 'variacion' => '35'],
    'BOV' => ['key' => 'BOV', 'value' => 'Mvdol', 'decimales' => 2, 'variacion' => '35'],
    'BRL' => ['key' => 'BRL', 'value' => 'Real brasileño', 'decimales' => 2, 'variacion' => '51'],
    'BSD' => ['key' => 'BSD', 'value' => 'Dólar de las Bahamas', 'decimales' => 2, 'variacion' => '35'],
    'BTN' => ['key' => 'BTN', 'value' => 'Ngultrum', 'decimales' => 2, 'variacion' => '35'],
    'BWP' => ['key' => 'BWP', 'value' => 'Pula', 'decimales' => 2, 'variacion' => '35'],
    'BYR' => ['key' => 'BYR', 'value' => 'Rublo bielorruso', 'decimales' => 0, 'variacion' => '35'],
    'BZD' => ['key' => 'BZD', 'value' => 'Dólar de Belice', 'decimales' => 2, 'variacion' => '36'],
    'CAD' => ['key' => 'CAD', 'value' => 'Dolar Canadiense', 'decimales' => 2, 'variacion' => '31'],
    'CDF' => ['key' => 'CDF', 'value' => 'Franco congoleño', 'decimales' => 2, 'variacion' => '35'],
    'CHE' => ['key' => 'CHE', 'value' => 'WIR Euro', 'decimales' => 2, 'variacion' => '35'],
    'CHF' => ['key' => 'CHF', 'value' => 'Franco Suizo', 'decimales' => 2, 'variacion' => '49'],
    'CHW' => ['key' => 'CHW', 'value' => 'Franc WIR', 'decimales' => 2, 'variacion' => '35'],
    'CLF' => ['key' => 'CLF', 'value' => 'Unidad de Fomento', 'decimales' => 4, 'variacion' => '35'],
    'CLP' => ['key' => 'CLP', 'value' => 'Peso chileno', 'decimales' => 0, 'variacion' => '45'],
    'COP' => ['key' => 'COP', 'value' => 'Peso Colombiano', 'decimales' => 2, 'variacion' => '33'],
    'COU' => ['key' => 'COU', 'value' => 'Unidad de Valor real', 'decimales' => 2, 'variacion' => '35'],
    'CRC' => ['key' => 'CRC', 'value' => 'Colón costarricense', 'decimales' => 2, 'variacion' => '31'],
    'CUC' => ['key' => 'CUC', 'value' => 'Peso Convertible', 'decimales' => 2, 'variacion' => '35'],
    'CUP' => ['key' => 'CUP', 'value' => 'Peso Cubano', 'decimales' => 2, 'variacion' => '36'],
    'CVE' => ['key' => 'CVE', 'value' => 'Cabo Verde Escudo', 'decimales' => 2, 'variacion' => '35'],
    'CZK' => ['key' => 'CZK', 'value' => 'Corona checa', 'decimales' => 2, 'variacion' => '42'],
    'DJF' => ['key' => 'DJF', 'value' => 'Franco de Djibouti', 'decimales' => 0, 'variacion' => '35'],
    'DKK' => ['key' => 'DKK', 'value' => 'Corona danesa', 'decimales' => 2, 'variacion' => '40'],
    'DOP' => ['key' => 'DOP', 'value' => 'Peso Dominicano', 'decimales' => 2, 'variacion' => '51'],
    'DZD' => ['key' => 'DZD', 'value' => 'Dinar argelino', 'decimales' => 2, 'variacion' => '30'],
    'EGP' => ['key' => 'EGP', 'value' => 'Libra egipcia', 'decimales' => 2, 'variacion' => '34'],
    'ERN' => ['key' => 'ERN', 'value' => 'Nakfa', 'decimales' => 2, 'variacion' => '35'],
    'ETB' => ['key' => 'ETB', 'value' => 'Birr etíope', 'decimales' => 2, 'variacion' => '35'],
    'FJD' => ['key' => 'FJD', 'value' => 'Dólar de Fiji', 'decimales' => 2, 'variacion' => '35'],
    'FKP' => ['key' => 'FKP', 'value' => 'Libra malvinense', 'decimales' => 2, 'variacion' => '35'],
    'GEL' => ['key' => 'GEL', 'value' => 'Lari', 'decimales' => 2, 'variacion' => '35'],
    'GHS' => ['key' => 'GHS', 'value' => 'Cedi de Ghana', 'decimales' => 2, 'variacion' => '35'],
    'GIP' => ['key' => 'GIP', 'value' => 'Libra de Gibraltar', 'decimales' => 2, 'variacion' => '35'],
    'GMD' => ['key' => 'GMD', 'value' => 'Dalasi', 'decimales' => 2, 'variacion' => '35'],
    'GNF' => ['key' => 'GNF', 'value' => 'Franco guineano', 'decimales' => 0, 'variacion' => '35'],
    'GTQ' => ['key' => 'GTQ', 'value' => 'Quetzal', 'decimales' => 2, 'variacion' => '36'],
    'GYD' => ['key' => 'GYD', 'value' => 'Dólar guyanés', 'decimales' => 2, 'variacion' => '35'],
    'HKD' => ['key' => 'HKD', 'value' => 'Dolar De Hong Kong', 'decimales' => 2, 'variacion' => '35'],
    'HNL' => ['key' => 'HNL', 'value' => 'Lempira', 'decimales' => 2, 'variacion' => '28'],
    'HRK' => ['key' => 'HRK', 'value' => 'Kuna', 'decimales' => 2, 'variacion' => '35'],
    'HTG' => ['key' => 'HTG', 'value' => 'Gourde', 'decimales' => 2, 'variacion' => '35'],
    'HUF' => ['key' => 'HUF', 'value' => 'Florín', 'decimales' => 2, 'variacion' => '38'],
    'IDR' => ['key' => 'IDR', 'value' => 'Rupia', 'decimales' => 2, 'variacion' => '35'],
    'ILS' => ['key' => 'ILS', 'value' => 'Nuevo Shekel Israelí', 'decimales' => 2, 'variacion' => '33'],
    'INR' => ['key' => 'INR', 'value' => 'Rupia india', 'decimales' => 2, 'variacion' => '30'],
    'IQD' => ['key' => 'IQD', 'value' => 'Dinar iraquí', 'decimales' => 3, 'variacion' => '59'],
    'IRR' => ['key' => 'IRR', 'value' => 'Rial iraní', 'decimales' => 2, 'variacion' => '35'],
    'ISK' => ['key' => 'ISK', 'value' => 'Corona islandesa', 'decimales' => 0, 'variacion' => '35'],
    'JMD' => ['key' => 'JMD', 'value' => 'Dólar Jamaiquino', 'decimales' => 2, 'variacion' => '28'],
    'JOD' => ['key' => 'JOD', 'value' => 'Dinar jordano', 'decimales' => 3, 'variacion' => '35'],
    'JPY' => ['key' => 'JPY', 'value' => 'Yen', 'decimales' => 0, 'variacion' => '57'],
    'KES' => ['key' => 'KES', 'value' => 'Chelín keniano', 'decimales' => 2, 'variacion' => '30'],
    'KGS' => ['key' => 'KGS', 'value' => 'Som', 'decimales' => 2, 'variacion' => '35'],
    'KHR' => ['key' => 'KHR', 'value' => 'Riel', 'decimales' => 2, 'variacion' => '35'],
    'KMF' => ['key' => 'KMF', 'value' => 'Franco Comoro', 'decimales' => 0, 'variacion' => '35'],
    'KPW' => ['key' => 'KPW', 'value' => 'Corea del Norte ganó', 'decimales' => 2, 'variacion' => '35'],
    'KRW' => ['key' => 'KRW', 'value' => 'Won', 'decimales' => 0, 'variacion' => '35'],
    'KWD' => ['key' => 'KWD', 'value' => 'Dinar kuwaití', 'decimales' => 3, 'variacion' => '33'],
    'KYD' => ['key' => 'KYD', 'value' => 'Dólar de las Islas Caimán', 'decimales' => 2, 'variacion' => '35'],
    'KZT' => ['key' => 'KZT', 'value' => 'Tenge', 'decimales' => 2, 'variacion' => '35'],
    'LAK' => ['key' => 'LAK', 'value' => 'Kip', 'decimales' => 2, 'variacion' => '35'],
    'LBP' => ['key' => 'LBP', 'value' => 'Libra libanesa', 'decimales' => 2, 'variacion' => '35'],
    'LKR' => ['key' => 'LKR', 'value' => 'Rupia de Sri Lanka', 'decimales' => 2, 'variacion' => '35'],
    'LRD' => ['key' => 'LRD', 'value' => 'Dólar liberiano', 'decimales' => 2, 'variacion' => '35'],
    'LSL' => ['key' => 'LSL', 'value' => 'Loti', 'decimales' => 2, 'variacion' => '35'],
    'LYD' => ['key' => 'LYD', 'value' => 'Dinar libio', 'decimales' => 3, 'variacion' => '35'],
    'MAD' => ['key' => 'MAD', 'value' => 'Dirham marroquí', 'decimales' => 2, 'variacion' => '36'],
    'MDL' => ['key' => 'MDL', 'value' => 'Leu moldavo', 'decimales' => 2, 'variacion' => '35'],
    'MGA' => ['key' => 'MGA', 'value' => 'Ariary malgache', 'decimales' => 2, 'variacion' => '35'],
    'MKD' => ['key' => 'MKD', 'value' => 'Denar', 'decimales' => 2, 'variacion' => '35'],
    'MMK' => ['key' => 'MMK', 'value' => 'Kyat', 'decimales' => 2, 'variacion' => '35'],
    'MNT' => ['key' => 'MNT', 'value' => 'Tugrik', 'decimales' => 2, 'variacion' => '35'],
    'MOP' => ['key' => 'MOP', 'value' => 'Pataca', 'decimales' => 2, 'variacion' => '35'],
    'MRO' => ['key' => 'MRO', 'value' => 'Ouguiya', 'decimales' => 2, 'variacion' => '35'],
    'MUR' => ['key' => 'MUR', 'value' => 'Rupia de Mauricio', 'decimales' => 2, 'variacion' => '35'],
    'MVR' => ['key' => 'MVR', 'value' => 'Rupia', 'decimales' => 2, 'variacion' => '35'],
    'MWK' => ['key' => 'MWK', 'value' => 'Kwacha', 'decimales' => 2, 'variacion' => '35'],
    'MYR' => ['key' => 'MYR', 'value' => 'Ringgit malayo', 'decimales' => 2, 'variacion' => '27'],
    'MZN' => ['key' => 'MZN', 'value' => 'Mozambique Metical', 'decimales' => 2, 'variacion' => '35'],
    'NAD' => ['key' => 'NAD', 'value' => 'Dólar de Namibia', 'decimales' => 2, 'variacion' => '35'],
    'NGN' => ['key' => 'NGN', 'value' => 'Naira', 'decimales' => 2, 'variacion' => '31'],
    'NIO' => ['key' => 'NIO', 'value' => 'Córdoba Oro', 'decimales' => 2, 'variacion' => '35'],
    'NOK' => ['key' => 'NOK', 'value' => 'Corona noruega', 'decimales' => 2, 'variacion' => '34'],
    'NPR' => ['key' => 'NPR', 'value' => 'Rupia nepalí', 'decimales' => 2, 'variacion' => '35'],
    'NZD' => ['key' => 'NZD', 'value' => 'Dólar de Nueva Zelanda', 'decimales' => 2, 'variacion' => '45'],
    'OMR' => ['key' => 'OMR', 'value' => 'Rial omaní', 'decimales' => 3, 'variacion' => '35'],
    'PAB' => ['key' => 'PAB', 'value' => 'Balboa', 'decimales' => 2, 'variacion' => '35'],
    'PEN' => ['key' => 'PEN', 'value' => 'Nuevo Sol', 'decimales' => 2, 'variacion' => '29'],
    'PGK' => ['key' => 'PGK', 'value' => 'Kina', 'decimales' => 2, 'variacion' => '35'],
    'PHP' => ['key' => 'PHP', 'value' => 'Peso filipino', 'decimales' => 2, 'variacion' => '30'],
    'PKR' => ['key' => 'PKR', 'value' => 'Rupia de Pakistán', 'decimales' => 2, 'variacion' => '35'],
    'PLN' => ['key' => 'PLN', 'value' => 'Zloty', 'decimales' => 2, 'variacion' => '39'],
    'PYG' => ['key' => 'PYG', 'value' => 'Guaraní', 'decimales' => 0, 'variacion' => '37'],
    'QAR' => ['key' => 'QAR', 'value' => 'Qatar Rial', 'decimales' => 2, 'variacion' => '35'],
    'RON' => ['key' => 'RON', 'value' => 'Leu rumano', 'decimales' => 2, 'variacion' => '36'],
    'RSD' => ['key' => 'RSD', 'value' => 'Dinar serbio', 'decimales' => 2, 'variacion' => '35'],
    'RUB' => ['key' => 'RUB', 'value' => 'Rublo ruso', 'decimales' => 2, 'variacion' => '35'],
    'RWF' => ['key' => 'RWF', 'value' => 'Franco ruandés', 'decimales' => 0, 'variacion' => '35'],
    'SAR' => ['key' => 'SAR', 'value' => 'Riyal saudí', 'decimales' => 2, 'variacion' => '35'],
    'SBD' => ['key' => 'SBD', 'value' => 'Dólar de las Islas Salomón', 'decimales' => 2, 'variacion' => '35'],
    'SCR' => ['key' => 'SCR', 'value' => 'Rupia de Seychelles', 'decimales' => 2, 'variacion' => '35'],
    'SDG' => ['key' => 'SDG', 'value' => 'Libra sudanesa', 'decimales' => 2, 'variacion' => '35'],
    'SEK' => ['key' => 'SEK', 'value' => 'Corona sueca', 'decimales' => 2, 'variacion' => '38'],
    'SGD' => ['key' => 'SGD', 'value' => 'Dolar De Singapur', 'decimales' => 2, 'variacion' => '35'],
    'SHP' => ['key' => 'SHP', 'value' => 'Libra de Santa Helena', 'decimales' => 2, 'variacion' => '35'],
    'SLL' => ['key' => 'SLL', 'value' => 'Leona', 'decimales' => 2, 'variacion' => '35'],
    'SOS' => ['key' => 'SOS', 'value' => 'Chelín somalí', 'decimales' => 2, 'variacion' => '35'],
    'SRD' => ['key' => 'SRD', 'value' => 'Dólar de Suriname', 'decimales' => 2, 'variacion' => '35'],
    'SSP' => ['key' => 'SSP', 'value' => 'Libra sudanesa Sur', 'decimales' => 2, 'variacion' => '35'],
    'STD' => ['key' => 'STD', 'value' => 'Dobra', 'decimales' => 2, 'variacion' => '35'],
    'SVC' => ['key' => 'SVC', 'value' => 'Colon El Salvador', 'decimales' => 2, 'variacion' => '35'],
    'SYP' => ['key' => 'SYP', 'value' => 'Libra Siria', 'decimales' => 2, 'variacion' => '35'],
    'SZL' => ['key' => 'SZL', 'value' => 'Lilangeni', 'decimales' => 2, 'variacion' => '35'],
    'THB' => ['key' => 'THB', 'value' => 'Baht', 'decimales' => 2, 'variacion' => '37'],
    'TJS' => ['key' => 'TJS', 'value' => 'Somoni', 'decimales' => 2, 'variacion' => '35'],
    'TMT' => ['key' => 'TMT', 'value' => 'Turkmenistán nuevo manat', 'decimales' => 2, 'variacion' => '35'],
    'TND' => ['key' => 'TND', 'value' => 'Dinar tunecino', 'decimales' => 3, 'variacion' => '35'],
    'TOP' => ['key' => 'TOP', 'value' => 'Paanga', 'decimales' => 2, 'variacion' => '35'],
    'TRY' => ['key' => 'TRY', 'value' => 'Lira turca', 'decimales' => 2, 'variacion' => '35'],
    'TTD' => ['key' => 'TTD', 'value' => 'Dólar de Trinidad y Tobago', 'decimales' => 2, 'variacion' => '29'],
    'TWD' => ['key' => 'TWD', 'value' => 'Nuevo dólar de Taiwán', 'decimales' => 2, 'variacion' => '30'],
    'TZS' => ['key' => 'TZS', 'value' => 'Shilling tanzano', 'decimales' => 2, 'variacion' => '35'],
    'UAH' => ['key' => 'UAH', 'value' => 'Hryvnia', 'decimales' => 2, 'variacion' => '51'],
    'UGX' => ['key' => 'UGX', 'value' => 'Shilling de Uganda', 'decimales' => 0, 'variacion' => '35'],
    'UYI' => ['key' => 'UYI', 'value' => 'Peso Uruguay en Unidades Indexadas (URUIURUI)', 'decimales' => 0, 'variacion' => '35'],
    'UYU' => ['key' => 'UYU', 'value' => 'Peso Uruguayo', 'decimales' => 2, 'variacion' => '32'],
    'UZS' => ['key' => 'UZS', 'value' => 'Uzbekistán Sum', 'decimales' => 2, 'variacion' => '35'],
    'VEF' => ['key' => 'VEF', 'value' => 'Bolívar', 'decimales' => 2, 'variacion' => '153'],
    'VND' => ['key' => 'VND', 'value' => 'Dong', 'decimales' => 0, 'variacion' => '30'],
    'VUV' => ['key' => 'VUV', 'value' => 'Vatu', 'decimales' => 0, 'variacion' => '35'],
    'WST' => ['key' => 'WST', 'value' => 'Tala', 'decimales' => 2, 'variacion' => '35'],
    'XAF' => ['key' => 'XAF', 'value' => 'Franco CFA BEAC', 'decimales' => 0, 'variacion' => '35'],
    'XAG' => ['key' => 'XAG', 'value' => 'Plata', 'decimales' => 0, 'variacion' => '35'],
    'XAU' => ['key' => 'XAU', 'value' => 'Oro', 'decimales' => 0, 'variacion' => '35'],
    'XBA' => ['key' => 'XBA', 'value' => 'Unidad de Mercados de Bonos Unidad Europea Composite (EURCO)', 'decimales' => 0, 'variacion' => '35'],
    'XBB' => ['key' => 'XBB', 'value' => 'Unidad Monetaria de Bonos de Mercados Unidad Europea (UEM-6)', 'decimales' => 0, 'variacion' => '35'],
    'XBC' => ['key' => 'XBC', 'value' => 'Mercados de Bonos Unidad Europea unidad de cuenta a 9 (UCE-9)', 'decimales' => 0, 'variacion' => '35'],
    'XBD' => ['key' => 'XBD', 'value' => 'Mercados de Bonos Unidad Europea unidad de cuenta a 17 (UCE-17)', 'decimales' => 0, 'variacion' => '35'],
    'XCD' => ['key' => 'XCD', 'value' => 'Dólar del Caribe Oriental', 'decimales' => 2, 'variacion' => '35'],
    'XDR' => ['key' => 'XDR', 'value' => 'DEG (Derechos Especiales de Giro)', 'decimales' => 0, 'variacion' => '37'],
    'XOF' => ['key' => 'XOF', 'value' => 'Franco CFA BCEAO', 'decimales' => 0, 'variacion' => '35'],
    'XPD' => ['key' => 'XPD', 'value' => 'Paladio', 'decimales' => 0, 'variacion' => '35'],
    'XPF' => ['key' => 'XPF', 'value' => 'Franco CFP', 'decimales' => 0, 'variacion' => '35'],
    'XPT' => ['key' => 'XPT', 'value' => 'Platino', 'decimales' => 0, 'variacion' => '35'],
    'XSU' => ['key' => 'XSU', 'value' => 'Sucre', 'decimales' => 0, 'variacion' => '35'],
    'XTS' => ['key' => 'XTS', 'value' => 'Códigos reservados específicamente para propósitos de prueba', 'decimales' => 0, 'variacion' => '0'],
    'XUA' => ['key' => 'XUA', 'value' => 'Unidad ADB de Cuenta', 'decimales' => 0, 'variacion' => '35'],
    'XXX' => ['key' => 'XXX', 'value' => 'Los códigos asignados para las transacciones en que intervenga ninguna moneda', 'decimales' => 0, 'variacion' => '0'],
    'YER' => ['key' => 'YER', 'value' => 'Rial yemení', 'decimales' => 2, 'variacion' => '35'],
    'ZAR' => ['key' => 'ZAR', 'value' => 'Rand', 'decimales' => 2, 'variacion' => '54'],
    'ZMW' => ['key' => 'ZMW', 'value' => 'Kwacha zambiano', 'decimales' => 2, 'variacion' => '35'],
    'ZWL' => ['key' => 'ZWL', 'value' => 'Zimbabwe Dólar', 'decimales' => 2, 'variacion' => '35'],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionMonedas = new Collection($this->monedas);
  }

  public function withTrashed()
  {
    $this->collectionMonedas = $this->collectionMonedas->merge($this->monedasOld);
    return $this;
  }

  /**
   * Obtiene la coleccion de las monedas
   *
   * @return Illuminate\Support\Collection
   */
  public function get()
  {
    return $this->collectionMonedas;
  }

  /**
   * Filtra las monedas para los CFDI
   * @param  Request $request
   * @return Illuminate\Support\Collection
   */
  public function search($request)
  {
    $monedas = $this->collectionMonedas;
    if ($this->clTxt($request->input('term')) != '') {
      $monedas = $monedas->filter(function($item, $key) use ($request) {
        return (strpos($this->clTxt($item['value']), $this->clTxt($request->term)) !== false ||
                strpos($this->clTxt($item['key']), $this->clTxt($request->term)) !== false);
      });
    }
    return $monedas;
  }

}