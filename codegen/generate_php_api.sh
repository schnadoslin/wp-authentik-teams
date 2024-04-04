npm install @openapitools/openapi-generator-cli -g
openapi-generator-cli generate -g php -o out -i https://phakir-ad.re-mic.de/api/v3/schema/

apt install php-curl php-xml

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '8a6138e2a05a8c28539c9f0fb361159823655d7ad2deecb371b04a83966c61223adc522b0189079e3e9e277cd72b8897') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

cd ./out
sudo composer update --no-interaction



