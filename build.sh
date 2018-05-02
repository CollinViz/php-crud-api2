#!/bin/bash
START=$(date +%s.%N)
if [ ! -e target ]; then
    mkdir target
fi
if [ -e target/api.php ]; then
    rm target/api.php
fi
echo "<?php" > target/api.php
tee -a target/api.php >/dev/null <<'EOF'
/**
 * PHP-CRUD-API                 License: MIT
 * Maurits van der Schee: maurits@vdschee.nl
 * https://github.com/mevdschee/php-crud-api
 **/
EOF
echo 'namespace Com\Tqdev\CrudApi;' >> target/api.php
find . -path ./tests -prune -o -path ./target -prune -o -iname '*.php' | grep '\.php$' | sort -r | xargs cat | grep -v "^<?php\|^namespace \|^use \|spl_autoload_register\|^\s*//" >> target/api.php
FILECOUNT=`grep '/* source:' target/api.php | wc -l`
ERRORS=`php -l target/api.php`
if [ $? != 0 ]; then
    echo $ERRORS
    exit 1
fi;
END=$(date +%s.%N)
DIFF=$(echo "( $END - $START ) * 1000 / 1" | bc)
echo "$FILECOUNT files combined in $DIFF ms into 'target/api.php'"