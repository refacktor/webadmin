FILES
-----

1. webadmin.ini
This is the basic configuration read by webadmin.php


   ;;; database configurations
   [db]
   user = dbuser
   password = pass
   host = localhost
   port = 3306
   dbname = webadmin

   ;;; site specific configurations
   [site]
   base_url = http://localhost/proj/webadmin.php

   ;;; this is the site owner's email
   admin_mail = coderpurple@gmail.com

   ;;; this is site owner's password
   admin_password = paword

The webadmin.php script is coded to search for webadmin.ini in a folder above the webadmin.php
E.g. If webadmin is located in /var/www/project/webadmin.php, then the ini file needs to be located at /var/www/webadmin.ini

2. db.sql
MySQL database used for the project.

3. webadmin.php


Notes: 

1. For mail sending functionality, I presume that the machine is installed with Postfix or Sendmail utilities.
2. Please rename the webadmin.ini.template file to webadmin.ini in the appropriate directory.
