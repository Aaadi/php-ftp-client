<?php
/**
 * Created by PhpStorm.
 * User: Adnan
 * Date: 19/07/2019
 * Time: 3:09 PM
 */

//https://medoo.in/doc
//https://github.com/php-fig/log
//https://github.com/altayalp/php-ftp-client

require_once "config.php";
require_once "vendor/autoload.php";
use Medoo\Medoo;
use altayalp\FtpClient\FileFactory;
use altayalp\FtpClient\DirectoryFactory;
use altayalp\FtpClient\Servers\FtpServer;

class cronJob
{
    public $DbConnection;
    public $saveOnDisk;

    public $sourceFtpHost;
    public $sourceFtpUser;
    public $sourceFtpPassword;
    public $FtpServer;

    public $destinationDirectory;

    function __construct($config)
    {
        $this->saveOnDisk = $config['saveOnDisk'];
        $this->sourceFtpHost = $config['sourceFtpHost'];
        $this->sourceFtpUser = $config['sourceFtpUser'];
        $this->sourceFtpPassword = $config['sourceFtpPassword'];
        $this->connectDb($config);

        // Source FTP connection
        $this->FtpServer = new FtpServer($this->sourceFtpHost);
        $this->FtpServer->login($this->sourceFtpUser, $this->sourceFtpPassword);
        $this->FtpServer->turnPassive();

        // Destination FTP connection
        $this->destinationDirectory = $config['destinationDirectory'];
        //if backup folder is not created yet
        if (!is_dir($this->destinationDirectory))
        {
            mkdir($this->destinationDirectory);
            echo 'Backup Folder created!<br/>';
        }
        $this->runner();
    }

    private function connectDb($config)
    {
        // database connection
        $this->DbConnection = new Medoo([
            // required
            'database_type' => 'mysql',
            'database_name' => $config['database_name'],
            'server' => $config['server'],
            'username' => $config['username'],
            'password' => $config['password'],
            // [optional]
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'port' => 3306,
            // [optional] Table prefix
            'prefix' => '',
            // [optional] Enable logging (Logging is disabled by default for better performance)
            'logging' => true,
            // [optional] MySQL socket (shouldn't be used with server and port)
            'socket' => '/tmp/mysql.sock',
            // [optional] driver_option for connection, read more from http://www.php.net/manual/en/pdo.setattribute.php
            'option' => [
                PDO::ATTR_CASE => PDO::CASE_NATURAL
            ],
            // [optional] Medoo will execute those commands after connected to the database for initialization
            'command' => [
                'SET SQL_MODE=ANSI_QUOTES'
            ]
        ]);
    }

    public function runner()
    {
        // check db where type D Status 0 records exist
        $records = $this->DbConnection->select("ftpfiles", "*",  [
            "type" => "D",
            "status" => 0
        ]);

        if (!empty($records))
        {
            foreach ($records as $item)
            {
                // make it a parent directory and get all its child files & directires save them all

                //get FTP files & directories
                $files = $this->getAllFiles($item['path']);
                $dirs = $this->getAllDir($item['path']);
                $this->saveFileDir($files, $dirs);
                // update parent directory status to 1
                $response = $this->DbConnection->update("ftpfiles", array("status" => 1), array( "id" => $item['id']));
                echo '<br/>Parent Directory: '.$item['path'].' Record successfully added!';
            }
        }
        else
        {
            //if record not found!
            //get FTP files & directories
            $files = $this->getAllFiles();
            $dirs = $this->getAllDir();
            $pathValues = array_merge($files, $dirs);
            // if $pathValues exist in db don't save anything
            if (!empty($pathValues))
            {
                $records = $this->DbConnection->select("ftpfiles", ["path", "type"], ["path" => $pathValues]);
                if (empty($records))
                {
                    $this->saveFileDir($files, $dirs);
                }
                else
                {
                    $res = $this->downloadFiles();
                    if ($res && $this->saveOnDisk)
                    {
                        $this->UploadToDestinationFtp();
                    }
                }
            }
        }
    }

    /**
     * @param string $path
     * @return array
     */
    public function getAllFiles($path = '')
    {
        //get FTP files
        $file = FileFactory::build($this->FtpServer);
        $list = $file->ls($path);
        $list = $this->prePendPath($path, $list);
        return $list;
    }

    /**
     * @param string $path
     * @return array
     */
    public function getAllDir($path = '')
    {
        $dir = DirectoryFactory::build($this->FtpServer);
        $list = $dir->ls($path);
        $list = $this->prePendPath($path, $list);
        return $list;
    }

    /**
     * @param $path
     * @param $list
     * @return array
     */
    public function prePendPath($path, $list)
    {
        $response = array();
        if (!empty($list))
        {
            foreach ($list as $item)
            {
                $response[] = $path.'/'.$item;
            }
        }
        else
        {
            $response = $list;
        }
        return $response;
    }
    /**
     * @param $data
     * @return bool
     */
    private function saveLog($data)
    {
        if (empty($data))
        {
            return false;
        }
        $response = $this->DbConnection->insert("ftpfiles", $data);
        //var_dump($this->DbConnection->error());exit;
        echo '<br/>Record Successfully added!';
        return $response;
    }

    /**
     * @param $files
     * @param $dirs
     */
    private function saveFileDir($files, $dirs)
    {
        if (!empty($files))
        {
            $input = array();
            foreach ($files as $file)
            {
                $input[] = array(
                    'path'      => $file,
                    'type'      => 'F',
                    'status'    => 1
                );
            }
            $res = $this->saveLog($input);
        }
        if (!empty($dirs))
        {
            $input = array();
            foreach ($dirs as $file)
            {
                $input[] = array(
                    'path'      => $file,
                    'type'      => 'D'
                );
            }
            $res = $this->saveLog($input);
        }
    }

    private function downloadFiles($destination = 'backup')
    {
        $records = $this->DbConnection->select("ftpfiles", "*", ["status" => 1]);
        if (!empty($records))
        {
            foreach ($records as $item)
            {
                $to = $destination.$item['path'];
                if ($item['type'] == 'D' && !is_dir($to))
                {
                    mkdir($to);
                    $response = $this->DbConnection->update("ftpfiles", array("status" => 2), array( "id" => $item['id']));
                    echo '<br/>Directory: '.$to.' created successfully!';
                }
            }
            //download files from FTP
            $file = FileFactory::build($this->FtpServer);
            foreach ($records as $item)
            {
                $to = $destination.$item['path'];
                if ($item['type'] == 'F')
                {
                    $res = $file->download($item['path'], $to);
                    $response = $this->DbConnection->update("ftpfiles", array("status" => 2), array( "id" => $item['id']));
                    echo '<br/>File: '.$to.' downloaded successfully!';
                }
            }

            return false;
        }
        else
        {
            echo 'DOne!';
            return true;
        }
    }

    private function UploadToDestinationFtp()
    {
        $records = $this->DbConnection->select("ftpfiles", "*", ["status" => 2]);
    }
}

$job = new cronJob($config);