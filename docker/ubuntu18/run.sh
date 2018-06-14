#!/bin/bash
echo "================================================"
echo " Ubuntu 18.04"
echo "================================================"

echo -n "[1/4] Starting MySQL 5.7 ........ "
# make sure mysql can create socket and lock
mkdir /var/run/mysqld && chmod 777 /var/run/mysqld
# run mysql server
nohup mysqld > /root/mysql.log 2>&1 &
# wait for mysql to become available
while ! mysqladmin ping -hlocalhost >/dev/null 2>&1; do
    sleep 1
done
# create database and user on mysql
mysql -u root >/dev/null << 'EOF'
CREATE DATABASE `php-crud-api` CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'php-crud-api'@'localhost' IDENTIFIED BY 'php-crud-api';
GRANT ALL PRIVILEGES ON `php-crud-api`.* TO 'php-crud-api'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF
echo "done"

echo -n "[2/4] Starting PostgreSQL 10 .... "
# ensure statistics can be written
mkdir /var/run/postgresql/10-main.pg_stat_tmp/ && chmod 777 /var/run/postgresql/10-main.pg_stat_tmp/
# run postgres server
nohup su - -c "/usr/lib/postgresql/10/bin/postgres -D /etc/postgresql/10/main" postgres > /root/postgres.log 2>&1 &
# wait for postgres to become available
until su - -c "psql -U postgres -c '\q'" postgres >/dev/null 2>&1; do
   sleep 1;
done
# create database and user on postgres
su - -c "psql -U postgres >/dev/null" postgres << 'EOF'
CREATE USER "php-crud-api" WITH PASSWORD 'php-crud-api';
CREATE DATABASE "php-crud-api";
GRANT ALL PRIVILEGES ON DATABASE "php-crud-api" to "php-crud-api";
\c "php-crud-api";
CREATE EXTENSION IF NOT EXISTS postgis;
\q
EOF
echo "done"

echo -n "[3/4] Skipping SQLServer 2017 ... "
echo "done"

echo -n "[4/4] Cloning PHP-CRUD-API v2 ... "
# install software
git clone --quiet https://github.com/mevdschee/php-crud-api2.git
echo "done"

echo "------------------------------------------------"

# run the tests
cd php-crud-api2
php test.php
