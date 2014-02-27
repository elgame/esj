<?php  
$file = $_GET['file'];
$path = $_GET['path'].'/';

$file2 = $file.'.pem';

$response = array($path.$file, $path.$file2);
exec("openssl x509 -inform DER -outform PEM -in {$file} -pubkey -out {$file2}");

if (!copy($file, '../../'.$path.$file))
	$response[0] = '';
if (!copy($file2, '../../'.$path.$file2))
	$response[1] = '';

unlink($file);
unlink($file2);

echo json_encode($response);
// exec("openssl pkcs8 -inform DER -in nedr620710h76_1302281329s.key -out nedr620710h76_1302281329s.key.pem -passin pass:Piloto01");
// exec("openssl rsa -in nedr620710h76_1302281329s.key.pem -des3 -out nedr620710h76_1302281329s_p.key.pem -passout pass:Piloto01");
?>