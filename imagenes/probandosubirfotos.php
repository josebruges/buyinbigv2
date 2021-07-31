<?php 

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

echo $_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva';
echo $_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva/carpetanueva2';
echo $_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva/carpetanueva2/item.php';
mkdir($_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva', 0777, true);
mkdir($_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva/carpetanueva2', 0777, true);
chmod($_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva', 0777);
chmod($_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva/carpetanueva2', 0777);
$handle = fopen($_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva/carpetanueva2/item.php','a+') or die('unable to create');
chmod($_SERVER['DOCUMENT_ROOT'] . 'imagenes/carpetanueva/carpetanueva2/item.php', 0777);
?>