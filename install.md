Debian

sudo vim /etc/php/7.0/cli/conf.d/30-swoole.ini
debian 安装swoole   
sudo apt install php-pear  php-dev
sudo pecl install swoole

php -i |grep php.ini
add "extension=swoole.so" to php.ini

sudo vim /etc/php/7.0/cli/conf.d/30-swoole.ini
