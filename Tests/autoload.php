<?php

spl_autoload_register(
    function ($class) {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
        if (file_exists(__DIR__.'/../'.$file)) {
            require __DIR__.'/../'.$file;

            return true;
        }

        return false;
    }
);
