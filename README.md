# 5-minute PHP framework

Small and fast PHP framework usable for practically any small PHP project. Contains MySQL and MongoDB support, data crypting and automatic caching with Memcached.

## Features

* Fast (almost no overhead) and small (only 150KB of core files).
* Minimal memory usage with lazy-loading system prevents unneeded files from being loaded.
* MVC-based with simple template system.
* Supports MySQL and MongoDB databases out of the box (both can be used simultaneously).
* Can be easily extended with almost any database support, transparently to the rest of the code.
* Encrypts and decrypts (automatically) data passed to the database (encrypting can be defined for each model separately).
* Automatically caches database results with Memcached.
* Supports AJAX requests.
* Contains example of a simple user's object allowing to login the user.
* Easy to maintain and improve.

## Requirements

* PHP 5.3.
* Optional: [mcrypt](http://php.net/mcrypt) extension is required for encryption.
* Optional: [memcached](http://php.net/memcached) extension is required for caching.
* Optional: [PDO with MySQL driver](http://php.net/pdo) extension is required for MySQL usage.
* Optional: [MongoDB driver](http://php.net/mongo) extension is required for MongoDB usage.

## How to start

1. Download the source files (as [zip](https://github.com/pbudzon/5-minute-PHP-framework/zipball/master) or [tar.gz](https://github.com/pbudzon/5-minute-PHP-framework/tarball/master) or clone this repository).
2. Edit the `public_html/index.php` file to adjust config settings: set `absolute_url` and `relative_url` to proper values (refer to inline documentation for more details on config values).
3. *Optional*: you may also want to set the `cookie_name` value.
4. Point your server to `public_html`.
5. You're good to go!

## How to see the build-in example

1. Follow the steps from *How to start*.
2. Rename appropriate file from `app/models` to `usersModel.php` (for example, if you want to use MongoDB, rename `usersModel_mongodb.php` to `usersModel.php`).
3. Refer to the chosen model file for information how to add a example login data to your database.
4. Add appropriate config settings concerning your database to `index.php`:
 + For MySQL: `mysql_db_host`, `mysql_db_port`, `mysql_db_user`, `mysql_db_pass`.
 + For MongoDB: `mongo_db_socket` or `mongo_db_host` and `mongo_db_port`, optionally also `mongo_db_user`, `mongo_db_pass`.
5. Open the page, example login data is - login: _**test**_,  password: _**test**_. 

## FAQ

* _How to use automatic caching?_

Set the `memcache_host` config value to appropriate memcache host (`memcache_port` is also available if needed). That's all.


* _How to use models data encryption?_

Change `crypt_std_key` value to any key you want (with the same lenght) and set `$_crypted` value in appropriate models to `true`. Also, remember to encrypt all data before inserting it to the database (it will be decrypted automatically). Refer to usersModel_ files for example.


* _What should I do next?_

You may want to refer to the documentation of `appConfig::$_defaults` variable for more information about the config settings.


* _And after that?_

Code!


## LICENSE

Licensed under BSD 2-Clause License.

Copyright &copy; 2011 Paulina Budzoń. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.