<?php

class ComposerAutoloaderInit {
    private static $loader;

    public static function loadClassLoader($class): void {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/../vendor/composer/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/../vendor/composer/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit', 'loadClassLoader'));

        require __DIR__ . '/ComposerStaticInit.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit::getInitializer($loader));

        $loader->register(true);

        $filesToLoad = \Composer\Autoload\ComposerStaticInit::$files;
        $requireFile = \Closure::bind(static function ($fileIdentifier, $file) {
            if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
                $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;

                require $file;
            }
        }, null, null);
        foreach ($filesToLoad as $fileIdentifier => $file) {
            $requireFile($fileIdentifier, $file);
        }

        return $loader;
    }
}
