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

require_once "vendor/autoload.php";
use Medoo\Medoo;
use altayalp\FtpClient\FileFactory;
use altayalp\FtpClient\DirectoryFactory;
use altayalp\FtpClient\Servers\FtpServer;

class cronJob
{
    public $host;
    public $login;
    public $password;

    public $DbConnection;
    public $FtpServer;

    function __construct($database)
    {
        $this->host = 'localhost';
        $this->login = 'adnan';
        $this->password = '123';
        $this->DbConnection = $database;
        // FTP connection
        $this->FtpServer = new FtpServer($this->host);
        $this->FtpServer->login($this->login, $this->password);
        $this->FtpServer->turnPassive();

        $this->runner();
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
                $response = $this->DbConnection->update("ftpfiles", array(`status` => 1), array( id => $item['id']));
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
                    $this->downloadFiles();
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
        return $list;
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

    private function downloadFiles()
    {
        //$file->download('index.php', 'backup/local.php');
    }
}

// database connection
$database = new Medoo([
    // required
    'database_type' => 'mysql',
    'database_name' => 'cron_job',
    'server' => 'localhost',
    'username' => 'root',
    'password' => '',
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
$xyz = new cronJob($database);