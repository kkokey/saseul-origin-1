## SASEUL Origin v1.1.0.20

#### Environment

- PHP 7.2
- Memcached
- MongoDB 4.0
- libsodium

### Install - CentOS, RedHat, Amazon Linux

Git

~~~~
yum install git -y
~~~~

C Compiler

~~~~
yum install gcc72 -y
~~~~

PHP 7.2 & pecl

~~~~
yum install php72 php7-pear php72-bcmath php72-devel php72-mbstring php72-opcache php72-pecl-memcached
~~~~

Memcached

~~~~
yum install memcached -y

service memcached start
chkconfig memcached on
~~~~

MongoDB

~~~~
echo "[mongodb-org-4.0]
name=MongoDB Repository
baseurl=https://repo.mongodb.org/yum/amazon/2013.03/mongodb-org/4.0/x86_64
gpgcheck=1
enabled=1
gpgkey=https://www.mongodb.org/static/pgp/server-4.0.asc" > /etc/yum.repos.d/mongodb-org-4.0.repo

yum install mongodb-org -y
pecl7 install mongodb
echo "extension=mongodb.so" >> /etc/php.ini

service mongod start
chkconfig mongod on
~~~~

libsodium

~~~~
wget https://download.libsodium.org/libsodium/releases/LATEST.tar.gz
tar -xvzf LATEST.tar.gz
cd libsodium-stable
./configure
make
make install

pecl7 install libsodium
echo "extension=sodium.so" >> /etc/php.ini
~~~~

SASEUL Origin

~~~~
useradd saseul
su - saseul
cd ~
git clone https://github.com/anonymous16966/saseul-origin
cd saseul-origin/src
php saseul_script
php saseul_script Start
php saseul_script Stat
~~~~

### Install - Windows 10

PHP 7.2 & Extensions [[Google Drive link](https://drive.google.com/open?id=1goKC7ZxOc2ao_f57PWc1O7Zz_CHZNC3O)]

Set php.ini

~~~~
...
zend_extension=[Directory]\ext\php_xdebug-2.8.0-7.2-vc15-x86_64.dll
...
~~~~

Memcached 

MongoDB


준비 중.