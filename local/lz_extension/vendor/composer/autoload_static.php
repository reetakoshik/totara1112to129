<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitdcff4c37013253f1cae3a62e0a97966b
{
    public static $prefixesPsr0 = array (
        's' => 
        array (
            'stringEncode' => 
            array (
                0 => __DIR__ . '/..' . '/paquettg/string-encode/src',
            ),
        ),
        'P' => 
        array (
            'PHPHtmlParser' => 
            array (
                0 => __DIR__ . '/..' . '/paquettg/php-html-parser/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInitdcff4c37013253f1cae3a62e0a97966b::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
