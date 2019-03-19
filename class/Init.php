<?php

namespace Moncton\WP\Starter;

use Composer\Script\Event;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\FileSystem\FileSystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;

class Init
{
    
    public static $config = array();

    public static function postUpdate(Event $event)
    {
        
    }

    /**
     * Composer post-autoload-dump callback
     * @Param Event
     */
    public static function postAutoloadDump(Event $event)
    {
       
        $config = self::loadConfig();
        

        $fileSystem = new Filesystem();

        
        if(!empty($config['ignore'])){

            foreach($config['ignore'] as $stream) {

                $tmp = $config['tmp-dir'].'/'.md5($stream);

                if($fileSystem->exists($tmp)){
                  
                    $fileSystem->mirror($tmp, $config['documentRoot'].'/'.$stream);
                    // remove tmp folder
                    $fileSystem->remove($tmp);

                }

            }
        }

        // remove unsed pulgins & themes
        $finder = new Finder();
        $finder->files()->in($config['themes'])
        ->directories()
        ->depth('== 0')
        ->name('twenty*');

        foreach ($finder as $file) {
            $fileSystem->remove($file->getRealPath());
        }

        $fileSystem->remove($config['plugins'].'/hello.php');

        if(!$fileSystem->exists($config['base'].'/config.ignore.yml')){

            $fileSystem->copy(__DIR__.'/config.ignore.yml', $config['base'].'/config.ignore.yml');
            
            $fileSystem->mirror($config['base'].'/JointsWP-child', $config['themes'].'/JointsWP-child');
        }

    }

     /**
     * Composer pre-autoload-dump callback
     * @Param Event
     */
    public static function preAutoloadDump(Event $event)
    {
       
        $config = self::loadConfig();
     
        $fileSystem = new Filesystem();

        if(!empty($config['ignore'])){

            if(!$fileSystem->exists($config['tmp-dir'])){
                $fileSystem->mkdir($config['tmp-dir']);
            }
            
            foreach($config['ignore'] as $stream) {

                if(!$fileSystem->exists($config['documentRoot'].'/'. $stream)){
                    continue;
                }

                $tmp = $config['tmp-dir'].'/'.md5(trim($stream, '/'));

                if($fileSystem->exists($tmp)){
                    // remove tmp folder if exist
                    $fileSystem->remove($tmp);
                }

                $fileSystem->mkdir($tmp);

                $fileSystem->mirror($config['documentRoot'].'/'. $stream, $tmp);
            }
        }

    }

    /**
     * Load config from config.yml
     * @return Array
     */
    public static function loadConfig()
    {                                  
        if(!count(self::$config)) {
            
            $base = realpath(__DIR__.'/..');

            $documentRoot = $base .'/www';
        
            $themes = $documentRoot.'/wp-content/themes';

            $themes = $documentRoot.'/wp-content/themes';
        
            $plugins = $documentRoot.'/wp-content/plugins';

             // Load defaut config file
            $default_file = realpath(__DIR__."/config.ignore.yml");
            self::$config = Yaml::parseFile($default_file);
           
            // Load local config file if exist
            $local_file = realpath($base."/config.ignore.yml");

            if (file_exists($local_file)) {
                self::$config = array_merge(self::$config, Yaml::parseFile($local_file));
            }

            self::$config['base'] = $base;
            self::$config['documentRoot'] = $documentRoot;
            self::$config['themes'] = $themes;
            self::$config['plugins'] = $plugins;
            self::$config['tmp-dir'] = $base.'/tmp';

        
        }

        return self::$config;
    }

}