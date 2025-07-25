<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitcb92b8d2c931b21248f98bdb4e379d30
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
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

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInitcb92b8d2c931b21248f98bdb4e379d30', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitcb92b8d2c931b21248f98bdb4e379d30', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitcb92b8d2c931b21248f98bdb4e379d30::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
