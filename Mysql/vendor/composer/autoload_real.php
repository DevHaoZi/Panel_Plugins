<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInita2b691310aa0529e5b91442ee7a3cebd
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

        spl_autoload_register(array('ComposerAutoloaderInita2b691310aa0529e5b91442ee7a3cebd', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInita2b691310aa0529e5b91442ee7a3cebd', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInita2b691310aa0529e5b91442ee7a3cebd::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
