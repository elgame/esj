<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class home extends MY_Controller {
	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('');

	public function _remap($method){

		$this->load->model("usuarios_model");
		if($this->usuarios_model->checkSession()){
			$this->usuarios_model->excepcion_privilegio = $this->excepcion_privilegio;
			$this->info_empleado                         = $this->usuarios_model->get_usuario_info($this->session->userdata('id'), true);

			$this->{$method}();
		}else
			$this->{'login'}();
	}

	public function index(){

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/home.js'),
    ));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Panel de Administración'
		);

		// $this->load->model('cuentas_pagar_model');
		// $params['cuentas_pagar'] = $this->cuentas_pagar_model->get_cuentas_pagar('15', 'total_pagar DESC')['cuenta_pagar'];

		// $this->load->model('cajas_model');
		// $params['inventario'] = $this->cajas_model->get_inventario('15', 'total_debe DESC')['inventario'];


		// $client = new SoapClient("http://demo-facturacion.finkok.com/servicios/soap/stamp.wsdl");
		// $params = array('xml' => "PGNmZGk6Q29tcHJvYmFudGUgeG1sbnM6Y2ZkaT0iaHR0cDovL3d3dy5zYXQuZ29iLm14L2NmZC8zIiB4bWxuczp4c2k9Imh0dHA6Ly93d3cudzMub3JnLzIwMDEvWE1MU2NoZW1hLWluc3RhbmNlIiB4c2k6c2NoZW1hTG9jYXRpb249Imh0dHA6Ly93d3cuc2F0LmdvYi5teC9jZmQvMyBodHRwOi8vd3d3LnNhdC5nb2IubXgvc2l0aW9faW50ZXJuZXQvY2ZkLzMvY2ZkdjMyLnhzZCAgICIgdmVyc2lvbj0iMy4yIiBmb2xpbz0iMSIgZmVjaGE9IjIwMTMtMDEtMjNUMTM6NTA6MTAiIGZvcm1hRGVQYWdvPSJQQUdPIEVOIFVOQSBTT0xBIEVYSElCSUNJT04iIG5vQ2VydGlmaWNhZG89IjIwMDAxMDAwMDAwMjAwMDAwMjkzIiBjZXJ0aWZpY2Fkbz0iTUlJRTJqQ0NBOEtnQXdJQkFnSVVNakF3TURFd01EQXdNREF5TURBd01EQXlPVE13RFFZSktvWklodmNOQVFFRkJRQXdnZ0ZjTVJvd0dBWURWUVFEREJGQkxrTXVJRElnWkdVZ2NISjFaV0poY3pFdk1DMEdBMVVFQ2d3bVUyVnlkbWxqYVc4Z1pHVWdRV1J0YVc1cGMzUnlZV05wdzdOdUlGUnlhV0oxZEdGeWFXRXhPREEyQmdOVkJBc01MMEZrYldsdWFYTjBjbUZqYWNPemJpQmtaU0JUWldkMWNtbGtZV1FnWkdVZ2JHRWdTVzVtYjNKdFlXTnB3N051TVNrd0p3WUpLb1pJaHZjTkFRa0JGaHBoYzJsemJtVjBRSEJ5ZFdWaVlYTXVjMkYwTG1kdllpNXRlREVtTUNRR0ExVUVDUXdkUVhZdUlFaHBaR0ZzWjI4Z056Y3NJRU52YkM0Z1IzVmxjbkpsY204eERqQU1CZ05WQkJFTUJUQTJNekF3TVFzd0NRWURWUVFHRXdKTldERVpNQmNHQTFVRUNBd1FSR2x6ZEhKcGRHOGdSbVZrWlhKaGJERVNNQkFHQTFVRUJ3d0pRMjk1YjJGanc2RnVNVFF3TWdZSktvWklodmNOQVFrQ0RDVlNaWE53YjI1ellXSnNaVG9nUVhKaFkyVnNhU0JIWVc1a1lYSmhJRUpoZFhScGMzUmhNQjRYRFRFeU1UQXlOakU1TWpJME0xb1hEVEUyTVRBeU5qRTVNakkwTTFvd2dnRlRNVWt3UndZRFZRUURFMEJCVTA5RFNVRkRTVTlPSUVSRklFRkhVa2xEVlV4VVQxSkZVeUJFUlV3Z1JFbFRWRkpKVkU4Z1JFVWdVa2xGUjA4Z01EQTBJRVJQVGlCTlFWSlVTVTRnTVdFd1h3WURWUVFwRTFoQlUwOURTVUZEU1U5T0lFUkZJRUZIVWtsRFZVeFVUMUpGVXlCRVJVd2dSRWxUVkZKSlZFOGdSRVVnVWtsRlIwOGdNREEwSUVSUFRpQk5RVkpVU1U0Z1EwOUJTRlZKVEVFZ1dTQk9WVVZXVHlCTVJVOU9JRUZETVVrd1J3WURWUVFLRTBCQlUwOURTVUZEU1U5T0lFUkZJRUZIVWtsRFZVeFVUMUpGVXlCRVJVd2dSRWxUVkZKSlZFOGdSRVVnVWtsRlIwOGdNREEwSUVSUFRpQk5RVkpVU1U0Z01TVXdJd1lEVlFRdEV4eEJRVVE1T1RBNE1UUkNVRGNnTHlCSVJVZFVOell4TURBek5GTXlNUjR3SEFZRFZRUUZFeFVnTHlCSVJVZFVOell4TURBelRVUkdVazVPTURreEVUQVBCZ05WQkFzVENGTmxjblpwWkc5eU1JR2ZNQTBHQ1NxR1NJYjNEUUVCQVFVQUE0R05BRENCaVFLQmdRRGxySTlsb296ZCtVY1c3WUh0cUppbVFqelg5d0hJVWNjMUtaeUJCQjgvNWZac2daL3NtV1M0U2Q2SG5QczlHU1R0blRtTTRiRWd4MjhOM3VsVXNoYWFCRXRabzN0c2p3a0JWL3lWUTNTUnlNRGtxQkEyTkVqYmN1bStlL01kQ01IaVBJMWVTR0hFcGRFU3Q1NWEwUzZOMjRQVzczMlhtM1piR2dPcDF0aHQxd0lEQVFBQm94MHdHekFNQmdOVkhSTUJBZjhFQWpBQU1Bc0dBMVVkRHdRRUF3SUd3REFOQmdrcWhraUc5dzBCQVFVRkFBT0NBUUVBdW9QWGUrQkJJcm1KbitJR2VJK205N09sUDNSQzRDdDNhbWpHbVpJQ2J2aEk5QlRCTENML1B6UWpqV0J3VTBNRzh1SzZlL2djQjlmK2tsUGlYaFFUZUkxWUt6RnRXcnpjdHBORUpZbzBLWE1ndkRpcHV0S3BoUTMyNGRQMG56a0tVZlhsUkl6U2NKSkNTZ1J3OVppZktXTjBEOXFUZGtOa2prODNUb1Bnd25sZGc1bHpVNjJ3b1hvNEFLYmN1YWJBWU9Wb0M3b3dNNWJmTnVXSmU1NjZVekQ2aTVQRlkxNWpZTXppMStJQ3JpREl0Q3YzUytKZHF5ckJyWDNSbG9aaGR5WHFzMkh0eGZ3NGIxT2NZYm9QQ3U0KzlxTTNPVjAyd3lHS2xHUU1oZnJYTndZeWo4aHV4UzFwSGdoRVJPTTJaczBwYVpVT3krNmFqTStYaDBMWDJ3PT0iIGNvbmRpY2lvbmVzRGVQYWdvPSJTZXJhIG1hcmNhZGEgY29tbyBwYWdhZGEgZW4gY3VhbnRvIGVsIHJlY2VwdG9yIGhheWEgY3ViaWVydG8gZWwgcGFnby4iIHN1YlRvdGFsPSIxMTAwMC4wMCIgTW9uZWRhPSJwZXNvcyIgdG90YWw9IjEyNzYwLjAwIiBtZXRvZG9EZVBhZ289IlRyYW5zZmVyZW5jaWEgQmFuY2FyaWEiIHRpcG9EZUNvbXByb2JhbnRlPSJpbmdyZXNvIiBMdWdhckV4cGVkaWNpb249Ik1vcmVsaWEsIE1pY2hvYWMmIzIyNTtuIiBzZWxsbz0iMWFCWGo1SndJNXAxeW05ZTlSWDJpREp6N1QrRU5jUldXc1FHZjFZcFFWU21iZlRUN3JPSlZNdFROTWdzbE44Rk4vbnBxYjVjTE5nYXlHQWJJSGh0VlJyaVY5WkFRY2ZCUE4zZG1jNCsrUU9sdXpTbGpuUG44cXg0U2F3a1dEQWlFNWhWRFI0TC9NYnhzQ1F2dUZMWE1qS1FBRUNqdzN0N01Ta254MFZQM1ZZPSI+ICA8Y2ZkaTpFbWlzb3Igbm9tYnJlPSIgQXNvY2lhY2lvbiBkZSBBZ3JpY3VsdG9yZXMgZGVsIGRpc3RyaXRvICAiIHJmYz0iQUFEOTkwODE0QlA3Ij4gICAgPGNmZGk6RG9taWNpbGlvRmlzY2FsIGNhbGxlPSJBdiBNYWRlcm8iIG5vRXh0ZXJpb3I9IjQ1IiBjb2xvbmlhPSJDZW50cm8iIGxvY2FsaWRhZD0iTW9yZWxpYSIgcmVmZXJlbmNpYT0iU2luIFJlZmVyZW5jaWEiIG11bmljaXBpbz0iTW9yZWxpYSIgZXN0YWRvPSJNaWNob2FjJiMyMjU7biIgcGFpcz0iTSYjMjMzO3hpY28iIGNvZGlnb1Bvc3RhbD0iNTgwMDAiLz4gIDxjZmRpOkV4cGVkaWRvRW4gY2FsbGU9IkF2IE1hZGVybyIgcmVmZXJlbmNpYT0iU2luIFJlZmVyZW5jaWEiIG5vRXh0ZXJpb3I9IjQ1IiBjb2xvbmlhPSJDZW50cm8iIGxvY2FsaWRhZD0iTW9yZWxpYSIgbXVuaWNpcGlvPSJNb3JlbGlhIiBlc3RhZG89Ik1pY2hvYWMmIzIyNTtuIiBwYWlzPSJNJiMyMzM7eGljbyIgY29kaWdvUG9zdGFsPSI1ODAwMCIvPiAgICA8Y2ZkaTpSZWdpbWVuRmlzY2FsIFJlZ2ltZW49IlBydWViYXMgRmlzY2FsZXMiLz4gIDwvY2ZkaTpFbWlzb3I+ICA8Y2ZkaTpSZWNlcHRvciBub21icmU9IkVMIFNvY2lvbmF0aW9uIFNBIGRlIENWIiByZmM9IkhFTzg2MTIxNEpLTCI+ICAgIDxjZmRpOkRvbWljaWxpbyByZWZlcmVuY2lhPSJTaW4gUmVmZXJlbmNpYSIgZXN0YWRvPSJBZ3Vhc2NhbGllbnRlcyIgcGFpcz0iTSYjMjMzO3hpY28iLz4gIDwvY2ZkaTpSZWNlcHRvcj4gIDxjZmRpOkNvbmNlcHRvcz4gICAgPGNmZGk6Q29uY2VwdG8gY2FudGlkYWQ9IjIiIHVuaWRhZD0iUGllemEiIG5vSWRlbnRpZmljYWNpb249IlNVTiIgZGVzY3JpcGNpb249IlByYWRhIFN1bmdsYXNzZXMtUHJhZGEgU3VuIEdsYXNzZXMgQXZpYXRvciIgdmFsb3JVbml0YXJpbz0iNTUwMC4wMCIgaW1wb3J0ZT0iMTEwMDAuMDAiPjxjZmRpOkNvbXBsZW1lbnRvQ29uY2VwdG8vPiA8L2NmZGk6Q29uY2VwdG8+ICAgPC9jZmRpOkNvbmNlcHRvcz4gIDxjZmRpOkltcHVlc3RvcyB0b3RhbEltcHVlc3Rvc1RyYXNsYWRhZG9zPSIxNzYwLjAwIj4gICAgICAgIDxjZmRpOlRyYXNsYWRvcz4gICAgICAgICAgICA8Y2ZkaTpUcmFzbGFkbyBpbXBvcnRlPSIxNzYwLjAwIiB0YXNhPSIxNi4wMCIgaW1wdWVzdG89IklWQSIvPiAgICAgICAgCSAgICAgIDwvY2ZkaTpUcmFzbGFkb3M+ICAgICAgICA8L2NmZGk6SW1wdWVzdG9zPiAgPGNmZGk6Q29tcGxlbWVudG8vPjwvY2ZkaTpDb21wcm9iYW50ZT4K", 'username' => "gamalielm@indieds.com", 'password' => "gamaL1&l");
		// var_dump($client->stamp($params));

    // $this->load->library('cfdi');
    // $this->cfdi->cargaDatosFiscales(4);
    // var_dump($this->cfdi->obtenSello("||3.2|2013-07-24T19:09:29|ingreso|Pago en una sola exhibición|co|2000|2000|efectivo|Michoacán, Michoacán|No identificado|NEDR620710H7|ROBERTO NEVAREZ DOMINGUEZ|Pista Aérea|S/N|Ranchito|Ranchito|Michoacán|Michoacán|MEXICO|60800|Pista Aérea|S/N|Ranchito|Ranchito|Michoacán|Michoacán|MEXICO|60800|Actividad empresarial, régimen general de ley|AESA850407IU9|ABRAN ARMENTA SOTO|BLVD. SOLIDARIDAD INT. FCO. I. MADERO, LOCAL 5,6 D|.|MEXICO|100|Cajas|KGS LIMON VERDE 400(PLASTICO)|20|2000|IVA|0|0|IVA|0.00|0|0||"));exit;


/*
		$this->load->library('cfdi');
		$this->cfdi->cargaDatosFiscales(1);
		var_dump($this->cfdi->obtenSello('||3.2|2013-07-24T11:46:03|ingreso|Pago en una sola exhibición|co|1000|1000|efectivo|TECOMAN, Colima|No identificado|AVT920312NQ3|EMPAQUE SAN JORGE S.A DE C.V.|AV. 20 DE NOVIEMBRE|S/N|CONOCIDA|Villa de Alvarez|TECOMAN|Colima|MEXICO|28984|AV. 20 DE NOVIEMBRE|S/N|CONOCIDA|Villa de Alvarez|TECOMAN|Colima|MEXICO|28984|Régimen Intermedio|VILA830807TU0|ADRIANA VILLALOBOS LOPEZ|AERONAUTICA L89-90|6910|A LA C JARDINES DEL AEROPUERTO|MEXICO|32690|10|a|KGS LIMON VERDE 400(PLASTICO)|100|1000|IVA|0|0|IVA|0.00|0|0||'));


		$xml = '<?xml version="1.0" encoding="UTF-8"?> <cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sat.gob.mx/cfd/3 http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv32.xsd" version="3.2" serie="FAC" folio="247" fecha="2013-07-24T11:46:03" sello="qjiGTxPaUXdiOCPVnbipanil7dO+xAV0lLKMjneX7JHB4X+KcLawF5gHFRfL2PrYTZgZf+CGjZe502iSwc030hqNtSHvIb6lUoPiyUhSOw9oFUdz61c2tqKmOfTOKqpseNcz42UAZcXN2lgROi0X/t086IdxPYQ39KI5EHDJJsU=" formaDePago="Pago en una sola exhibición" noCertificado="00001000000203144869" certificado="MIIEbDCCA1SgAwIBAgIUMDAwMDEwMDAwMDAyMDMxNDQ4NjkwDQYJKoZIhvcNAQEFBQAwggGVMTgwNgYDVQQDDC9BLkMuIGRlbCBTZXJ2aWNpbyBkZSBBZG1pbmlzdHJhY2nDs24gVHJpYnV0YXJpYTEvMC0GA1UECgwmU2VydmljaW8gZGUgQWRtaW5pc3RyYWNpw7NuIFRyaWJ1dGFyaWExODA2BgNVBAsML0FkbWluaXN0cmFjacOzbiBkZSBTZWd1cmlkYWQgZGUgbGEgSW5mb3JtYWNpw7NuMSEwHwYJKoZIhvcNAQkBFhJhc2lzbmV0QHNhdC5nb2IubXgxJjAkBgNVBAkMHUF2LiBIaWRhbGdvIDc3LCBDb2wuIEd1ZXJyZXJvMQ4wDAYDVQQRDAUwNjMwMDELMAkGA1UEBhMCTVgxGTAXBgNVBAgMEERpc3RyaXRvIEZlZGVyYWwxFDASBgNVBAcMC0N1YXVodMOpbW9jMRUwEwYDVQQtEwxTQVQ5NzA3MDFOTjMxPjA8BgkqhkiG9w0BCQIML1Jlc3BvbnNhYmxlOiBDZWNpbGlhIEd1aWxsZXJtaW5hIEdhcmPDrWEgR3VlcnJhMB4XDTEzMDIyODE5MzMzNVoXDTE3MDIyODE5MzMzNVowga0xIjAgBgNVBAMTGVJPQkVSVE8gTkVWQVJFWiBET01JTkdVRVoxIjAgBgNVBCkTGVJPQkVSVE8gTkVWQVJFWiBET01JTkdVRVoxIjAgBgNVBAoTGVJPQkVSVE8gTkVWQVJFWiBET01JTkdVRVoxFjAUBgNVBC0TDU5FRFI2MjA3MTBINzYxGzAZBgNVBAUTEk5FRFI2MjA3MTBIQ0hWTUIwMDEKMAgGA1UECxMBMTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEA5tufImZ9dhFrBJU+n+GI7J57mBOoay/+JmqUU70RW7b6RqEsRNg0JP27qY/8R1IyTWzjsB6dupx5G1/i3WtYUBAfpGiycPnI1M5tB52KaYGcD9m6b5g5d32Npdn0sRyqGUspt06zHaL9OJU/5pV4cW9ZVFN0uEMR7ur7uOLNXqMCAwEAAaMdMBswDAYDVR0TAQH/BAIwADALBgNVHQ8EBAMCBsAwDQYJKoZIhvcNAQEFBQADggEBAAoZyfQZ+uxgejY7orFVI4uujg60OewVq7mAi83tkvJIeY/Cghw3gIjN3H8cguZVEUrgd1Y5qg2+HHN0QJxbY10CPPlOgv/T0oJPTGj/l0IBSqq/JXd80DnHgi0IeoP62liAlWf/ikS4ugH1IzbeAjYWDmPMjnsS2uyLK3LtuEX6Goa/PvkIihJZs8qmZ4/UuNRfhD7zUeruVK1xoh1fqA636ozwCxgpeo4vOaU+QFQRyavjrmOqMa2zYunok2GOsZOZURRmdxMg9hZx4UwvSnBXZoPjX2AKxiWb0AmQF/HMtwElDXfpDFTYf/FWv+zTJiDMQpuzteteFfEOWNJQErs=" condicionesDePago="co" subTotal="1000" total="1000" tipoDeComprobante="ingreso" metodoDePago="efectivo" LugarExpedicion="TECOMAN, Colima" NumCtaPago="No identificado" ><cfdi:Emisor rfc="AVT920312NQ3" nombre="EMPAQUE SAN JORGE S.A DE C.V."><cfdi:DomicilioFiscal calle="AV. 20 DE NOVIEMBRE" noExterior="S/N" colonia="CONOCIDA" localidad="Villa de Alvarez" municipio="TECOMAN" estado="Colima" pais="MEXICO" codigoPostal="28984"/><cfdi:ExpedidoEn calle="AV. 20 DE NOVIEMBRE" noExterior="S/N" colonia="CONOCIDA" localidad="Villa de Alvarez" municipio="TECOMAN" estado="Colima" pais="MEXICO" codigoPostal="28984"/><cfdi:RegimenFiscal Regimen="Régimen Intermedio" /></cfdi:Emisor><cfdi:Receptor rfc="VILA830807TU0" nombre="ADRIANA VILLALOBOS LOPEZ"><cfdi:Domicilio calle="AERONAUTICA L89-90" noExterior="6910" colonia="A LA C JARDINES DEL AEROPUERTO" pais="MEXICO" codigoPostal="32690"/></cfdi:Receptor><cfdi:Conceptos><cfdi:Concepto cantidad="10" unidad="a" descripcion="KGS LIMON VERDE 400(PLASTICO)" valorUnitario="100" importe="1000"></cfdi:Concepto></cfdi:Conceptos><cfdi:Impuestos totalImpuestosRetenidos="0" totalImpuestosTrasladados="0"><cfdi:Retenciones><cfdi:Retencion impuesto="IVA" importe="0"/></cfdi:Retenciones><cfdi:Traslados><cfdi:Traslado impuesto="IVA" tasa="0" importe="0"/></cfdi:Traslados></cfdi:Impuestos><cfdi:Complemento/></cfdi:Comprobante>';
		$xml = trim(str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml));
		$len = strlen($xml);
		$ciclos = ceil($len/57);
		$conta = 0;
		$resp = '';
		for ($i=0; $i < $ciclos; $i++) {
			$resp .= base64_encode(substr($xml, $conta, 57))."\n";
			$conta += 57;
		}
		var_dump(base64_encode("e>"));
		var_dump($resp);
		echo "*************";

		$data = array('PGNmZGk6Q29tcHJvYmFudGUgeG1sbnM6Y2ZkaT0iaHR0cDovL3d3dy5zYXQuZ29iLm14L2NmZC8z',
'IiB4bWxuczp4c2k9Imh0dHA6Ly93d3cudzMub3JnLzIwMDEvWE1MU2NoZW1hLWluc3RhbmNlIiB4',
'c2k6c2NoZW1hTG9jYXRpb249Imh0dHA6Ly93d3cuc2F0LmdvYi5teC9jZmQvMyBodHRwOi8vd3d3',
'LnNhdC5nb2IubXgvc2l0aW9faW50ZXJuZXQvY2ZkLzMvY2ZkdjMyLnhzZCAgICIgdmVyc2lvbj0i',
'My4yIiBmb2xpbz0iMSIgZmVjaGE9IjIwMTMtMDEtMjNUMTM6NTA6MTAiIGZvcm1hRGVQYWdvPSJQ',
'QUdPIEVOIFVOQSBTT0xBIEVYSElCSUNJT04iIG5vQ2VydGlmaWNhZG89IjIwMDAxMDAwMDAwMjAw',
'MDAwMjkzIiBjZXJ0aWZpY2Fkbz0iTUlJRTJqQ0NBOEtnQXdJQkFnSVVNakF3TURFd01EQXdNREF5',
'TURBd01EQXlPVE13RFFZSktvWklodmNOQVFFRkJRQXdnZ0ZjTVJvd0dBWURWUVFEREJGQkxrTXVJ',
'RElnWkdVZ2NISjFaV0poY3pFdk1DMEdBMVVFQ2d3bVUyVnlkbWxqYVc4Z1pHVWdRV1J0YVc1cGMz',
'UnlZV05wdzdOdUlGUnlhV0oxZEdGeWFXRXhPREEyQmdOVkJBc01MMEZrYldsdWFYTjBjbUZqYWNP',
'emJpQmtaU0JUWldkMWNtbGtZV1FnWkdVZ2JHRWdTVzVtYjNKdFlXTnB3N051TVNrd0p3WUpLb1pJ',
'aHZjTkFRa0JGaHBoYzJsemJtVjBRSEJ5ZFdWaVlYTXVjMkYwTG1kdllpNXRlREVtTUNRR0ExVUVD',
'UXdkUVhZdUlFaHBaR0ZzWjI4Z056Y3NJRU52YkM0Z1IzVmxjbkpsY204eERqQU1CZ05WQkJFTUJU',
'QTJNekF3TVFzd0NRWURWUVFHRXdKTldERVpNQmNHQTFVRUNBd1FSR2x6ZEhKcGRHOGdSbVZrWlhK',
'aGJERVNNQkFHQTFVRUJ3d0pRMjk1YjJGanc2RnVNVFF3TWdZSktvWklodmNOQVFrQ0RDVlNaWE53',
'YjI1ellXSnNaVG9nUVhKaFkyVnNhU0JIWVc1a1lYSmhJRUpoZFhScGMzUmhNQjRYRFRFeU1UQXlO',
'akU1TWpJME0xb1hEVEUyTVRBeU5qRTVNakkwTTFvd2dnRlRNVWt3UndZRFZRUURFMEJCVTA5RFNV',
'RkRTVTlPSUVSRklFRkhVa2xEVlV4VVQxSkZVeUJFUlV3Z1JFbFRWRkpKVkU4Z1JFVWdVa2xGUjA4',
'Z01EQTBJRVJQVGlCTlFWSlVTVTRnTVdFd1h3WURWUVFwRTFoQlUwOURTVUZEU1U5T0lFUkZJRUZI',
'VWtsRFZVeFVUMUpGVXlCRVJVd2dSRWxUVkZKSlZFOGdSRVVnVWtsRlIwOGdNREEwSUVSUFRpQk5R',
'VkpVU1U0Z1EwOUJTRlZKVEVFZ1dTQk9WVVZXVHlCTVJVOU9JRUZETVVrd1J3WURWUVFLRTBCQlUw',
'OURTVUZEU1U5T0lFUkZJRUZIVWtsRFZVeFVUMUpGVXlCRVJVd2dSRWxUVkZKSlZFOGdSRVVnVWts',
'RlIwOGdNREEwSUVSUFRpQk5RVkpVU1U0Z01TVXdJd1lEVlFRdEV4eEJRVVE1T1RBNE1UUkNVRGNn',
'THlCSVJVZFVOell4TURBek5GTXlNUjR3SEFZRFZRUUZFeFVnTHlCSVJVZFVOell4TURBelRVUkdV',
'azVPTURreEVUQVBCZ05WQkFzVENGTmxjblpwWkc5eU1JR2ZNQTBHQ1NxR1NJYjNEUUVCQVFVQUE0',
'R05BRENCaVFLQmdRRGxySTlsb296ZCtVY1c3WUh0cUppbVFqelg5d0hJVWNjMUtaeUJCQjgvNWZa',
'c2daL3NtV1M0U2Q2SG5QczlHU1R0blRtTTRiRWd4MjhOM3VsVXNoYWFCRXRabzN0c2p3a0JWL3lW',
'UTNTUnlNRGtxQkEyTkVqYmN1bStlL01kQ01IaVBJMWVTR0hFcGRFU3Q1NWEwUzZOMjRQVzczMlht',
'M1piR2dPcDF0aHQxd0lEQVFBQm94MHdHekFNQmdOVkhSTUJBZjhFQWpBQU1Bc0dBMVVkRHdRRUF3',
'SUd3REFOQmdrcWhraUc5dzBCQVFVRkFBT0NBUUVBdW9QWGUrQkJJcm1KbitJR2VJK205N09sUDNS',
'QzRDdDNhbWpHbVpJQ2J2aEk5QlRCTENML1B6UWpqV0J3VTBNRzh1SzZlL2djQjlmK2tsUGlYaFFU',
'ZUkxWUt6RnRXcnpjdHBORUpZbzBLWE1ndkRpcHV0S3BoUTMyNGRQMG56a0tVZlhsUkl6U2NKSkNT',
'Z1J3OVppZktXTjBEOXFUZGtOa2prODNUb1Bnd25sZGc1bHpVNjJ3b1hvNEFLYmN1YWJBWU9Wb0M3',
'b3dNNWJmTnVXSmU1NjZVekQ2aTVQRlkxNWpZTXppMStJQ3JpREl0Q3YzUytKZHF5ckJyWDNSbG9a',
'aGR5WHFzMkh0eGZ3NGIxT2NZYm9QQ3U0KzlxTTNPVjAyd3lHS2xHUU1oZnJYTndZeWo4aHV4UzFw',
'SGdoRVJPTTJaczBwYVpVT3krNmFqTStYaDBMWDJ3PT0iIGNvbmRpY2lvbmVzRGVQYWdvPSJTZXJh',
'IG1hcmNhZGEgY29tbyBwYWdhZGEgZW4gY3VhbnRvIGVsIHJlY2VwdG9yIGhheWEgY3ViaWVydG8g',
'ZWwgcGFnby4iIHN1YlRvdGFsPSIxMTAwMC4wMCIgTW9uZWRhPSJwZXNvcyIgdG90YWw9IjEyNzYw',
'LjAwIiBtZXRvZG9EZVBhZ289IlRyYW5zZmVyZW5jaWEgQmFuY2FyaWEiIHRpcG9EZUNvbXByb2Jh',
'bnRlPSJpbmdyZXNvIiBMdWdhckV4cGVkaWNpb249Ik1vcmVsaWEsIE1pY2hvYWMmIzIyNTtuIiBz',
'ZWxsbz0iMWFCWGo1SndJNXAxeW05ZTlSWDJpREp6N1QrRU5jUldXc1FHZjFZcFFWU21iZlRUN3JP',
'SlZNdFROTWdzbE44Rk4vbnBxYjVjTE5nYXlHQWJJSGh0VlJyaVY5WkFRY2ZCUE4zZG1jNCsrUU9s',
'dXpTbGpuUG44cXg0U2F3a1dEQWlFNWhWRFI0TC9NYnhzQ1F2dUZMWE1qS1FBRUNqdzN0N01Ta254',
'MFZQM1ZZPSI+ICA8Y2ZkaTpFbWlzb3Igbm9tYnJlPSIgQXNvY2lhY2lvbiBkZSBBZ3JpY3VsdG9y',
'ZXMgZGVsIGRpc3RyaXRvICAiIHJmYz0iQUFEOTkwODE0QlA3Ij4gICAgPGNmZGk6RG9taWNpbGlv',
'RmlzY2FsIGNhbGxlPSJBdiBNYWRlcm8iIG5vRXh0ZXJpb3I9IjQ1IiBjb2xvbmlhPSJDZW50cm8i',
'IGxvY2FsaWRhZD0iTW9yZWxpYSIgcmVmZXJlbmNpYT0iU2luIFJlZmVyZW5jaWEiIG11bmljaXBp',
'bz0iTW9yZWxpYSIgZXN0YWRvPSJNaWNob2FjJiMyMjU7biIgcGFpcz0iTSYjMjMzO3hpY28iIGNv',
'ZGlnb1Bvc3RhbD0iNTgwMDAiLz4gIDxjZmRpOkV4cGVkaWRvRW4gY2FsbGU9IkF2IE1hZGVybyIg',
'cmVmZXJlbmNpYT0iU2luIFJlZmVyZW5jaWEiIG5vRXh0ZXJpb3I9IjQ1IiBjb2xvbmlhPSJDZW50',
'cm8iIGxvY2FsaWRhZD0iTW9yZWxpYSIgbXVuaWNpcGlvPSJNb3JlbGlhIiBlc3RhZG89Ik1pY2hv',
'YWMmIzIyNTtuIiBwYWlzPSJNJiMyMzM7eGljbyIgY29kaWdvUG9zdGFsPSI1ODAwMCIvPiAgICA8',
'Y2ZkaTpSZWdpbWVuRmlzY2FsIFJlZ2ltZW49IlBydWViYXMgRmlzY2FsZXMiLz4gIDwvY2ZkaTpF',
'bWlzb3I+ICA8Y2ZkaTpSZWNlcHRvciBub21icmU9IkVMIFNvY2lvbmF0aW9uIFNBIGRlIENWIiBy',
'ZmM9IkhFTzg2MTIxNEpLTCI+ICAgIDxjZmRpOkRvbWljaWxpbyByZWZlcmVuY2lhPSJTaW4gUmVm',
'ZXJlbmNpYSIgZXN0YWRvPSJBZ3Vhc2NhbGllbnRlcyIgcGFpcz0iTSYjMjMzO3hpY28iLz4gIDwv',
'Y2ZkaTpSZWNlcHRvcj4gIDxjZmRpOkNvbmNlcHRvcz4gICAgPGNmZGk6Q29uY2VwdG8gY2FudGlk',
'YWQ9IjIiIHVuaWRhZD0iUGllemEiIG5vSWRlbnRpZmljYWNpb249IlNVTiIgZGVzY3JpcGNpb249',
'IlByYWRhIFN1bmdsYXNzZXMtUHJhZGEgU3VuIEdsYXNzZXMgQXZpYXRvciIgdmFsb3JVbml0YXJp',
'bz0iNTUwMC4wMCIgaW1wb3J0ZT0iMTEwMDAuMDAiPjxjZmRpOkNvbXBsZW1lbnRvQ29uY2VwdG8v',
'PiA8L2NmZGk6Q29uY2VwdG8+ICAgPC9jZmRpOkNvbmNlcHRvcz4gIDxjZmRpOkltcHVlc3RvcyB0',
'b3RhbEltcHVlc3Rvc1RyYXNsYWRhZG9zPSIxNzYwLjAwIj4gICAgICAgIDxjZmRpOlRyYXNsYWRv',
'cz4gICAgICAgICAgICA8Y2ZkaTpUcmFzbGFkbyBpbXBvcnRlPSIxNzYwLjAwIiB0YXNhPSIxNi4w',
'MCIgaW1wdWVzdG89IklWQSIvPiAgICAgICAgCSAgICAgIDwvY2ZkaTpUcmFzbGFkb3M+ICAgICAg',
'ICA8L2NmZGk6SW1wdWVzdG9zPiAgPGNmZGk6Q29tcGxlbWVudG8vPjwvY2ZkaTpDb21wcm9iYW50',
'ZT4K');
		foreach ($data as $key => $value) {
			var_dump(base64_decode($value));
		}
*/
		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/general/home', $params);
		$this->load->view('panel/footer');
	}




	/**
	 * carga el login para entrar al panel
	 */
	public function login(){

		$params['seo'] = array(
			'titulo' => 'Login'
		);

		$this->load->library('form_validation');
		$rules = array(
			array('field'	=> 'usuario',
				'label'		=> 'Usuario',
				'rules'		=> 'required'),
			array('field'	=> 'pass',
				'label'		=> 'Contraseña',
				'rules'		=> 'required')
		);
		$this->form_validation->set_rules($rules);
		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = array(
					'title' => 'Error al Iniciar Sesión!',
					'msg' => preg_replace("[\n|\r|\n\r]", '', validation_errors()),
					'ico' => 'error');
		}else{
			$data = array('usuario' => $this->input->post('usuario'), 'pass' => $this->input->post('pass'));
			$mdl_res = $this->usuarios_model->setLogin($data);
			if ($mdl_res[0] && $this->usuarios_model->checkSession()) {
				redirect(base_url('panel/home'));
			}
			else{
				$params['frm_errors'] = array(
					'title' => 'Error al Iniciar Sesión!',
					'msg' => 'El usuario y/o contraseña son incorrectos, o no cuenta con los permisos necesarios para loguearse',
					'ico' => 'error');
			}
		}

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/login', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * cierra la sesion del usuario
	 */
	public function logout(){
		$this->session->sess_destroy();
		redirect(base_url('panel/home'));
	}
}

?>