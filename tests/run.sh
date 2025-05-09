#!/bin/bash

cd ..

composer dump-autoload -o

cd -

/www/server/php/84/bin/php -c /home/wheakerd/PhpstormProjects/super-kernel/framework/tests/php.ini