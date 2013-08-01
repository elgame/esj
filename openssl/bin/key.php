<?php  
$file = $_GET['file'];
$path = $_GET['path'].'/';
$pass = $_GET['pass'];
$new_pass = $_GET['newpass'];

$file2 = $file.'.pem';

$response = array($path.$file2);
exec("openssl pkcs8 -inform DER -in {$file} -out {$file2} -passin pass:{$pass}");
exec("openssl rsa -in {$file2} -des3 -out {$file2} -passout pass:{$new_pass}");

if (!copy($file2, '../../'.$path.$file2))
	$response[1] = '';

unlink($file);
unlink($file2);

echo json_encode($response);
?>