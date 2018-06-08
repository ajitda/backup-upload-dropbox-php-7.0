<?php
require 'vendor/autoload.php';
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Dropbox;

ini_set('max_execution_time', 600);
ini_set('memory_limit','1024M');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$day = date("d");
$month = date("F");
$year = date("Y");
$time = date("h:i A");

$database = 'rtg';
$user = 'root';
$pass = 'Nop@MySQL2017!#';
$host = 'localhost';

$db_file = "rtg_".$day."_".$month."_".$year.".sql";

exec("mysqldump --user={$user} --password={$pass} --host={$host} {$database} --result-file={$db_file} 2>&1", $output);


$token = 'yR0gg4AYBcIAAAAAAAADkngaO49IM1c4SJ2BTMbl-dBcTHJMAUVP2kgjZupkOOgg';
$clientId = '77q37l8xr39jzim';
$clientSecret = 'c8yg4nf87ur1mlr';

//$projectFolder = $month.","."$year"."/".$day."-".$month." ".$time." GMT +6"
$app = new DropboxApp($clientId, $clientSecret, $token);
$dropbox = new Dropbox($app);
if($dropbox){
	$projectFolder = $month.","."$year"."/".$day."-".$month." ".$time." GMT +6";
	$src_file = "backup_".$day."_".$month."_".$year.".zip";
	
	$file_path = '../../../html';
	$zipfile = zipData($file_path, __DIR__.'/'.$src_file);

	if($zipfile){
		$src_path = __DIR__."/$src_file";
		$dpx_src_path = "/".$projectFolder."/".$src_file;

		$dropboxFile = new DropboxFile($src_path);
		$file = $dropbox->upload($dropboxFile, $dpx_src_path, ['autorename' => false]);

		$db_path = __DIR__."/$db_file";
		$dpx_db_path = "/".$projectFolder."/".$db_file;
		$dropboxDbFile = new DropboxFile($db_path);
		$file = $dropbox->upload($dropboxDbFile, $dpx_db_path, ['autorename' => false]);

		if($file){
			echo "upload successfull";
			unlink($src_file);
			unlink($db_path);
		}
	}
}else{
	echo "Your given dropbox credentials are wrong";
}

// Here the magic happens :)
function zipData($source, $destination) {
    if (extension_loaded('zip')) {
        if (file_exists($source)) {
            $zip = new ZipArchive();
            if ($zip->open($destination, ZIPARCHIVE::CREATE)) {
                //$source = realpath($source);
                //echo $source;exit;
                if (is_dir($source)) {
                    $iterator = new RecursiveDirectoryIterator($source);
                    // skip dot files while iterating
                    $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
                    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($files as $file) {
                        // $file = realpath($file);
                        if (is_dir($file)) {
                            $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                        } else if (is_file($file)) {
                            $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                        }
                    }
                } else if (is_file($source)) {
                    $zip->addFromString(basename($source), file_get_contents($source));
                }
            }
            return $zip->close();
        }
    }
    return false;
}
