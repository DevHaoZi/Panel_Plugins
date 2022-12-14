<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitaefbc7696692ea28cc10c59f0ac6613d
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

        spl_autoload_register(array('ComposerAutoloaderInitaefbc7696692ea28cc10c59f0ac6613d', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitaefbc7696692ea28cc10c59f0ac6613d', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitaefbc7696692ea28cc10c59f0ac6613d::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
