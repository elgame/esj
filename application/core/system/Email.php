<?php

class Email{
	
	public static $uid;
	public static $header;

	public static $correo_emisor_em = 'notificaciones@tiendacapp.com';
	public static $nombre_emisor = 'Tienda Capp';
	
	public static function base($html){

		self::$uid = md5(uniqid(time()));
		self::$header = "From: ".self::$nombre_emisor." <".self::$correo_emisor_em.">\r\n";
		self::$header .= "Reply-To: ".self::$correo_emisor_em."\r\n";
		self::$header .= "MIME-Version: 1.0\r\n";
		self::$header .= "Content-Type: multipart/mixed; boundary=\"".self::$uid."\"\r\n\r\n";
		self::$header .= "This is a multi-part message in MIME format.\r\n";
		self::$header .= "--".self::$uid."\r\n";
		self::$header .= "Content-Type: text/html; charset=UTF-8\r\n";
		self::$header .= "Content-Transfer-Encoding: 8bit\r\n";

		self::$template = str_replace(
			array(
				'{titulo}',
				'{url_logo}',
				'{contenido}',
                '{anio}',
                '{empresa}'
			),array(
				self::$nombre_emisor,
				base_url('application/images/logo.png'),
				$html,
                date("Y"),
                self::$nombre_emisor
			), self::$template);

		self::$header .= self::$template."\r\n\r\n";
		//self::$header .= "--".self::$uid."\r\n";
	}

	public static function addFile($name, $archivo, $conte_type='text/plain', $mas=true){
		if($mas)
			self::$header .= "--".self::$uid."\r\n";
		self::$header .= "Content-Type: ".$conte_type."; name=\"".$name."\"\r\n"; // use different content types here
		self::$header .= "Content-Transfer-Encoding: base64\r\n";
		self::$header .= "Content-Disposition: attachment; filename=\"".$name."\"\r\n\r\n";
		self::$header .= chunk_split(base64_encode($archivo))."\r\n\r\n";
	}

	public static function send($mail, $asunto){
		self::$header .= "--".self::$uid."--";
		return mail($mail, $asunto, self::$template, self::$header);
	}
	


	private static $template = '<center>
    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="backgroundTable">
    	<tr>
        	<td align="center" valign="top">
            	<table border="0" cellpadding="0" cellspacing="0" width="600" id="templateContainer">
                	<tr>
                    	<td align="center" valign="top">
                            <!-- // Begin Template Header \\ -->
                        	<table border="0" cellpadding="0" cellspacing="0" width="600" id="templateHeader">
                                <tr>
                                    <td style="font-size: 40px;font-weight: bold; color:#fff;text-align: center;background-color:#FF9326; height: 100px;">
                                        <img width="90" height="90" style="float: left; margin-left: 10px;" src="{url_logo}">
                                    	<span style="padding-top: 20px;">{titulo}</span>
                                    </td>
                                </tr>
                            </table>
                            <!-- // End Template Header \\ -->
                        </td>
                    </tr>
                	<tr>
                    	<td align="center" valign="top">
                            <!-- // Begin Template Body \\ -->
                        	<table border="0" cellpadding="0" cellspacing="0" width="600" id="templateBody">
                            	<tr>
                                    <td valign="top" class="bodyContent">
                        
                                        <!-- // Begin Module: Standard Content \\ -->
                                        <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                            <tr>
                                                <td valign="top" style="color: #555;">
                                                    <div mc:edit="std_content00">
                                                        {contenido}
                                                    </div>
                                                    <br />
    											</td>
                                            </tr>
                                        </table>
                                        <!-- // End Module: Standard Content \\ -->
                                        
                                    </td>
                                </tr>
                            </table>
                            <!-- // End Template Body \\ -->
                        </td>
                    </tr>
                	<tr>
                    	<td align="center" valign="top">
                            <!-- // Begin Template Footer \\ -->
                        	<table border="0" cellpadding="10" cellspacing="0" width="600" id="templateFooter" style="border-top: 1px dashed #555;">
                            	<tr>
                                	<td valign="top" class="footerContent">
                                    
                                        <!-- // Begin Module: Standard Footer \\ -->
                                        <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                            <tr>
                                                <td valign="top" width="350">
                                                    <div mc:edit="std_footer">
    													<em>Copyright &copy; {anio} {empresa}, Todos los derechos reservados.</em>
    													<br />
    													01 (312) 312 4311 <br />
                                                        <span style="color: #FF9326">contacto@consultoriacapp.com</span>
                                                    </div>
                                                </td>
                                                <td valign="top" width="190" id="monkeyRewards">
                                                    <div mc:edit="monkeyrewards">
                                                        
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        <!-- // End Module: Standard Footer \\ -->
                                    
                                    </td>
                                </tr>
                            </table>
                            <!-- // End Template Footer \\ -->
                        </td>
                    </tr>
                </table>
                <br />
            </td>
        </tr>
    </table>
    </center>';
	
}


?>