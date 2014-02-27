<?php  
$file = $_GET['file'];
$path = $_GET['path'].'/';
$pass = $_GET['pass'];
$new_pass = $_GET['newpass'];

$file2 = 's_'.$file.'.pem';
$file3 = $file.'.pem';

$response = array($path.$file3);
$command1 = escapeshellcmd("openssl pkcs8 -inform DER -in {$file} -out {$file2} -passin pass:".escapeshellarg($pass)."");
$command2 = escapeshellcmd("openssl rsa -in {$file2} -des3 -out {$file3} -passout pass:{$new_pass}");
shell_exec($command1);
shell_exec($command2);

if (!copy($file3, '../../'.$path.$file3))
	$response[1] = '';

unlink($file);
unlink($file2);
unlink($file3);

echo json_encode($response);
?>