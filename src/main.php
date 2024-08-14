<?php

require_once __DIR__ . '/../../../autoload.php';

const OVERRIDEN_CLASSES_DIR = 'overridden';

$options = getopt("r", ['namespace:', 'recursive']);

if (validateSignature($options))
    overrideClassByNamespace($options['namespace'], $options['recursive']);
else
    usage();

function overrideClassByNamespace($namespace, $recursive)
{
    if (!class_exists($namespace = trim($namespace, '\\')))
        error("Class \"$namespace\" not found.");
    else {
        $reflector = new \ReflectionClass($namespace);
        $fileName = $reflector->getFileName();

        if (is_dir(OVERRIDEN_CLASSES_DIR) && mb_stripos($fileName, realpath(OVERRIDEN_CLASSES_DIR)) === 0)
            error("Class \"$namespace\" is already overridden.");

        $destination = [OVERRIDEN_CLASSES_DIR];
        if ($recursive) {
            foreach (explode('\\', $namespace) as $dir) {
                $destination[] = $dir;
            }

            array_pop($destination);
        }

        $destination = implode(DIRECTORY_SEPARATOR, $destination);

        copyFile($fileName, $destination);
        sync();

        echo "The \"$namespace\" class was successfully overridden.";
    }
}

function validateSignature(&$options)
{
    $options['recursive'] = isset($options['recursive']) || isset($options['r']);
    return (isset($options['namespace']) && hasNotUnsupportedKeys($options));
}

function hasNotUnsupportedKeys($options)
{
    global $argc, $argv;

    switch ($argc - 1) {
        case 3: //? --namespace <namespace> -r
            return ($options['recursive'] && ($argv[1] == '--namespace' || $argv[2] == '--namespace'));
        case 2: //? --namespace=<namespace> -r OR --namespace <namespace>
            return ($options['recursive'] || $argv[1] == '--namespace');
        case 1: //? --namespace=<namespace>
            return true;
        default:
            return false;
    }
}

function usage()
{
    $usageText = <<<TEXT
    USAGE:
    php vendor/bin/overrider --namespace="Foo\Bar\Baz" [-r, --recursive]
    
    --namespace         Required, accepts the namespace of a class that must already be registered with the autoloader.
    -r, --recursive     Optional, generates directories for the overridden class in accordance with the PSR-0.
    
    NOTICE:
    Make sure you are running the utility from the project root, the composer.json file is available, and the overridden class is already registered in the autoloader.
    TEXT;

    die($usageText);
}

function error($errorMsg){
    die("ERROR:\n$errorMsg");
}

function copyFile($sourceFile, $destination)
{

    if (!is_dir($destination))
        mkdir($destination, 0755, true);

    if (!copy($sourceFile, $destination . DIRECTORY_SEPARATOR . basename($sourceFile))) {
        error("Failed to copy class file to \"$destination\".");
    }
}

function sync()
{
    if ($composer = file_get_contents('composer.json')) {
        $composerJson = json_decode($composer, true);
        if (isset($composerJson)) {
            if (!isset($composerJson['autoload']['classmap']))
                $composerJson['autoload']['classmap'] = [OVERRIDEN_CLASSES_DIR];
            else if (!in_array(OVERRIDEN_CLASSES_DIR, $composerJson['autoload']['classmap']))
                array_unshift($composerJson['autoload']['classmap'], OVERRIDEN_CLASSES_DIR);

            if (file_put_contents('composer.json', json_encode($composerJson, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)))
                echo `composer dump-autoload`;
            else
                error("Failed to update \"composer.json\".");
        } else
            error("Failed to parse \"composer.json\".");
    } else
        error("Failed to open \"composer.json\".");
}