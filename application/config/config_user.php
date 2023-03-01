<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Base Site URL
|--------------------------------------------------------------------------
|
| URL to your CodeIgniter root. Typically this will be your base URL,
| WITH a trailing slash:
|
|	http://example.com/
|
| If this is not set then CodeIgniter will guess the protocol, domain and
| path to your installation.
|http://localhost/sanjorge/   http://sanjorge.dev/
*/

$config['jsv']  = '1.591';
$config['snapshot_cam1']  = 'http://Bascula:Basc.2021$.@192.168.1.243/Streaming/channels/1/picture';
$config['snapshot_cam2']  = 'http://Bascula:Basc.2021$.@192.168.1.244/Streaming/channels/1/picture';
$config['snapshot_cam3']  = 'http://admin:12345@192.168.1.241/Streaming/channels/1/picture';
$config['snapshot_cam4']  = 'http://admin:12345@192.168.1.242/Streaming/channels/1/picture';

$config['is_bodega'] = 0;

// configuracion de clasificaciones unidas
$config['clasif_joins']  = ['2' => '29', '29' => '2'];
