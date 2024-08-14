The utility is designed for convenient redefinition of classes from the vendor by copying them to the *overriden* directory in the root of the project and adding them to the classmap, which ensures the priority of such classes over their originals in the autoloader. 
If the *overriden* directory does not exist, the utility will create it.

Usage:

    php vendor/bin/overrider --namespace="Foo\Bar\Baz" [-r, --recursive]
    
    --namespace         Required, accepts the namespace of a class that must already be registered with the autoloader.
    -r, --recursive     Optional, generates directories for the overridden class in accordance with the PSR-0.

Make sure you are running the utility from the project root, the composer.json file is available, and the overridden class is already registered in the autoloader.
