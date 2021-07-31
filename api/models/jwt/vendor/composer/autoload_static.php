<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf35a9a1f158994e0abbb1b07bc59d1a4
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf35a9a1f158994e0abbb1b07bc59d1a4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf35a9a1f158994e0abbb1b07bc59d1a4::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
