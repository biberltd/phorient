<?php
/**
 * Created by PhpStorm.
 * User: ertiz
 * Date: 15/01/2018
 * Time: 10:59
 */

namespace BiberLtd\Bundle\Phorient\Services;
use Symfony\Component\Filesystem\Filesystem;

class FileFactory
{

    private $fs;
    private $content;
    private $cacheFolder;
    /**
     * FileFactory constructor.
     */
    public function __construct($cacheFolder)
    {
        $this->fs = new Filesystem();
        $this->cacheFolder = $cacheFolder;
    }


    public function getFile($filename)
    {
        if(!$this->fs->exists($this->cacheFolder."/cm/"))
        {
            $this->fs->mkdir($this->cacheFolder."/cm/");
        }
        $fileWithFullPath = $this->cacheFolder."/cm/".$filename;
        if($this->fs->exists($fileWithFullPath)){
            $class = unserialize(file_get_contents($fileWithFullPath));

            return  $class;
        }


        return false;
    }

    public function createFile($filename, $class)
    {
        $fileWithFullPath = $this->cacheFolder."/cm/".$filename;
        if($file = $this->getFile($filename)!==false)
        {
            return $file;
        }
        file_put_contents($fileWithFullPath,serialize($class));

        return $this->getFile($filename);
    }
}