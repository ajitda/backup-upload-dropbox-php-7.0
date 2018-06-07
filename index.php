<?php
require 'vendor/autoload.php';

// Make sure the script can handle large folders/files
ini_set('max_execution_time', 600);
ini_set('memory_limit','3024M');
//echo __DIR__;exit;
// Start the backup!

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


class Backup
{
    private $dbxClient;
    private $projectFolder;
    /**
     * __construct pass token and project to the client method
     * @param string $token  authorization token for Dropbox API
     * @param string $project       name of project and version
     * @param string $projectFolder name of the folder to upload into
     */
    public function __construct($token, $projectFolder)
    {

        $this->dbxClient = new Spatie\Dropbox\Client($token);
        $this->projectFolder = $projectFolder;
    }

    /**
     * upload set the file or directory to upload
     * @param  [type] $dirtocopy [description]
     * @return [type]            [description]
     */
    public function upload($dirtocopy)
    {
        if (!file_exists($dirtocopy)) {

            exit("File $dirtocopy does not exist");

        } else {

            //if dealing with a file upload it
            if (is_file($dirtocopy)) {
                $this->uploadFile($dirtocopy);

            } else { //otherwise collect all files and folders

                $iter = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dirtocopy, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
                );

                //loop through all entries
                foreach ($iter as $file) {

                    $words = explode('/',$file);
                    $stop = end($words);

                    //if file is not in the ignore list pass to uploadFile method
                    if (!in_array($stop, $this->ignoreList())) {
                        $this->uploadFile($file);
                    }

                }
            }
        }
    }

    /**
     * uploadFile upload file to dropbox using the Dropbox API
     * @param  string $file path to file
     */
    public function uploadFile($file, $mode = 'add')
    {
        $path = "/".$this->projectFolder."/$file";
        $contents = file_get_contents($file);

        //if the contents is not empty upload otherwise do nothing
        if (! empty($contents)) {
            $this->dbxClient->upload($path, $contents, $mode);
        }
    }

    /**
     * ignoreList array of filenames or directories to ignore
     * @return array
     */
    public function ignoreList()
    {
        return array(
            '.DS_Store',
            'cgi-bin'
        );
    }
}
//var_dump($output);

if(file_exists('dropbox/vendor/autoload.php')){

    //set access token
    $token = 'yR0gg4AYBcIAAAAAAAADkngaO49IM1c4SJ2BTMbl-dBcTHJMAUVP2kgjZupkOOgg';

    $projectFolder = $month.","."$year"."/".$day."-".$month." ".$time." GMT +6";
    $projectFolder = "uploadBackupChtl/";
    $src_file = "backup_".$day."_".$month."_".$year.".zip";
    $zipfile = zipData('../src', __DIR__.'/'.$src_file);

    if($zipfile){
        echo 'Finished.';
        $bk = new Backup($token, $projectFolder);
        $bk->uploadFile($src_file);//file or folder to upload to dropbox
        /$bk->uploadFile($db_file);//file or folder to upload to dropbox
        //echo '<pre>';print_r($bk);exit;
        @unlink($src_file);
        @unlink($db_file);
        echo 'Upload Complete';
        //return new Filesystem(new DropboxAdapter($client));
    }
} else {
    echo "<h1>Please install via composer.json</h1>";
    echo "<p>Install Composer instructions: <a href='https://getcomposer.org/doc/00-intro.md#globally'>https://getcomposer.org/doc/00-intro.md#globally</a></p>";
    echo "<p>Once composer is installed navigate to the working directory in your terminal/command promt and enter 'composer install'</p>";
    exit;
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



