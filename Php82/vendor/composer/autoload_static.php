<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1f350c607bb73b3133f2324b050c5e2f
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Plugins\\Php82\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Plugins\\Php82\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Plugins\\Php82\\Controllers\\Php82Controller' => __DIR__ . '/../..' . '/Controllers/Php82Controller.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1f350c607bb73b3133f2324b050c5e2f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1f350c607bb73b3133f2324b050c5e2f::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1f350c607bb73b3133f2324b050c5e2f::$classMap;

        }, null, ClassLoader::class);
    }
}