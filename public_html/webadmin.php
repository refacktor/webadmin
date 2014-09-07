<?php
// source: http://cker.name/webadmin/
/*
 * webadmin.php - a simple Web-based file manager
 * Copyright (C) 2004-2011  Daniel Wacker [daniel dot wacker at web dot de]
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * -------------------------------------------------------------------------
 * While using this script, do NOT navigate with your browser's back and
 * forward buttons! Always open files in a new browser tab!
 * -------------------------------------------------------------------------
 *
 * This is Version 0.9, revision 12
 * =========================================================================
 *
 * Changes of revision 12
 * [bhb at o2 dot pl]
 *    added Polish translation
 * [daniel dot wacker at web dot de]
 *    switched to UTF-8
 *    fixed undefined variable
 *
 * Changes of revision 11
 * [daniel dot wacker at web dot de]
 *    fixed handling if folder isn't readable
 *
 * Changes of revision 10
 * [alex dash smirnov at web.de]
 *    added Russian translation
 * [daniel dot wacker at web dot de]
 *    added </td> to achieve valid XHTML (thanks to Marc Magos)
 *    improved delete function
 * [ava at asl dot se]
 *    new list order: folders first
 *
 * Changes of revision 9
 * [daniel dot wacker at web dot de]
 *    added workaround for directory listing, if lstat() is disabled
 *    fixed permisson of uploaded files (thanks to Stephan Duffner)
 *
 * Changes of revision 8
 * [okankan at stud dot sdu dot edu dot tr]
 *    added Turkish translation
 * [j at kub dot cz]
 *    added Czech translation
 * [daniel dot wacker at web dot de]
 *    improved charset handling
 *
 * Changes of revision 7
 * [szuniga at vtr dot net]
 *    added Spanish translation
 * [lars at soelgaard dot net]
 *    added Danish translation
 * [daniel dot wacker at web dot de]
 *    improved rename dialog
 *
 * Changes of revision 6
 * [nederkoorn at tiscali dot nl]
 *    added Dutch translation
 *
 * Changes of revision 5
 * [daniel dot wacker at web dot de]
 *    added language auto select
 *    fixed symlinks in directory listing
 *    removed word-wrap in edit textarea
 *
 * Changes of revision 4
 * [daloan at guideo dot fr]
 *    added French translation
 * [anders at wiik dot cc]
 *    added Swedish translation
 *
 * Changes of revision 3
 * [nzunta at gabriele dash erba dot it]
 *    improved Italian translation
 *
 * Changes of revision 2
 * [daniel dot wacker at web dot de]
 *    got images work in some old browsers
 *    fixed creation of directories
 *    fixed files deletion
 *    improved path handling
 *    added missing word 'not_created'
 * [till at tuxen dot de]
 *    improved human readability of file sizes
 * [nzunta at gabriele dash erba dot it]
 *    added Italian translation
 *
 * Changes of revision 1
 * [daniel dot wacker at web dot de]
 *    webadmin.php completely rewritten:
 *    - clean XHTML/CSS output
 *    - several files selectable
 *    - support for windows servers
 *    - no more treeview, because
 *      - webadmin.php is a >simple< file manager
 *      - performance problems (too much additional code)
 *      - I don't like: frames, java-script, to reload after every treeview-click
 *    - execution of shell scripts
 *    - introduced revision numbers
 *
/* ------------------------------------------------------------------------- */


/*
 * 
 */

session_start();
$msg = "";

$case_failure = 2;
global $email, $passwords, $justification;
$die_and_reload = false;

$vars = parse_ini_file("../webadmin.ini",true); 
$debug=false;

$time_to_refresh = false;
if(isset($vars['debug']['write_log_file']))
   $debug=true;
if(isset($vars['debug']['time_to_refresh']))
   $time_to_refresh=$vars['debug']['time_to_refresh'];

if(!isset($vars['debug']['NO_REPORTING']))
   error_reporting(0);

if($debug){
   log_this(date(DATE_ATOM). ' $_GET - ' . return_var_dump($_GET));
   log_this(date(DATE_ATOM). ' $_POST - ' . return_var_dump($_POST));
   log_this(date(DATE_ATOM). ' $_SESSION - ' . return_var_dump($_SESSION));
}

if(isset($_GET['logout'])){
  $_SESSION['login']='';
  unset($_SESSION['login']);
  $_SESSION['mail']='';
  unset($_SESSION['mail']);
  header("Location: " . $vars['site']['base_url']);
}

if(!isset($_SESSION['login'])){

  $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
  $_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
  $clear_fields = false;

  if(isset($_GET['activate'])){
     // activation case

     if(!empty($_GET['key']) && isset($_GET['key']))
      {
         try { 
	         $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
  		 $case_failure = 0;
         } catch (Exception $exc) {
            $msg = "ERROR 101. Please contact your site administrator.";
         } 
    
        $code=mysqli_real_escape_string($connection,$_GET['key']);
		$query="SELECT uid FROM users WHERE activation='$code'";
        if($debug)
           log_this(date(DATE_ATOM). ' query - ' . $query);

        $c=mysqli_query($connection,$query);

        if(mysqli_num_rows($c) > 0)
        {
	   $q1 = "SELECT uid FROM users WHERE activation='$code' and status='0'";
           if($debug)
              log_this(date(DATE_ATOM). ' query - ' . $q1);
           $count=mysqli_query($connection, $q1);
           if(mysqli_num_rows($count) == 1)
           {
	      $q2 = "UPDATE users SET status='1' WHERE activation='$code'";
              mysqli_query($connection, $q2);
	      if($debug)
                 log_this(date(DATE_ATOM). ' query - ' . $q2);
              $msg="Congratulations! Your account is successfully activated.";
  	      $case_failure = 0;
              $clear_fields = true;
           }
           else
           {
              $msg ="Your account is already active, no need to activate again";
              $clear_fields = true;
  	      $case_failure = 0;
           }
        } else {
            $msg ="Wrong activation code.";
            $clear_fields = true;
            $case_failure = 0;
        }
        mysqli_close($connection); 
      }

     if(!empty($_GET['code']) && isset($_GET['code'])&& !empty($_GET['ownerk']) && isset($_GET['ownerk']))
     {
        try { 
            $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
        } catch (Exception $exc) {
            $msg = "ERROR 101. Please contact your site administrator.";
        } 
       
       $code=mysqli_real_escape_string($connection,$_GET['code']);
       $ownerk=mysqli_real_escape_string($connection,$_GET['ownerk']);
       $c=mysqli_query($connection,"SELECT uid FROM users WHERE activation='$code' and owner_key='$ownerk'");
 
       if(mysqli_num_rows($c) > 0)
       {
          $count=mysqli_query($connection,"SELECT uid,email FROM users WHERE activation='$code' and owner_authorized='0' and owner_key='$ownerk'");
          if(mysqli_num_rows($count) == 1)
          {
             mysqli_query($connection,"UPDATE users SET owner_authorized='1' WHERE activation='$code' and owner_key='$ownerk'");
             $vals = mysqli_fetch_array($count);
             $to=$vals['email'];
             $msg="User account - $to is successfully authorized.";
             $subject="webadmin.php - Login authorized";
             $base_url = $vars['site']['base_url'];
             $body='Hi ' .$to. ', <br/> <br/> Congratulations. The site owner has approved of your login to webadmin. <a href="'.$base_url.'">Login now</a>';
             $headers  = 'MIME-Version: 1.0' . "\r\n";
             $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
             mail($to, $subject, $body, $headers,'-froot@localhost'); 
             $clear_fields = true;
          }
          else
          {
             $msg ="User account already active.";
             $clear_fields = true;
          }
       } else {
          $msg ="Something went wrong. Either a wrong activation code or a wrong key was submitted. Contact your site administrator.";
          $clear_fields = true;
       }
       mysqli_close($connection);
       print_and_reload($msg, 3,  $vars['site']['base_url']);
     }
    
     if(!empty($_GET['code']) && isset($_GET['code']))
     {
         $msg = "Please contact site administrator for authorization."; 
         $clear_fields = true;
   	 $case_failure = 0;
     }
     show_register($msg, $case_failure, $email, $passwords, $justification,$clear_fields,true);
  }  

  if(isset($_POST['register'])){

    // register case
    $case_failure = 1;
         
    if(!empty($_POST['email']) && isset($_POST['email']) &&  !empty($_POST['password']) &&  isset($_POST['password']) && !empty($_POST['justification']) && isset($_POST['justification'])  )
    {
       // username and password sent from form
       $email = trim(mysql_escape_string($_POST['email']));
       $justification = trim(mysql_escape_string($_POST['justification']));
       $passwords = trim(mysql_escape_string($_POST['password']));
       $password = md5($passwords);
       
       // regular expression for email check
       $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/';
       
       if(preg_match($regex, $email))
       {  
          try { 
         $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
          } catch (Exception $exc) {
         $msg = "ERROR 101. Please contact your site administrator.";
                $clear_fields = true;
          } 
      
          $activation=md5($email.time()); // encrypted email+timestamp
          $count=mysqli_query($connection,"SELECT uid FROM users WHERE email='$email'");

          // email check
          if(mysqli_num_rows($count) < 1)
          {
             mysqli_query($connection,"INSERT INTO users(email,password,activation,justification) VALUES('$email','$password','$activation', '$justification')");
             // sending email
             $to=$email;
             $subject="webadmin.php - Email verification";
             $base_url = $vars['site']['base_url'];
             $body='Hi, <br/> <br/> We need to make sure you are human. Please verify your email and get started using your webadmin account. <br/> <br/> <a href="'.$base_url.'?activate=true&key='.$activation.'">'.$base_url.'?activate=true&key='.$activation.'</a>';
             $headers  = 'MIME-Version: 1.0' . "\r\n";
             $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
             mail($to, $subject, $body, $headers,'-froot@localhost'); 

             // admin email 
             $body_admin='Hi, <br/> <br/> A new user - [[USER]] wants to activate his/her account. Please authorize by visiting the attached link. <br/> <br/> <a href="[[URL]]">[[URL]]</a>';
             $values = array(
  		  'USER' => $email,
		  'URL' => $base_url . '?activate=true&code=' . $activation,
		);
             $qu = "UPDATE users SET owner_key='[[OWNER_KEY]]' WHERE email='$email'"; 
	     mail_admin('New user activation' , $body_admin, $values, $qu);
             $msg= "Registration successful, please check your email.";
             $case_failure = 0;
             $clear_fields = true;
          } else {
             $msg= 'The email is already taken, please try new.';
             $clear_fields = true;
          }
        mysqli_close($connection); 
       } else {
           $msg = 'The email you have entered is invalid, please try again.';
           $clear_fields = false;
       }
    } else {
       $msg = "Please fill all three fields: email, name and justification for registration!";
       $clear_fields = false;
       $case_failure = 1;
    }
  } else {
    // case login
    
    $case_failure = 0;
    if(!empty($_POST['email']) && isset($_POST['email']) &&  !empty($_POST['password']) &&  isset($_POST['password']))
    {
       // username and password sent from form
       $email = trim(mysql_escape_string($_POST['email']));
       $passwords = trim(mysql_escape_string($_POST['password']));
       $password = md5($passwords);
       
       // regular expression for email check
       $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/';
       
       if(preg_match($regex, $email))
       { 
           // check for admin
            if ($email == $vars['site']['admin_mail'] && $passwords == $vars['site']['admin_password']){
              $_SESSION['login'] = "0";
              $_SESSION['mail'] = $email;
              print_and_reload('Logging in as admin.', 2, $vars['site']['base_url']);
            }
 
          try { 
             $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
          } catch (Exception $exc) {
             $msg = "ERROR 101. Please contact your site administrator.";
          } 
          
          $count = mysqli_query($connection,"SELECT uid, status, owner_authorized FROM users WHERE email='$email' and password='$password'");

          $cnt = mysqli_num_rows($count);
          if($cnt>1) {
                 $msg = "ERROR 102. Please contact your site administrator.";
              }else if ( $cnt == 0 ) {
                 $msg = "Invalid email and/or password combination.";
                 $clear_fields = false;
              }else if ( $cnt == 1 ){
                 $vals = mysqli_fetch_array($count);    
                 if ($vals['status'] != '1'){
                    $msg = "You have not yet activated your email.";
                    $clear_fields = false;
                 } else if ($vals['owner_authorized'] != '1') {
                    $msg = "Your account is awaiting site owner's authorization.";
                    $clear_fields = false;
                 } else {
          	    $_SESSION['login'] = $vals['uid'];
            	    $_SESSION['mail'] = $email;
                    header("refresh: 0;");
                 }    
              }
          mysqli_close($connection);
       }else{
            $msg = "The email you entered is invalid.";
            $clear_fields = false;
	    $case_failure=0;
       }
     } else {
        if(isset($_POST))
          $msg = "Please fill email and password fields..";
        $clear_fields = false;
	$case_failure=0;
     } 
  }
  if((isset($_GET['action']) && $_GET['action']=='view') ||  (isset($_GET['action']) && $_GET['action']=='login')){
     if(isset($msg) && $msg!=""){
        show_register($msg,0 , "", "", "","",true);
     }else{
        show_register("Please login to view the files.","" , "", "", "","",true);
     }

  } elseif ( (isset($_GET['action']) && $_GET['action']  == 'login' ) || (isset($_GET['action']) && $_GET['action']  == 'register' )){
     show_register($msg, $case_failure, $email, $passwords, $justification,$clear_fields,true);
  } 
  else{
     show_register($msg, $case_failure, $email, $passwords, $justification,$clear_fields,false);
  }
} 

function print_and_reload($msg, $seconds, $url){
       global $debug,$time_to_refresh;
       if($debug){
          $seconds = 10;
          if(!$time_to_refresh)
               $time_to_refresh = 5;
          log_this(date(DATE_ATOM). ' print_and_reload - ' . $msg. ' && url = ' .$url. '\n');
          $seconds = $time_to_refresh;
       }

      
       echo $msg;
           
       debug_print_backtrace();
       
       die();
}

function take_url_to($url){
           echo '<script type="text/javascript">
           function doReload(){
           <!--
           window.location. = "' . $url . '" 
           //-->
           }
           setInterval("doReload()", ' . ($seconds * 1000). ');
           </script>';
           die();
}

/* Your language:
 * 'en' - English
 * 'de' - German
 * 'fr' - French
 * 'it' - Italian
 * 'nl' - Dutch
 * 'se' - Swedish
 * 'sp' - Spanish
 * 'dk' - Danish
 * 'tr' - Turkish
 * 'cs' - Czech
 * 'ru' - Russian
 * 'pl' - Polish
 * 'auto' - autoselect
 */
$lang = 'auto';

/* Homedir:
 * For example: './' - the script's directory
 */
$homedir = './';

/* Size of the edit textarea
 */
$editcols = 80;
$editrows = 25;

/* -------------------------------------------
 * Optional configuration (remove # to enable)
 */

/* Permission of created directories:
 * For example: 0705 would be 'drwx---r-x'.
 */
# $dirpermission = 0705;

/* Permission of created files:
 * For example: 0604 would be '-rw----r--'.
 */
# $filepermission = 0604;

/* Filenames related to the apache web server:
 */
$htaccess = '.htaccess';
$htpasswd = '.htpasswd';

/* ------------------------------------------------------------------------- */

if (get_magic_quotes_gpc()) {
  array_walk($_GET, 'strip');
  array_walk($_POST, 'strip');
  array_walk($_REQUEST, 'strip');
}

if (isset($_GET) && array_key_exists('image', $_GET)) {
  header('Content-Type: image/gif');
  die(getimage($_GET['image']));
}

if (!function_exists('lstat')) {
  function lstat ($filename) {
    return stat($filename);
  }
}

$delim = DIRECTORY_SEPARATOR;

if (function_exists('php_uname')) {
  $win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? true : false;
} else {
  $win = ($delim == '\\') ? true : false;
}

//die(" all paths - " . get_all_paths('C:\xampp\htdocs'));
//die(" all paths - " . get_all_paths('C:\xampp\htdocs\test.txt'));
//die(" all paths - " . get_all_paths('C:\xampp\My htdocs\t-test.txt\file'));

if (!empty($_SERVER['PATH_TRANSLATED'])) {
  $scriptdir = dirname($_SERVER['PATH_TRANSLATED']);
} elseif (!empty($_SERVER['SCRIPT_FILENAME'])) {
  $scriptdir = dirname($_SERVER['SCRIPT_FILENAME']);
} elseif (function_exists('getcwd')) {
  $scriptdir = getcwd();
} else {
  $scriptdir = '.';
}
$homedir = relative2absolute($homedir, $scriptdir);

$dir = (array_key_exists('dir', $_REQUEST)) ? $_REQUEST['dir'] : $homedir;

if (isset($_POST) && array_key_exists('olddir', $_POST) && !path_is_relative($_POST['olddir'])) {
  $dir = relative2absolute($dir, $_POST['olddir']);
}

$directory = simplify_path(addslash($dir));

$files = array();
$action = '';
$access_perm = '';
if (!empty($_POST['submit_all'])) {
  $action = $_POST['action_all'];
  for ($i = 0; $i < $_POST['num']; $i++) {
    if (array_key_exists("checked$i", $_POST) && $_POST["checked$i"] == 'true') {
      $files[] = $_POST["file$i"];
    }
  }
} elseif (!empty($_REQUEST['action'])) {
  $action = $_REQUEST['action'];
  $files[] = relative2absolute($_REQUEST['file'], $directory);
} elseif (!empty($_POST['submit_upload']) && !empty($_FILES['upload']['name'])) {
  $files[] = $_FILES['upload'];
  $action = 'upload';
} elseif (isset($_POST) && array_key_exists('num', $_POST)) {
  for ($i = 0; $i < $_POST['num']; $i++) {
    if (array_key_exists("submit$i", $_POST)) break;
    if (array_key_exists("Read$i", $_POST)) {  $access_perm='read'; break;}
    if (array_key_exists("Write$i", $_POST)) { $access_perm= 'write'; break;}
  }
  if ($i < $_POST['num']) {
    $action = $_POST["action$i"];
    $files[] = $_POST["file$i"];
  }
}
if (isset($_POST) && empty($action) && (!empty($_POST['submit_create']) || (array_key_exists('focus', $_POST) && $_POST['focus'] == 'create')) && !empty($_POST['create_name'])) {
  $files[] = relative2absolute($_POST['create_name'], $directory);
  switch ($_POST['create_type']) {
  case 'directory':
    $action = 'create_directory';
    break;
  case 'file':
    $action = 'create_file';
  }
}
if (sizeof($files) == 0) $action = ''; else $file = reset($files);

if ($lang == 'auto') {
  if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) >= 2) {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
  } else {
    $lang = 'en';
  }
}

$words = getwords($lang);
global $site_charset;
if ($site_charset == 'auto') {
  $site_charset = $word_charset;
}

$cols = ($win) ? 4 : 7;

if (!isset($dirpermission)) {
  $dirpermission = (function_exists('umask')) ? (0777 & ~umask()) : 0755;
}
if (!isset($filepermission)) {
  $filepermission = (function_exists('umask')) ? (0666 & ~umask()) : 0644;
}

if (!empty($_SERVER['SCRIPT_NAME'])) {
  $self = html(basename($_SERVER['SCRIPT_NAME']));
} elseif (!empty($_SERVER['PHP_SELF'])) {
  $self = html(basename($_SERVER['PHP_SELF']));
} else {
  $self = '';
}

if (!empty($_SERVER['SERVER_SOFTWARE'])) {
  if (strtolower(substr($_SERVER['SERVER_SOFTWARE'], 0, 6)) == 'apache') {
    $apache = true;
  } else {
    $apache = false;
  }
} else {
  $apache = true;
}

if(!empty($access_perm) && isset($access_perm) && $access_perm!="") {
  $action=$access_perm;
}

if($debug)
  log_this(date(DATE_ATOM). ' ACTION - ' . $action. '\n');
 
switch ($action) {

case 'askpermission':
   global $vars, $delim;
   
   $fileperm = $_POST['fileperm'];
   $cp = $_POST['perm'];
   $is_dir = $_POST['is_dir'];
   $rwaccess = $_POST['rwaccess'];
   
   $file ="";
   $filesl = "";
   
   /*if (get_magic_quotes_gpc()) {
       $file = stripslashes($fileperm);
   }
   else {
	   $file = $fileperm;	
   }*/
   $file = $fileperm;	
   $file = relative2absolute($file);	
   
   if(strlen($is_dir)>0){
      $file=addslash($file);	
   }
   
   $filesl = mysql_real_escape_string($file);
   
   $at = ($cp=='read') ? '0':'1';   
   
   $connection =get_connection(); 
   $arr = array($at); 
   if($is_dir || ends_with($file,$delim) || $rwaccess)
      $arr = array('0', '1');
   foreach ($arr as $val)
   { 
      $c=mysqli_query($connection,"SELECT * FROM `file_access` WHERE uid='". $_SESSION['login'] . "' and path='" . $filesl . "' and access_type='$val'");
 
      if($c && mysqli_num_rows($c) >0){
         $uquery = "UPDATE `file_access` SET owner_authorized='0' WHERE uid = '" . $_SESSION['login']. "' and path = '" .$filesl. "' and access_type='$val'";
         if($debug)
            log_this(date(DATE_ATOM). ' query - ' . $uquery);
         mysqli_query($connection,$uquery);
      }else{
         $iquery="INSERT INTO `file_access` (`uid`, `path`, `access_type`, `owner_authorized`, `updated_path`) VALUES('". $_SESSION['login'] . "','" .$filesl. "', '$val', '0','')";
         if($debug)
           log_this(date(DATE_ATOM). ' query - askpermission - ' . $iquery);
         mysqli_query($connection,$iquery);
      }
   }
   mysqli_close($connection);
 
   /////////// admin_mail ////////////////////
   $body_admin='Hi, <br/>User - [[USER]]  needs ' . ($rwaccess? 'read/write' : $cp) . ' access to ' . $file . '. <br/><br/>You can grant it by clicking the link below: ';
   $body_admin.='<a href="[[URL1]]">Grant ' .($rwaccess? 'read/write' : $cp). '  access</a>.<br/>';
   $body_admin.='You can deny it by clicking the link below:<br/><a href="[[URL2]]">Deny ' .($rwaccess? 'read/write' : $cp). ' access</a>.<br/>Thanks.';
   $values = array('USER' => $_SESSION['mail'],
 		  'URL1' => $vars['site']['base_url']. '?action=' . ($rwaccess?'read_write':(($at == '1')? 'write': 'read')) .  '_access&file=' . $file . '&user=' . $_SESSION['mail'],
 		  'URL2' => $vars['site']['base_url']. '?action=' . ($rwaccess?'read_write':(($at == '1')? 'write': 'read')) .  '_deny&file=' . $file . '&user=' . $_SESSION['mail']
 		);
   $qu = "UPDATE file_access SET owner_key='[[OWNER_KEY]]' WHERE uid='". $_SESSION["login"] . "' and path='$filesl'"; 
   mail_admin('webadmin.php - ' .ucfirst($cp). ' access required' , $body_admin, $values, $qu);
   /////////// admin_mail end ////////////////////
  
   print_and_reload("Notified site owner for a $cp access to " .$file, 3, $vars['site']['base_url']);

break;

case 'presetvalues':
 $messageOnly = false;
 if(isset($_POST['password']) && !empty($_POST['password']) && isset($_POST['cpassword']) && !empty($_POST['cpassword']) && isset($_POST['key']) && !empty($_POST['key']))
    {
       $p = trim(mysql_escape_string($_POST['password']));
       $cp = trim(mysql_escape_string($_POST['cpassword']));
       $key = trim(mysql_escape_string($_POST['key']));
       if(strlen($key)<32){
	   $msg = "You have submitted an invalid key.";
	   $_GET['key']=$key;
       }else  if($p != $cp){
	   $msg = "The passwords you have submitted do not match. Please try again.";
	   $_GET['key']=$key;
       } else { 
           $connection =get_connection(); 
           $query ="SELECT uid FROM users WHERE activation='$key' and owner_authorized='1'";
           $count = mysqli_query($connection,$query);
           if(mysqli_num_rows($count) == 0){
               $msg = "Please retry. There was no such request received or your account is under moderation.";
	       $_GET['key']=$key;
           }else{
               $password = md5($p);
	       $query = "UPDATE users SET password = '$password', activation='' WHERE activation = '$key'";
               $t = mysqli_query($connection,$query);
               $msg = "Your password has been successfully reset.";
               $messageOnly=true;
           }
	   mysqli_close($connection);
       }
    }else{
	   $msg = "Invalid password selected. Please try a new one.";
    }    
    show_register ($msg, 3, "", "", "", true, true,$messageOnly); 
break;

case 'preset':
 $messageOnly = true;
 if(!empty($_GET['key']) && isset($_GET['key']))
    {
       $key = trim(mysql_escape_string($_GET['key']));
       $connection =get_connection(); 
       $query ="SELECT uid FROM users WHERE activation='$key' and owner_authorized='1'";
       $count = mysqli_query($connection,$query);
       if(mysqli_num_rows($count) == 0){
	   // no such request
	   $msg = "Please retry. There was no such request received.";
       }else{
           // show password reset fields
	   $passwords = trim(mysql_escape_string($_POST['password']));
           $password = md5($passwords);
	   $msg = "Please fill with a new password.";
	   $messageOnly=false;
       }
    }else{
        //invalid password reset attempt
       $msg = "Invalid password reset event.";
    }    
    show_register ($msg, 3, "", "", "", true, true,$messageOnly); 
break;

case 'forgot':
    if(!empty($_POST['email']) && isset($_POST['email']) &&  !empty($_POST['forgot']) &&  isset($_POST['forgot']))
    {
       $email = trim(mysql_escape_string($_POST['email']));
       
       // regular expression for email check
       $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/';
       
       if(preg_match($regex, $email))
       { 
          $connection =get_connection(); 
          $query ="SELECT uid FROM users WHERE email='$email' and owner_authorized='1'";
       $count = mysqli_query($connection,$query);
      $cnt = mysqli_num_rows($count);
      if($cnt>1) {
             $msg = "ERROR 102. Please contact your site administrator.";
          }else if ( $cnt == 0 ) {
         $msg = "Your registration request is awaiting moderation.";
         $clear_fields = false;
      }else if ( $cnt == 1 ){    
         $activation=md5($email.time()); // encrypted email+timestamp
         $query = "UPDATE users SET activation='".$activation ."' WHERE email='" .$email. "'";
             mysqli_query($connection,$query);
                // sending email
             $to=$email;
             $subject="webadmin.php - Password recovery";
             $base_url = $vars['site']['base_url'];
             $body='Hi, <br/> <br/> We received a request for a password reset of your webadmin account. If you would like to change your password please visit the link below. <br/> <br/> <a href="'.$base_url.'?action=preset&key='.$activation.'">'.$base_url.'?action=preset&key='.$activation.'</a>';
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
             mail($to, $subject, $body, $headers,'-froot@localhost'); 
             $msg= "Password reset details sent to your email.";
          }
          mysqli_close($connection);
       } else {
        $msg = "Improper email address.";
      $clear_fields = false;
       }
     } else {
       if(isset($_POST))
          $msg = "Please fill the email field.";
      $clear_fields = false;
     }
     show_register ($msg, 2, "", "", "", true, true); 
break;

case 'read_access':
   global $debug, $delim;
   try { 
       $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
   } catch (Exception $exc) {
       $msg = "ERROR 101. Please contact your site administrator.";
   } 

   $get_email=mysqli_real_escape_string($connection,$_GET['user']);
   $get_path=mysqli_real_escape_string($connection,$_GET['file']);
   $get_pathsl = addslashes($get_path);
   $ownerk=mysqli_real_escape_string($connection,$_GET['ownerk']);

   if ( $get_email == "" ){
       print_and_reload("User email is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $get_path == "" ){
       print_and_reload("File path is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $ownerk == "" ){
       print_and_reload("Authorization invalid! Please contact your administrator.", 3, $vars['site']['base_url']);
    }

   $at="";
   
   $access_types = array('0');    
   
//   if(is_dir($get_path)||ends_with($get_path,$delim)){
//      $access_types = array('0', '1');    
//   }
   
   foreach($access_types as $at){
      $squery = "SELECT * FROM `file_access`, users  WHERE `file_access`.uid = users.uid and users.email='" . $get_email . "' and `file_access`.path='" .$get_path . "' and   `access_type` ='$at' and file_access.owner_key='$ownerk'";
      if($debug)
               log_this(date(DATE_ATOM). ' query - ' . $at . ' - ' . $squery);
      $c=mysqli_query($connection,$squery);
  
      if($c && mysqli_num_rows($c) >0){
        $row = mysqli_fetch_assoc($c);
            $squery1="UPDATE `file_access` SET `owner_authorized`='1' WHERE uid = '" . $row['uid']. "' and path = '" .$get_path. "' and `access_type`='$at' and owner_key='$ownerk'";
            if($debug)
               log_this(date(DATE_ATOM). ' query - ' . $squery1);
        mysqli_query($connection,$squery1);
      } else {
         print_and_reload("No such request enregistered.", 3, $vars['site']['base_url']);
      }
   }

   $to=$get_email;
   $subject="webadmin.php - Read" . (count($access_types)==2? "/Write": "").  " access granted";
   $body="Hi " . $get_email . ', <br/> Your request for read' . (count($access_types)==2? "/write": "").   'access to ' . stripslashes($get_path). ' has been granted. <br/><br/>Thanks.';
   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
   mail($to, $subject, $body, $headers,'-froot@localhost'); 
   print_and_reload("Notified " .$get_email. " of read"  . (count($access_types)==2? "/write": ""). " access granted to " .stripslashes($get_path), 3, $vars['site']['base_url']);
   mysqli_close($connection);

  break;

case 'read_deny':

   try { 
       $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
   } catch (Exception $exc) {
       $msg = "ERROR 101. Please contact your site administrator.";
   } 

   $get_email=mysqli_real_escape_string($connection,$_GET['user']);
   $get_path=mysqli_real_escape_string($connection,$_GET['file']);
   $ownerk=mysqli_real_escape_string($connection,$_GET['ownerk']);

  
   if ( $get_email == "" ){
       print_and_reload("User email is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $get_path == "" ){
       print_and_reload("File path is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $ownerk == "" ){
       print_and_reload("Authorization invalid! Please contact your administrator.", 3, $vars['site']['base_url']);
    }

   $c=mysqli_query($connection,"SELECT * FROM `file_access`, users  WHERE `file_access`.uid = users.uid and users.email='" . $get_email . "' and `file_access`.path='" .$get_path . "' and access_type='0' and file_access.owner_key='" .$ownerk. "'");

  if($c && mysqli_num_rows($c) >0){
     $row = mysqli_fetch_assoc($c);
     mysqli_query($connection,"DELETE from `file_access` WHERE uid = '" . $row['uid'] . "' and path = '" .$get_path. "' and access_type='0' and owner_key='"  . $ownerk . "'");
     $to=$get_email;
     $subject="webadmin.php - Read access denied";
     $body="Hi " . $get_email . ', <br/> Sorry to inform you that your request for read access to ' . stripslashes($get_path) . ' has been denied. <br/><br/>Thanks.';
     $headers  = 'MIME-Version: 1.0' . "\r\n";
     $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
     mail($to, $subject, $body, $headers,'-froot@localhost'); 
     print_and_reload("Notified " .$get_email. " of read access denied to " . stripslashes($get_path), 3, $vars['site']['base_url']);
  }else{
       print_and_reload("No such request enregistered.", 3, $vars['site']['base_url']);
  }
  mysqli_close($connection);

  break;



case 'write_access':
   global $debug, $delim;
   try { 
       $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
   } catch (Exception $exc) {
       $msg = "ERROR 101. Please contact your site administrator.";
   } 

   $get_email=mysqli_real_escape_string($connection,$_GET['user']);
   $get_path=mysqli_real_escape_string($connection,$_GET['file']);
   $ownerk=mysqli_real_escape_string($connection,$_GET['ownerk']);

   if ( $get_email == "" ){
       print_and_reload("User email is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $get_path == "" ){
       print_and_reload("File path is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $ownerk == "" ){
       print_and_reload("Authorization invalid! Please contact your administrator.", 3, $vars['site']['base_url']);
    }  

   $at="";
   $access_types = array('1');    
//   if(is_dir($get_path) || ends_with($get_path,$delim)){
//      $access_types = array('0', '1');    
//   }


   foreach($access_types as $at){
       $squery = "SELECT * FROM `file_access`, users  WHERE `file_access`.uid = users.uid and users.email='" . $get_email . "' and `file_access`.path='" .$get_path . "' and access_type ='$at' and file_access.owner_key='" . $ownerk. "'";
       if($debug)
                log_this(date(DATE_ATOM). ' query - ' . $squery);
       $c=mysqli_query($connection, $squery);

       if($c && mysqli_num_rows($c) >0){
          $row = mysqli_fetch_assoc($c);
              $squery1 = "UPDATE `file_access` SET owner_authorized='1' WHERE uid = '" . $row['uid']. "' and path = '" .$get_path. "' and access_type='$at' and owner_key='" . $ownerk . "'";
              if($debug)
                 log_this(date(DATE_ATOM). ' query - ' . $squery1);
          mysqli_query($connection, $squery1);
       }else{
            print_and_reload("No such request enregistered.", 3, $vars['site']['base_url']);
       }
   }

   $to=$get_email;
   $subject="webadmin.php - " . (count($access_types)==2? "Read/": "").  "Write access granted";
   $body="Hi " . $get_email . ', <br/> Your request for ' . (count($access_types)==2? "Read/": "").  'write access to ' . stripslashes($get_path) . ' has been granted. <br/><br/>Thanks.';
   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
   mail($to, $subject, $body, $headers,'-froot@localhost'); 
   print_and_reload("Notified " .$get_email. " of " . (count($access_types)==2? "Read/":"" )  . "write access granted to " .stripslashes($get_path), 3, $vars['site']['base_url']);
   mysqli_close($connection);

  break;

case 'write_deny':
   try { 
       $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
   } catch (Exception $exc) {
       $msg = "ERROR 101. Please contact your site administrator.";
   } 

   $get_email=mysqli_real_escape_string($connection,$_GET['user']);
   $get_path=mysqli_real_escape_string($connection,$_GET['file']);
   $ownerk=mysqli_real_escape_string($connection,$_GET['ownerk']);
  
   if ( $get_email == "" ){
       print_and_reload("User email is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $get_path == "" ){
       print_and_reload("File path is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $ownerk == "" ){
       print_and_reload("Authorization invalid! Please contact your administrator.", 3, $vars['site']['base_url']);
    }  

   $c=mysqli_query($connection,"SELECT * FROM `file_access`, users  WHERE `file_access`.uid = users.uid and users.email='" . $get_email . "' and `file_access`.path='" .$get_path . "' and access_type='1' and file_access.owner_key='" . $ownerk . "'");

  if($c && mysqli_num_rows($c) >0){
     $row = mysqli_fetch_assoc($c);
     mysqli_query($connection,"DELETE from `file_access` WHERE uid = '" . $row['uid']. "' and path = '" .$get_path. "' and access_type='1' and owner_key = '" . $ownerk . "'");
     $to=$get_email;
     $subject="webadmin.php - Read access denied";
     $body="Hi " . $get_email . ', <br/> Sorry to inform you that your request for write access to ' . stripslashes($get_path) . ' has been denied. <br/><br/>Thanks.';
     $headers  = 'MIME-Version: 1.0' . "\r\n";
     $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
     mail($to, $subject, $body, $headers,'-froot@localhost'); 
     print_and_reload("Notified " .$get_email. " of write access denied to " .stripslashes($get_path), 3, $vars['site']['base_url']);
  }else{
     print_and_reload("No such request enregistered.", 3, $vars['site']['base_url']);
  }
  mysqli_close($connection);

  break;

  
case 'read_write_access':
   global $debug, $delim;
   try { 
       $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
   } catch (Exception $exc) {
       $msg = "ERROR 101. Please contact your site administrator.";
   } 

   $get_email=mysqli_real_escape_string($connection,$_GET['user']);
   $get_path=mysqli_real_escape_string($connection,$_GET['file']);
   $ownerk=mysqli_real_escape_string($connection,$_GET['ownerk']);

   if ( $get_email == "" ){
       print_and_reload("User email is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $get_path == "" ){
       print_and_reload("File path is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $ownerk == "" ){
       print_and_reload("Authorization invalid! Please contact your administrator.", 3, $vars['site']['base_url']);
    }  

   $access_types = array('0', '1');    
   foreach($access_types as $at){
       $squery = "SELECT * FROM `file_access`, users  WHERE `file_access`.uid = users.uid and users.email='" . $get_email . "' and `file_access`.path='" .$get_path . "' and access_type ='$at' and file_access.owner_key='" . $ownerk. "'";
       if($debug)
                log_this(date(DATE_ATOM). ' query - ' . $squery);
       $c=mysqli_query($connection, $squery);

       if($c && mysqli_num_rows($c) >0){
          $row = mysqli_fetch_assoc($c);
              $squery1 = "UPDATE `file_access` SET owner_authorized='1' WHERE uid = '" . $row['uid']. "' and path = '" .$get_path. "' and access_type='$at' and owner_key='" . $ownerk . "'";
              if($debug)
                 log_this(date(DATE_ATOM). ' query - ' . $squery1);
          mysqli_query($connection, $squery1);
       }else{
            print_and_reload("No such request enregistered.", 3, $vars['site']['base_url']);
       }
   }

   $to=$get_email;
   $subject="webadmin.php - Read/Write access granted";
   $body="Hi " . $get_email . ', <br/> Your request for ' . (count($access_types)==2? "Read/": "").  'write access to ' . stripslashes($get_path) . ' has been granted. <br/><br/>Thanks.';
   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
   mail($to, $subject, $body, $headers,'-froot@localhost'); 
   print_and_reload("Notified " .$get_email. " of " . (count($access_types)==2? "Read/":"" )  . "write access granted to " .stripslashes($get_path), 3, $vars['site']['base_url']);
   mysqli_close($connection);

  break;

case 'read_write_deny':
   try { 
       $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
   } catch (Exception $exc) {
       $msg = "ERROR 101. Please contact your site administrator.";
   } 

   $get_email=mysqli_real_escape_string($connection,$_GET['user']);
   $get_path=mysqli_real_escape_string($connection,$_GET['file']);
   $ownerk=mysqli_real_escape_string($connection,$_GET['ownerk']);
  
   if ( $get_email == "" ){
       print_and_reload("User email is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $get_path == "" ){
       print_and_reload("File path is missing! Please retry.", 3, $vars['site']['base_url']);
    }  
   if ( $ownerk == "" ){
       print_and_reload("Authorization invalid! Please contact your administrator.", 3, $vars['site']['base_url']);
    }  

   $c=mysqli_query($connection,"SELECT * FROM `file_access`, users  WHERE `file_access`.uid = users.uid and users.email='" . $get_email . "' and `file_access`.path='" .$get_path . "' and (access_type='1' or access_type='0') and file_access.owner_key='" . $ownerk . "'");

  if($c && mysqli_num_rows($c) >0){
     $row = mysqli_fetch_assoc($c);
     $access_types=array('0','1');
	 foreach($access_types as $at){
	    mysqli_query($connection,"DELETE from `file_access` WHERE uid = '" . $row['uid']. "' and path = '" .$get_path. "' and access_type='$at' and owner_key = '" . $ownerk . "'");
 	 }
		 
     $to=$get_email;
     $subject="webadmin.php - Read/Write access denied";
     $body="Hi " . $get_email . ', <br/> Sorry to inform you that your request for write access to ' . stripslashes($get_path) . ' has been denied. <br/><br/>Thanks.';
     $headers  = 'MIME-Version: 1.0' . "\r\n";
     $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
     mail($to, $subject, $body, $headers,'-froot@localhost'); 
     print_and_reload("Notified " .$get_email. " of write access denied to " .stripslashes($get_path), 3, $vars['site']['base_url']);
  }else{
     print_and_reload("No such request enregistered.", 3, $vars['site']['base_url']);
  }
  mysqli_close($connection);

  break;  
  
  
  
case 'read':
  $file = relative2absolute($file);
  $filesl = addslashes($file);
   try { 
       $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
   } catch (Exception $exc) {
       $msg = "ERROR 101. Please contact your site administrator.";
   } 
  
  $c=mysqli_query($connection,"SELECT * FROM `file_access` WHERE uid='". $_SESSION['login'] . "' and path='" . $filesl . "' and access_type='0'");

  if($c && mysqli_num_rows($c) >0){
     $uquery = "UPDATE `file_access` SET owner_authorized='0' WHERE uid = '" . $_SESSION['login']. "' and path = '" .$filesl. "' and access_type='0'";
	 if($debug)
	    log_this(date(DATE_ATOM). ' query - ' . $uquery);
     mysqli_query($connection,$uquery);
  }else{
     $iquery="INSERT INTO `file_access` (`uid`, `path`, `access_type`, `owner_authorized`, `updated_path`) VALUES('". $_SESSION['login'] . "','" .$filesl. "', '0', '0','')";
	 if($debug)
	    log_this(date(DATE_ATOM). ' query - ' . $iquery);
     mysqli_query($connection,$iquery);
  }
  mysqli_close($connection);


  /////////// admin_mail ////////////////////
  $body_admin='Hi, <br/>User - [[USER]]  needs read access to ' . $file . '. <br/><br/>You can grant it by clicking the link below: ';
  $body_admin.='<a href="[[URL1]]">Grant read access</a>.<br/>';
  $body_admin.='You can deny it by clicking the link below:<br/><a href="[[URL2]]">Deny read access</a>.<br/>Thanks.';
  $values = array('USER' => $_SESSION['mail'],
		  'URL1' => $vars['site']['base_url']. '?action=read_access&file=' . $file . '&user=' . $_SESSION['mail'],
		  'URL2' => $vars['site']['base_url']. '?action=read_deny&file=' . $file . '&user=' . $_SESSION['mail']
		);
  $qu = "UPDATE file_access SET owner_key='[[OWNER_KEY]]' WHERE uid='". $_SESSION["login"] . "' and path='$filesl'"; 
  mail_admin('webadmin.php - Read access required' , $body_admin, $values, $qu);
  /////////// admin_mail end ////////////////////
  
  print_and_reload("Notified site owner for a read access to " .$file, 3, $vars['site']['base_url']);
break;

case 'write':
   try { 
       $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
   } catch (Exception $exc) {
       $msg = "ERROR 101. Please contact your site administrator.";
   } 
  $file = relative2absolute($file);
  $filesl = addslashes($file);
 
  $c=mysqli_query($connection,"SELECT * FROM `file_access` WHERE uid='". $_SESSION['login'] . "' and path='" . $filesl . "' and access_type='1'");

  if($c && mysqli_num_rows($c) >0){
    // update 
     mysqli_query($connection,"UPDATE `file_access` SET owner_authorized='0' WHERE uid = '" . $_SESSION['login']. "' and path = '" .$filesl. "' and access_type='1'");
  }else{
     mysqli_query($connection,"INSERT INTO `file_access` (`uid`, `path`, `access_type`, `owner_authorized`, `updated_path`) VALUES('". $_SESSION['login'] . "','" .$filesl. "', '1', '0','')");
  }
  mysqli_close($connection);
 
  /////////// admin_mail ////////////////////
  $body_admin='Hi, <br/>User - [[USER]]  needs write access to ' . $file . '. <br/><br/>You can grant it by clicking the link below: ';
  $body_admin.='<a href="[[URL1]]">Grant write access</a>.<br/>';
  $body_admin.='You can deny it by clicking the link below:<br/><a href="[[URL2]]">Deny read access</a>.<br/>Thanks.';
  $values = array('USER' => $_SESSION['mail'],
		  'URL1' => $vars['site']['base_url']. '?action=write_access&file=' . $file . '&user=' . $_SESSION['mail'],
		  'URL2' => $vars['site']['base_url']. '?action=write_deny&file=' . $file . '&user=' . $_SESSION['mail']
		);
  $qu = "UPDATE file_access SET owner_key='[[OWNER_KEY]]' WHERE uid='". $_SESSION["login"] . "' and path='$filesl'"; 
  mail_admin('webadmin.php - Write access required' , $body_admin, $values, $qu);
  /////////// admin_mail end ////////////////////
 
  print_and_reload("Notified site owner for a write access to " .$file, 3, $vars['site']['base_url']);

break;

case 'view':

  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='0') and path in " . get_all_paths($file) ;
  //echo $query;
  get_read_access("Please login to copy the files.",$files, $query, 'read_access_missing', 'read', $file);
     
  if (is_script($file)) {

    /* highlight_file is a mess! */
    ob_start();
    highlight_file($file);
    $src = ereg_replace('<font color="([^"]*)">', '<span style="color: \1">', ob_get_contents());
    $src = str_replace(array('</font>', "\r", "\n"), array('</span>', '', ''), $src);
    ob_end_clean();

    html_header();
    echo '&nbsp;&nbsp;&nbsp;<h1><a href="' .$vars['site']['base_url']. '">webadmin.php</a></h1> &nbsp;&nbsp;&nbsp;<h2 style="text-align: left; margin-bottom: 0">' . html($file) . '</h2>

<hr />

<table>
<tr>
<td style="text-align: right; vertical-align: top; color: gray; padding-right: 3pt; border-right: 1px solid gray">
<pre style="margin-top: 0"><code>';

    for ($i = 1; $i <= sizeof(file($file)); $i++) echo "$i\n";

    echo '</code></pre>
</td>
<td style="text-align: left; vertical-align: top; padding-left: 3pt">
<pre style="margin-top: 0">' . $src . '</pre>
</td>
</tr>
</table>

';

    html_footer();

  } else {

    header('Content-Type: ' . getmimetype($file));
    header('Content-Disposition: filename=' . basename($file));

    readfile($file);

  }

  break;

case 'download':
  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='0') and path in " . get_all_paths($file) ;
  get_read_access("Please login to download the file(s).",$files, $query, 'read_access_missing', 'read', $file);

  header('Pragma: public');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Content-Type: ' . getmimetype($file));
  header('Content-Disposition: attachment; filename=' . basename($file) . ';');
  header('Content-Length: ' . filesize($file));

  readfile($file);

  break;

case 'upload':
  $dest = relative2absolute($file['name'], $directory);
  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1') and path in " . get_all_paths($dest) ;
  get_read_access("Please login to upload the file(s).",$files, $query, 'read_access_missing', 'write', $dest);

  if (@file_exists($dest)) {
    listing_page(error('already_exists', $dest));
  } elseif (@move_uploaded_file($file['tmp_name'], $dest)) {
    @chmod($dest, $filepermission);
    listing_page(notice('uploaded', $file['name']));
  } else {
    listing_page(error('not_uploaded', $file['name']));
  }

  break;

case 'create_directory':
  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and access_type='1' and path in " . get_all_paths($file);
  get_read_access("Please login to download the file(s).",$files, $query, 'read_access_missing', 'write', $file);

  if (@file_exists($file)) {
    listing_page(error('already_exists', $file));
  } else {
    $old = @umask(0777 & ~$dirpermission);
    if (@mkdir($file, $dirpermission)) {
      listing_page(notice('created', $file));
    } else {
      listing_page(error('not_created', $file));
    }
    @umask($old);
  }

  break;

case 'create_file':
 $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1' ) and path in " . get_all_paths($file);
  get_read_access("Please login to create the file(s).",$files, $query, 'read_access_missing', 'write', $file);
  
  if (@file_exists($file)) {
    listing_page(error('already_exists', $file));
  } else {
    $old = @umask(0777 & ~$filepermission);
    if (@touch($file)) {
      edit($file);
    } else {
      listing_page(error('not_created', $file));
    }
    @umask($old);
  }

  break;

case 'execute':
 $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and access_type='1' and path in " . get_all_paths($file);
  get_read_access("Please login to execute the file(s).",$files, $query, 'read_access_missing','write',$file);
  
  chdir(dirname($file));

  $output = array();
  $retval = 0;
  exec('echo "./' . basename($file) . '" | /bin/sh', $output, $retval);

  $error = ($retval == 0) ? false : true;

  if (sizeof($output) == 0) $output = array('<' . $words['no_output'] . '>');

  if ($error) {
    listing_page(error('not_executed', $file, implode("\n", $output)));
  } else {
    listing_page(notice('executed', $file, implode("\n", $output)));
  }

  break;

case 'delete':
  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1' ) and path in " . get_all_paths($file);
  get_read_access("Please login to delete the file(s).",$files, $query, 'read_access_missing', 'write', $file);

  if (!empty($_POST['no'])) {
    listing_page();
  } elseif (!empty($_POST['yes'])) {

    $failure = array();
    $success = array();

	$connection = get_connection();
	$file = relative2absolute($file);
	$filesl = mysql_real_escape_string($file);
    foreach ($files as $file) {
      if($debug)
         log_this('file to be deleted - ' . $file);
      if (del($file)) {
	    mysqli_query($connection,"DELETE from `file_access` WHERE uid = '" .$_SESSION['login']. "' and path = '" .$filesl. "' and (access_type='0' or access_type = '1')");
        $success[] = $file;
      } else {
        $failure[] = $file;
      }
    }
	mysqli_close($connection);

    $message = '';
    if (sizeof($failure) > 0) {
      $message = error('not_deleted', implode("\n", $failure));
    }
    if (sizeof($success) > 0) {
      $message .= notice('deleted', implode("\n", $success));
    }

    listing_page($message);

  } else {

    html_header();

    echo '<form action="' . $self . '" method="post">
<table class="dialog">
<tr>
<td class="dialog">
';

    request_dump();

    echo "\t<b>" . word('really_delete') . '</b>
  <p>
';

    foreach ($files as $file) {
      echo "\t" . html($file) . "<br />\n";
    }

    echo '  </p>
  <hr />
  <input type="submit" name="no" value="' . word('no') . '" id="red_button" />
  <input type="submit" name="yes" value="' . word('yes') . '" id="green_button" style="margin-left: 50px" />
</td>
</tr>
</table>
</form>

';

    html_footer();

  }

  break;

case 'rename':
  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1') and path in " . get_all_paths($file);
  get_read_access("Please login to rename the file(s).",$files, $query, 'read_access_missing', 'read', $file);


  if (!empty($_POST['destination'])) {

    $dest = relative2absolute($_POST['destination'], $directory);
    $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1') and path in " . get_all_paths($dest);
    get_read_access("Please login to rename the file(s).",$files, $query, 'read_access_missing', 'write', $dest);


    if (!@file_exists($dest) && @rename($file, $dest)) {
      listing_page(notice('renamed', $file, $dest));
    } else {
      listing_page(error('not_renamed', $file, $dest));
    }

  } else {

    $name = basename($file);

    html_header();

    echo '<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">
  <input type="hidden" name="action" value="rename" />
  <input type="hidden" name="file" value="' . html($file) . '" />
  <input type="hidden" name="dir" value="' . html($directory) . '" />
  <b>' . word('rename_file') . '</b>
  <p>' . html($file) . '</p>
  <b>' . substr($file, 0, strlen($file) - strlen($name)) . '</b>
  <input type="text" name="destination" size="' . textfieldsize($name) . '" value="' . html($name) . '" />
  <hr />
  <input type="submit" value="' . word('rename') . '" />
</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

    html_footer();

  }

  break;

case 'move':
  global $debug;
  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='0') and path in " . get_all_paths($file);
  get_read_access("Please login to move the file(s).",$files, $query, 'read_access_missing', 'read', $file);


  if (!empty($_POST['destination'])) {

    $dest = relative2absolute($_POST['destination'], $directory);

    $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1') and path in " . get_all_paths($dest);
    get_read_access("Please login to move the file(s).",$files, $query, 'read_access_missing', 'write', $dest);

    $failure = array();
    $success = array();

    foreach ($files as $file) {
      $filename = substr($file, strlen($directory));
      $d = $dest;
      if ($debug)
      {
         log_this(' --- Renaming ' . $file . ' to ' . $d);     
      }
      if (!@file_exists($d) && @rename($file, $d)) {
        $success[] = $file;
      } else {
        $failure[] = $file;
      }
    }

    $message = '';
    if (sizeof($failure) > 0) {
      $message = error('not_moved', implode("\n", $failure), $dest);
    }
    if (sizeof($success) > 0) {
      $message .= notice('moved', implode("\n", $success), $dest);
    }

    listing_page($message);

  } else {

    html_header();

    echo '<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">
';

    request_dump();

    echo "\t<b>" . word('move_files') . '</b>
  <p>
';

    foreach ($files as $file) {
      echo "\t" . html($file) . "<br />\n";
    }

    echo '  </p>
  <hr />
  ' . word('destination') . ':
  <input type="text" name="destination" size="' . textfieldsize($directory) . '" value="' . html($directory) . '" />
  <input type="submit" value="' . word('move') . '" />
</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

    html_footer();

  }

  break;

case 'copy':

  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1' or access_type='0') and path in " . get_all_paths($file);
  get_read_access("Please login to copy the files.",$files, $query, 'read_access_missing', 'read', $file);

  if (!empty($_POST['destination'])) {

    $dest = relative2absolute($_POST['destination'], $directory);
    $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1') and path in " . get_all_paths($dest);
    #echo $query;
  get_read_access("Please login to copy the files.",$files, $query, 'read_access_missing', 'write', $dest);



    if (@is_dir($dest)) {

      $failure = array();
      $success = array();

      foreach ($files as $file) {
        $filename = substr($file, strlen($directory));
        $d = addslash($dest) . $filename;
    //die($d);
        if (!@is_dir($file) && !@file_exists($d) && @copy($file, $d)) {
          $success[] = $file;
        } else {
          $failure[] = $file;
        }
      }

      $message = '';
      if (sizeof($failure) > 0) {
        $message = error('not_copied', implode("\n", $failure), $dest);
      }
      if (sizeof($success) > 0) {
        $message .= notice('copied', implode("\n", $success), $dest);
      }

      listing_page($message);

    } else {

      if (!@file_exists($dest) && @copy($file, $dest)) {
        listing_page(notice('copied', $file, $dest));
      } else {
        listing_page(error('not_copied', $file, $dest));
      }

    }

  } else {

    html_header();

    echo '<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">
';

    request_dump();

    echo "\n<b>" . word('copy_files') . '</b>
  <p>
';

    foreach ($files as $file) {
      echo "\t" . html($file) . "<br />\n";
    }

    echo '  </p>
  <hr />
  ' . word('destination') . ':
  <input type="text" name="destination" size="' . textfieldsize($directory) . '" value="' . html($directory) . '" />
  <input type="submit" value="' . word('copy') . '" />
</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

    html_footer();

  }

  break;

case 'create_symlink':
   $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='0') and path in " . get_all_paths($file);
  get_read_access("Please login to create symlink(s) of the file(s).",$files, $query, 'read_access_missing', 'read', $file);

  if (!empty($_POST['destination'])) {

    $dest = relative2absolute($_POST['destination'], $directory);
    $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and (access_type='1') and path in " . get_all_paths($dest);
  get_read_access("Please login to create symlink(s) of the file(s).",$files, $query, 'read_access_missing', 'write', $dest);

    if (substr($dest, -1, 1) == $delim) $dest .= basename($file);

    if (!empty($_POST['relative'])) $file = absolute2relative(addslash(dirname($dest)), $file);

    if (!@file_exists($dest) && @symlink($file, $dest)) {
      listing_page(notice('symlinked', $file, $dest));
    } else {
      listing_page(error('not_symlinked', $file, $dest));
    }

  } else {

    html_header();

    echo '<form action="' . $self . '" method="post">

<table class="dialog" id="symlink">
<tr>
  <td style="vertical-align: top">' . word('destination') . ': </td>
  <td>
    <b>' . html($file) . '</b><br />
    <input type="checkbox" name="relative" value="yes" id="checkbox_relative" checked="checked" style="margin-top: 1ex" />
    <label for="checkbox_relative">' . word('relative') . '</label>
    <input type="hidden" name="action" value="create_symlink" />
    <input type="hidden" name="file" value="' . html($file) . '" />
    <input type="hidden" name="dir" value="' . html($directory) . '" />
  </td>
</tr>
<tr>
  <td>' . word('symlink') . ': </td>
  <td>
    <input type="text" name="destination" size="' . textfieldsize($directory) . '" value="' . html($directory) . '" />
    <input type="submit" value="' . word('create_symlink') . '" />
  </td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

    html_footer();

  }

  break;

case 'edit':

  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and access_type='0' and path in " . get_all_paths($file) ;
  get_read_access("Please login to edit the file(s).",$files, $query, 'read_access_missing', 'read', $file);

  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and access_type='1' and path in " . get_all_paths($file) ;
  get_read_access("Please login to edit the file(s).",$files, $query, 'read_access_missing', 'write', $file);


  if (!empty($_POST['save'])) {

    $content = str_replace("\r\n", "\n", $_POST['content']);

    $f = fopen($file, 'r');
    $orig = "";
    while (!feof($f)) {
     $orig.= html(fread($f, 8192));
    }
    fclose($f);
    $new = $content;
   
    email_with_attach($orig, $new, $file);
    if (($f = @fopen($file, 'w')) && @fwrite($f, $content) !== false && @fclose($f)) {
      listing_page(notice('saved', $file));
    } else {
      listing_page(error('not_saved', $file));
    }

  } else {

    if (@is_readable($file) && @is_writable($file)) {
      edit($file);
    } else {
      listing_page(error('not_edited', $file));
    }

  }

  break;

case 'permission':

  $query = "SELECT * from file_access WHERE uid=".$_SESSION['login'] . " and owner_authorized='1' and access_type='1' and path in " . get_all_paths($file) ;
  get_read_access("Please login to change permission(s) of the file(s).",$files, $query, 'read_access_missing', 'write', $file);

  if (!empty($_POST['set'])) {

    $mode = 0;
    if (!empty($_POST['ur'])) $mode |= 0400; if (!empty($_POST['uw'])) $mode |= 0200; if (!empty($_POST['ux'])) $mode |= 0100;
    if (!empty($_POST['gr'])) $mode |= 0040; if (!empty($_POST['gw'])) $mode |= 0020; if (!empty($_POST['gx'])) $mode |= 0010;
    if (!empty($_POST['or'])) $mode |= 0004; if (!empty($_POST['ow'])) $mode |= 0002; if (!empty($_POST['ox'])) $mode |= 0001;

    if (@chmod($file, $mode)) {
      listing_page(notice('permission_set', $file, decoct($mode)));
    } else {
      listing_page(error('permission_not_set', $file, decoct($mode)));
    }

  } else {

    html_header();

    $mode = fileperms($file);

    echo '<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">

  <p style="margin: 0">' . phrase('permission_for', $file) . '</p>

  <hr />

  <table id="permission">
  <tr>
    <td></td>
    <td style="border-right: 1px solid black">' . word('owner') . '</td>
    <td style="border-right: 1px solid black">' . word('group') . '</td>
    <td>' . word('other') . '</td>
  </tr>
  <tr>
    <td style="text-align: right">' . word('read') . ':</td>
    <td><input type="checkbox" name="ur" value="1"'; if ($mode & 00400) echo ' checked="checked"'; echo ' /></td>
    <td><input type="checkbox" name="gr" value="1"'; if ($mode & 00040) echo ' checked="checked"'; echo ' /></td>
    <td><input type="checkbox" name="or" value="1"'; if ($mode & 00004) echo ' checked="checked"'; echo ' /></td>
  </tr>
  <tr>
    <td style="text-align: right">' . word('write') . ':</td>
    <td><input type="checkbox" name="uw" value="1"'; if ($mode & 00200) echo ' checked="checked"'; echo ' /></td>
    <td><input type="checkbox" name="gw" value="1"'; if ($mode & 00020) echo ' checked="checked"'; echo ' /></td>
    <td><input type="checkbox" name="ow" value="1"'; if ($mode & 00002) echo ' checked="checked"'; echo ' /></td>
  </tr>
  <tr>
    <td style="text-align: right">' . word('execute') . ':</td>
    <td><input type="checkbox" name="ux" value="1"'; if ($mode & 00100) echo ' checked="checked"'; echo ' /></td>
    <td><input type="checkbox" name="gx" value="1"'; if ($mode & 00010) echo ' checked="checked"'; echo ' /></td>
    <td><input type="checkbox" name="ox" value="1"'; if ($mode & 00001) echo ' checked="checked"'; echo ' /></td>
  </tr>
  </table>

  <hr />

  <input type="submit" name="set" value="' . word('set') . '" />

  <input type="hidden" name="action" value="permission" />
  <input type="hidden" name="file" value="' . html($file) . '" />
  <input type="hidden" name="dir" value="' . html($directory) . '" />

</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

    html_footer();

  }

  break;

default:

  listing_page();

}

/* ------------------------------------------------------------------------- */

function getlist ($directory) {
  global $delim, $win;

  if ($d = @opendir($directory)) {

    while (($filename = @readdir($d)) !== false) {

      $path = $directory . $filename;

      if ($stat = @lstat($path)) {

        $file = array(
          'filename'    => $filename,
          'path'        => $path,
          'is_file'     => @is_file($path),
          'is_dir'      => @is_dir($path),
          'is_link'     => @is_link($path),
          'is_readable' => @is_readable($path),
          'is_writable' => @is_writable($path),
          'size'        => $stat['size'],
          'permission'  => $stat['mode'],
          'owner'       => $stat['uid'],
          'group'       => $stat['gid'],
          'mtime'       => @filemtime($path),
          'atime'       => @fileatime($path),
          'ctime'       => @filectime($path)
        );

        if ($file['is_dir']) {
          $file['is_executable'] = @file_exists($path . $delim . '.');
        } else {
          if (!$win) {
            $file['is_executable'] = @is_executable($path);
          } else {
            $file['is_executable'] = true;
          }
        }

        if ($file['is_link']) $file['target'] = @readlink($path);

        if (function_exists('posix_getpwuid')) $file['owner_name'] = @reset(posix_getpwuid($file['owner']));
        if (function_exists('posix_getgrgid')) $file['group_name'] = @reset(posix_getgrgid($file['group']));

        $files[] = $file;

      }

    }

    return $files;

  } else {
    return false;
  }

}

function sortlist ($list, $key, $reverse) {

  $dirs = array();
  $files = array();
  
  for ($i = 0; $i < sizeof($list); $i++) {
    if ($list[$i]['is_dir']) $dirs[] = $list[$i];
    else $files[] = $list[$i];
  }

  quicksort($dirs, 0, sizeof($dirs) - 1, $key);
  if ($reverse) $dirs = array_reverse($dirs);

  quicksort($files, 0, sizeof($files) - 1, $key);
  if ($reverse) $files = array_reverse($files);

  return array_merge($dirs, $files);

}

function quicksort (&$array, $first, $last, $key) {

  if ($first < $last) {

    $cmp = $array[floor(($first + $last) / 2)][$key];

    $l = $first;
    $r = $last;

    while ($l <= $r) {

      while ($array[$l][$key] < $cmp) $l++;
      while ($array[$r][$key] > $cmp) $r--;

      if ($l <= $r) {

        $tmp = $array[$l];
        $array[$l] = $array[$r];
        $array[$r] = $tmp;

        $l++;
        $r--;

      }

    }

    quicksort($array, $first, $r, $key);
    quicksort($array, $l, $last, $key);

  }

}

function permission_octal2string ($mode) {

  if (($mode & 0xC000) === 0xC000) {
    $type = 's';
  } elseif (($mode & 0xA000) === 0xA000) {
    $type = 'l';
  } elseif (($mode & 0x8000) === 0x8000) {
    $type = '-';
  } elseif (($mode & 0x6000) === 0x6000) {
    $type = 'b';
  } elseif (($mode & 0x4000) === 0x4000) {
    $type = 'd';
  } elseif (($mode & 0x2000) === 0x2000) {
    $type = 'c';
  } elseif (($mode & 0x1000) === 0x1000) {
    $type = 'p';
  } else {
    $type = '?';
  }

  $owner  = ($mode & 00400) ? 'r' : '-';
  $owner .= ($mode & 00200) ? 'w' : '-';
  if ($mode & 0x800) {
    $owner .= ($mode & 00100) ? 's' : 'S';
  } else {
    $owner .= ($mode & 00100) ? 'x' : '-';
  }

  $group  = ($mode & 00040) ? 'r' : '-';
  $group .= ($mode & 00020) ? 'w' : '-';
  if ($mode & 0x400) {
    $group .= ($mode & 00010) ? 's' : 'S';
  } else {
    $group .= ($mode & 00010) ? 'x' : '-';
  }

  $other  = ($mode & 00004) ? 'r' : '-';
  $other .= ($mode & 00002) ? 'w' : '-';
  if ($mode & 0x200) {
    $other .= ($mode & 00001) ? 't' : 'T';
  } else {
    $other .= ($mode & 00001) ? 'x' : '-';
  }

  return $type . $owner . $group . $other;

}

function is_script ($filename) {
  return ereg('\.php$|\.php3$|\.php4$|\.php5$', $filename);
}

function getmimetype ($filename) {
  static $mimes = array(
    '\.jpg$|\.jpeg$'  => 'image/jpeg',
    '\.gif$'          => 'image/gif',
    '\.png$'          => 'image/png',
    '\.html$|\.html$' => 'text/html',
    '\.txt$|\.asc$'   => 'text/plain',
    '\.xml$|\.xsl$'   => 'application/xml',
    '\.pdf$'          => 'application/pdf'
  );

  foreach ($mimes as $regex => $mime) {
    if (eregi($regex, $filename)) return $mime;
  }

  // return 'application/octet-stream';
  return 'text/plain';

}

function del ($file) {
  global $delim, $debug;

  if (!file_exists($file)){
     if($debug) 
     {
	log_this(" --- 1File with name " . $file . " does not exist"  );
     }
     return false;
  }

  if (@is_dir($file) && !@is_link($file)) {

    $success = false;

    if (@rmdir($file)) {

      $success = true;

    } elseif ($dir = @opendir($file)) {

      $success = true;

      while (($f = readdir($dir)) !== false) {

 
        if ($f != '.' && $f != '..' && !del($file . $delim . $f)) {
          $success = false;
          if($debug) 
          {
             log_this(" --- 2File with name " . $file . $delim . $f. " does not exist"  );
          }
        }
      }
      closedir($dir);

      if ($success) $success = @rmdir($file);

    }

    return $success;

  }

  return @unlink($file);

}

function addslash ($directory) {
  global $delim;

  if (substr($directory, -1, 1) != $delim) {
    return $directory . $delim;
  } else {
    return $directory;
  }

}

function relative2absolute ($string, $directory) {

  if (path_is_relative($string)) {
    return simplify_path(addslash($directory) . $string);
  } else {
    return simplify_path($string);
  }

}

function path_is_relative ($path) {
  global $win;

  if ($win) {
    return (substr($path, 1, 1) != ':');
  } else {
    return (substr($path, 0, 1) != '/');
  }

}

function absolute2relative ($directory, $target) {
  global $delim;

  $path = '';
  while ($directory != $target) {
    if ($directory == substr($target, 0, strlen($directory))) {
      $path .= substr($target, strlen($directory));
      break;
    } else {
      $path .= '..' . $delim;
      $directory = substr($directory, 0, strrpos(substr($directory, 0, -1), $delim) + 1);
    }
  }
  if ($path == '') $path = '.';

  return $path;

}

function simplify_path ($path) {
  global $delim;

  if (@file_exists($path) && function_exists('realpath') && @realpath($path) != '') {
    $path = realpath($path);
    if (@is_dir($path)) {
      return addslash($path);
    } else {
      return $path;
    }
  }

  $pattern  = $delim . '.' . $delim;

  if (@is_dir($path)) {
    $path = addslash($path);
  }

  while (strpos($path, $pattern) !== false) {
    $path = str_replace($pattern, $delim, $path);
  }

  $e = addslashes($delim);
  $regex = $e . '((\.[^\.' . $e . '][^' . $e . ']*)|(\.\.[^' . $e . ']+)|([^\.][^' . $e . ']*))' . $e . '\.\.' . $e;

  while (ereg($regex, $path)) {
    $path = ereg_replace($regex, $delim, $path);
  }
  
  return $path;

}

function human_filesize ($filesize) {

  $suffices = 'kMGTPE';

  $n = 0;
  while ($filesize >= 1000) {
    $filesize /= 1024;
    $n++;
  }

  $filesize = round($filesize, 3 - strpos($filesize, '.'));

  if (strpos($filesize, '.') !== false) {
    while (in_array(substr($filesize, -1, 1), array('0', '.'))) {
      $filesize = substr($filesize, 0, strlen($filesize) - 1);
    }
  }

  $suffix = (($n == 0) ? '' : substr($suffices, $n - 1, 1));

  return $filesize . " {$suffix}B";

}

function ends_with($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}


function strip (&$str) {
  $str = stripslashes($str);
}

/* ------------------------------------------------------------------------- */

function listing_page ($message = null) {
  global $self, $directory, $sort, $reverse, $vars;

  html_header();

  $list = getlist($directory);

  if (isset($_GET) && array_key_exists('sort', $_GET)) $sort = $_GET['sort']; else $sort = 'filename';
  if (isset($_GET) && array_key_exists('reverse', $_GET) && $_GET['reverse'] == 'true') $reverse = true; else $reverse = false;

  echo '<h1 style="margin-bottom: 0"><a href="'. $vars['site']['base_url'] . '">webadmin.php</a></h1><span style="padding:15px;">';
  if(isset($_SESSION['login']) && $_SESSION['login']>=0)
     echo $_SESSION['mail'] .  '   <a href="'.$self.'?logout=true">Log out</a>';
  echo '<form enctype="multipart/form-data" action="' . $self . '" method="post">

<table id="main">
';

  directory_choice();

  if (!empty($message)) {
    spacer();
    echo $message;
  }

  if (@is_writable($directory)) {
    upload_box();
    create_box();
  } else {
    spacer();
  }

  if ($list) {
    $list = sortlist($list, $sort, $reverse);
    listing($list);
  } else {
    echo error('not_readable', $directory);
  }

  echo '</table>

</form>

';

  html_footer();

}

function listing ($list) {
  global $directory, $homedir, $sort, $reverse, $win, $cols, $date_format, $self;

  echo '<tr class="listing">
  <th style="text-align: center; vertical-align: middle"><img src="' . $self . '?image=smiley" alt="smiley" /></th>
';

  column_title('filename', $sort, $reverse);
  column_title('size', $sort, $reverse);

  if (!$win) {
    column_title('permission', $sort, $reverse);
    column_title('owner', $sort, $reverse);
    column_title('group', $sort, $reverse);
  }

  echo '  <th class="functions">' . word('functions') . '</th>';

     echo ' </tr>
';

  for ($i = 0; $i < sizeof($list); $i++) {
    $file = $list[$i];

    $timestamps  = 'mtime: ' . date($date_format, $file['mtime']) . ', ';
    $timestamps .= 'atime: ' . date($date_format, $file['atime']) . ', ';
    $timestamps .= 'ctime: ' . date($date_format, $file['ctime']);

    echo '<tr class="listing">
  <td class="checkbox"><input type="checkbox" name="checked' . $i . '" value="true" onfocus="activate(\'other\')" /></td>
  <td class="filename" title="' . html($timestamps) . '">';

    if ($file['is_link']) {

      echo '<img src="' . $self . '?image=link" alt="link" /> ';
      echo html($file['filename']) . ' &rarr; ';

      $real_file = relative2absolute($file['target'], $directory);

      if (@is_readable($real_file)) {
        if (@is_dir($real_file)) {
          echo '[ <a href="' . $self . '?dir=' . urlencode($real_file) . '">' . html($file['target']) . '</a> ]';
        } else {
          echo '<a href="' . $self . '?action=view&amp;file=' . urlencode($real_file) . '">' . html($file['target']) . '</a>';
        }
      } else {
        echo html($file['target']);
      }

    } elseif ($file['is_dir']) {

      echo '<img src="' . $self . '?image=folder" alt="folder" /> [ ';
      if ($win || $file['is_executable']) {
        echo '<a href="' . $self . '?dir=' . urlencode($file['path']) . '">' . html($file['filename']) . '</a>';
      } else {
        echo html($file['filename']);
      }
      echo ' ]';

    } else {

      if (substr($file['filename'], 0, 1) == '.') {
        echo '<img src="' . $self . '?image=hidden_file" alt="hidden file" /> ';
      } else {
        echo '<img src="' . $self . '?image=file" alt="file" /> ';
      }

      if ($file['is_file'] && $file['is_readable']) {
         echo '<a href="' . $self . '?action=view&amp;file=' . urlencode($file['path']) . '">' . html($file['filename']) . '</a>';
      } else {
        echo html($file['filename']);
      }

    }

    if ($file['size'] >= 1000) {
      $human = ' title="' . human_filesize($file['size']) . '"';
    } else {
      $human = '';
    }

    echo "</td>\n";

    echo "\t<td class=\"size\"$human>{$file['size']} B</td>\n";

    if (!$win) {

      echo "\t<td class=\"permission\" title=\"" . decoct($file['permission']) . '">';

      $l = !$file['is_link'] && (!function_exists('posix_getuid') || $file['owner'] == posix_getuid());
      if ($l) echo '<a href="' . $self . '?action=permission&amp;file=' . urlencode($file['path']) . '&amp;dir=' . urlencode($directory) . '">';
      echo html(permission_octal2string($file['permission']));
      if ($l) echo '</a>';

      echo "</td>\n";

      if (array_key_exists('owner_name', $file)) {
        echo "\t<td class=\"owner\" title=\"uid: {$file['owner']}\">{$file['owner_name']}</td>\n";
      } else {
        echo "\t<td class=\"owner\">{$file['owner']}</td>\n";
      }

      if (array_key_exists('group_name', $file)) {
        echo "\t<td class=\"group\" title=\"gid: {$file['group']}\">{$file['group_name']}</td>\n";
      } else {
        echo "\t<td class=\"group\">{$file['group']}</td>\n";
      }

    }

    echo '  <td class="functions">
    <input type="hidden" name="file' . $i . '" value="' . html($file['path']) . '" />
';

    $actions = array();
    if (function_exists('symlink')) {
      $actions[] = 'create_symlink';
    }
    if (@is_writable(dirname($file['path']))) {
      $actions[] = 'delete';
      $actions[] = 'move';
    }
    if ($file['is_file'] && $file['is_readable']) {
      $actions[] = 'copy';
      $actions[] = 'download';
      if ($file['is_writable']) $actions[] = 'edit';
    }
    if (!$win && function_exists('exec') && $file['is_file'] && $file['is_executable'] && file_exists('/bin/sh')) {
      $actions[] = 'execute';
    }

    if (sizeof($actions) > 0) {

      echo '    <select class="small" name="action' . $i . '" size="1">
    <option value="">' . str_repeat('&nbsp;', 30) . '</option>
';

      foreach ($actions as $action) {
        echo "\t\t<option value=\"$action\">" . word($action) . "</option>\n";
      }

      echo '    </select>
    <input class="small" type="submit" name="submit' . $i . '" value=" &gt; " onfocus="activate(\'other\')" />
';

    }

   echo '  </td>';

  }
  echo '<tr class="listing_footer">
  <td style="text-align: right; vertical-align: top"><img src="' . $self . '?image=arrow" alt="&gt;" /></td>
  <td colspan="' . ($cols - 1) . '">
    <input type="hidden" name="num" value="' . sizeof($list) . '" />
    <input type="hidden" name="focus" value="" />
    <input type="hidden" name="olddir" value="' . html($directory) . '" />
';

  $actions = array();
  if (@is_writable(dirname($file['path']))) {
    $actions[] = 'delete';
    $actions[] = 'move';
  }
  $actions[] = 'copy';

  echo '    <select class="small" name="action_all" size="1">
    <option value="">' . str_repeat('&nbsp;', 30) . '</option>
';

  foreach ($actions as $action) {
    echo "\t\t<option value=\"$action\">" . word($action) . "</option>\n";
  }

  echo '    </select>
    <input class="small" type="submit" name="submit_all" value=" &gt; " onfocus="activate(\'other\')" />
  </td>
</tr>
';

}

function column_title ($column, $sort, $reverse) {
  global $self, $directory;

  $d = 'dir=' . urlencode($directory) . '&amp;';

  $arr = '';
  if ($sort == $column) {
    if (!$reverse) {
      $r = '&amp;reverse=true';
      $arr = ' &and;';
    } else {
      $arr = ' &or;';
    }
  } else {
    $r = '';
  }
  echo "\t<th class=\"$column\"><a href=\"$self?{$d}sort=$column$r\">" . word($column) . "</a>$arr</th>\n";

}

function directory_choice () {
  global $directory, $homedir, $cols, $self;

  echo '<tr>
  <td colspan="' . $cols . '" id="directory">
    <a href="' . $self . '?dir=' . urlencode($homedir) . '">' . word('directory') . '</a>:
    <input type="text" name="dir" size="' . textfieldsize($directory) . '" value="' . html($directory) . '" onfocus="activate(\'directory\')" />
    <input type="submit" name="changedir" value="' . word('change') . '" onfocus="activate(\'directory\')" />
  </td>
</tr>
';

}

function upload_box () {
  global $cols;

  echo '<tr>
  <td colspan="' . $cols . '" id="upload">
    ' . word('file') . ':
    <input type="file" name="upload" onfocus="activate(\'other\')" />
    <input type="submit" name="submit_upload" value="' . word('upload') . '" onfocus="activate(\'other\')" />
  </td>
</tr>
';

}

function create_box () {
  global $cols;

  echo '<tr>
  <td colspan="' . $cols . '" id="create">
    <select name="create_type" size="1" onfocus="activate(\'create\')">
    <option value="file">' . word('file') . '</option>
    <option value="directory">' . word('directory') . '</option>
    </select>
    <input type="text" name="create_name" onfocus="activate(\'create\')" />
    <input type="submit" name="submit_create" value="' . word('create') . '" onfocus="activate(\'create\')" />
  </td>
</tr>
';

}

function edit ($file) {
  global $self, $directory, $editcols, $editrows, $apache, $htpasswd, $htaccess;

  html_header();

  echo '<h2 style="margin-bottom: 3pt">' . html($file) . '</h2>

<form action="' . $self . '" method="post">

<table class="dialog">
<tr>
<td class="dialog">

  <textarea name="content" cols="' . $editcols . '" rows="' . $editrows . '" WRAP="off">';

  if (array_key_exists('content', $_POST)) {
    echo $_POST['content'];
  } else {
    $f = fopen($file, 'r');
    while (!feof($f)) {
      echo html(fread($f, 8192));
    }
    fclose($f);
  }

  if (!empty($_POST['user'])) {
    echo "\n" . $_POST['user'] . ':' . crypt($_POST['password']);
  }
  if (!empty($_POST['basic_auth'])) {
    if ($win) {
      $authfile = str_replace('\\', '/', $directory) . $htpasswd;
    } else {
      $authfile = $directory . $htpasswd;
    }
    echo "\nAuthType Basic\nAuthName &quot;Restricted Directory&quot;\n";
    echo 'AuthUserFile &quot;' . html($authfile) . "&quot;\n";
    echo 'Require valid-user';
  }

  echo '</textarea>

  <hr />
';

  if ($apache && basename($file) == $htpasswd) {
    echo '
  ' . word('user') . ': <input type="text" name="user" />
  ' . word('password') . ': <input type="password" name="password" />
  <input type="submit" value="' . word('add') . '" />

  <hr />
';

  }

  if ($apache && basename($file) == $htaccess) {
    echo '
  <input type="submit" name="basic_auth" value="' . word('add_basic_auth') . '" />

  <hr />
';

  }

  echo '
  <input type="hidden" name="action" value="edit" />
  <input type="hidden" name="file" value="' . html($file) . '" />
  <input type="hidden" name="dir" value="' . html($directory) . '" />
  <input type="reset" value="' . word('reset') . '" id="red_button" />
  <input type="submit" name="save" value="' . word('save') . '" id="green_button" style="margin-left: 50px" />

</td>
</tr>
</table>

<p><a href="' . $self . '?dir=' . urlencode($directory) . '">[ ' . word('back') . ' ]</a></p>

</form>

';

  html_footer();

}

function spacer () {
  global $cols;

  echo '<tr>
  <td colspan="' . $cols . '" style="height: 1em"></td>
</tr>
';

}

function textfieldsize ($content) {

  $size = strlen($content) + 5;
  if ($size < 30) $size = 30;

  return $size;

}

function request_dump () {

  foreach ($_REQUEST as $key => $value) {
    echo "\t<input type=\"hidden\" name=\"" . html($key) . '" value="' . html($value) . "\" />\n";
  }

}

/* ------------------------------------------------------------------------- */

function html ($string) {
  global $site_charset;
  return htmlentities($string, ENT_COMPAT, $site_charset);
}

function word ($word) {
  global $words, $word_charset;
  return htmlentities($words[$word], ENT_COMPAT, $word_charset);
}

function phrase ($phrase, $arguments) {
  global $words;
  static $search;

  if (!is_array($search)) for ($i = 1; $i <= 8; $i++) $search[] = "%$i";

  for ($i = 0; $i < sizeof($arguments); $i++) {
    $arguments[$i] = nl2br(html($arguments[$i]));
  }

  $replace = array('{' => '<pre>', '}' =>'</pre>', '[' => '<b>', ']' => '</b>');

  return str_replace($search, $arguments, str_replace(array_keys($replace), $replace, nl2br(html($words[$phrase]))));

}

function getwords ($lang) {
  global $date_format, $word_charset;
  $word_charset = 'UTF-8';

  switch ($lang) {
  case 'de':

    $date_format = 'd.m.y H:i:s';

    return array(
'directory' => 'Verzeichnis',
'file' => 'Datei',
'filename' => 'Dateiname',

'size' => 'Größe',
'permission' => 'Rechte',
'owner' => 'Eigner',
'group' => 'Gruppe',
'other' => 'Andere',
'functions' => 'Funktionen',

'read' => 'lesen',
'write' => 'schreiben',
'execute' => 'ausführen',

'create_symlink' => 'Symlink erstellen',
'delete' => 'löschen',
'rename' => 'umbenennen',
'move' => 'verschieben',
'copy' => 'kopieren',
'edit' => 'editieren',
'download' => 'herunterladen',
'upload' => 'hochladen',
'create' => 'erstellen',
'change' => 'wechseln',
'save' => 'speichern',
'set' => 'setze',
'reset' => 'zurücksetzen',
'relative' => 'Pfad zum Ziel relativ',

'yes' => 'Ja',
'no' => 'Nein',
'back' => 'zurück',
'destination' => 'Ziel',
'symlink' => 'Symbolischer Link',
'no_output' => 'keine Ausgabe',

'user' => 'Benutzername',
'password' => 'Kennwort',
'add' => 'hinzufügen',
'add_basic_auth' => 'HTTP-Basic-Auth hinzufügen',

'uploaded' => '"[%1]" wurde hochgeladen.',
'not_uploaded' => '"[%1]" konnte nicht hochgeladen werden.',
'already_exists' => '"[%1]" existiert bereits.',
'created' => '"[%1]" wurde erstellt.',
'not_created' => '"[%1]" konnte nicht erstellt werden.',
'really_delete' => 'Sollen folgende Dateien wirklich gelöscht werden?',
'deleted' => "Folgende Dateien wurden gelöscht:\n[%1]",
'not_deleted' => "Folgende Dateien konnten nicht gelöscht werden:\n[%1]",
'rename_file' => 'Benenne Datei um:',
'renamed' => '"[%1]" wurde in "[%2]" umbenannt.',
'not_renamed' => '"[%1] konnte nicht in "[%2]" umbenannt werden.',
'move_files' => 'Verschieben folgende Dateien:',
'moved' => "Folgende Dateien wurden nach \"[%2]\" verschoben:\n[%1]",
'not_moved' => "Folgende Dateien konnten nicht nach \"[%2]\" verschoben werden:\n[%1]",
'copy_files' => 'Kopiere folgende Dateien:',
'copied' => "Folgende Dateien wurden nach \"[%2]\" kopiert:\n[%1]",
'not_copied' => "Folgende Dateien konnten nicht nach \"[%2]\" kopiert werden:\n[%1]",
'not_edited' => '"[%1]" kann nicht editiert werden.',
'executed' => "\"[%1]\" wurde erfolgreich ausgeführt:\n{%2}",
'not_executed' => "\"[%1]\" konnte nicht erfolgreich ausgeführt werden:\n{%2}",
'saved' => '"[%1]" wurde gespeichert.',
'not_saved' => '"[%1]" konnte nicht gespeichert werden.',
'symlinked' => 'Symbolischer Link von "[%2]" nach "[%1]" wurde erstellt.',
'not_symlinked' => 'Symbolischer Link von "[%2]" nach "[%1]" konnte nicht erstellt werden.',
'permission_for' => 'Rechte für "[%1]":',
'permission_set' => 'Die Rechte für "[%1]" wurden auf [%2] gesetzt.',
'permission_not_set' => 'Die Rechte für "[%1]" konnten nicht auf [%2] gesetzt werden.',
'not_readable' => '"[%1]" kann nicht gelesen werden.'
    );

  case 'fr':

    $date_format = 'd.m.y H:i:s';

    return array(
'directory' => 'Répertoire',
'file' => 'Fichier',
'filename' => 'Nom fichier',

'size' => 'Taille',
'permission' => 'Droits',
'owner' => 'Propriétaire',
'group' => 'Groupe',
'other' => 'Autres',
'functions' => 'Fonctions',

'read' => 'Lire',
'write' => 'Ecrire',
'execute' => 'Exécuter',

'create_symlink' => 'Créer lien symbolique',
'delete' => 'Effacer',
'rename' => 'Renommer',
'move' => 'Déplacer',
'copy' => 'Copier',
'edit' => 'Ouvrir',
'download' => 'Télécharger sur PC',
'upload' => 'Télécharger sur serveur',
'create' => 'Créer',
'change' => 'Changer',
'save' => 'Sauvegarder',
'set' => 'Exécuter',
'reset' => 'Réinitialiser',
'relative' => 'Relatif',

'yes' => 'Oui',
'no' => 'Non',
'back' => 'Retour',
'destination' => 'Destination',
'symlink' => 'Lien symbollique',
'no_output' => 'Pas de sortie',

'user' => 'Utilisateur',
'password' => 'Mot de passe',
'add' => 'Ajouter',
'add_basic_auth' => 'add basic-authentification',

'uploaded' => '"[%1]" a été téléchargé sur le serveur.',
'not_uploaded' => '"[%1]" n a pas été téléchargé sur le serveur.',
'already_exists' => '"[%1]" existe déjà.',
'created' => '"[%1]" a été créé.',
'not_created' => '"[%1]" n a pas pu être créé.',
'really_delete' => 'Effacer le fichier?',
'deleted' => "Ces fichiers ont été détuits:\n[%1]",
'not_deleted' => "Ces fichiers n ont pu être détruits:\n[%1]",
'rename_file' => 'Renomme fichier:',
'renamed' => '"[%1]" a été renommé en "[%2]".',
'not_renamed' => '"[%1] n a pas pu être renommé en "[%2]".',
'move_files' => 'Déplacer ces fichiers:',
'moved' => "Ces fichiers ont été déplacés en \"[%2]\":\n[%1]",
'not_moved' => "Ces fichiers n ont pas pu être déplacés en \"[%2]\":\n[%1]",
'copy_files' => 'Copier ces fichiers:',
'copied' => "Ces fichiers ont été copiés en \"[%2]\":\n[%1]",
'not_copied' => "Ces fichiers n ont pas pu être copiés en \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" ne peut être ouvert.',
'executed' => "\"[%1]\" a été brillamment exécuté :\n{%2}",
'not_executed' => "\"[%1]\" n a pas pu être exécuté:\n{%2}",
'saved' => '"[%1]" a été sauvegardé.',
'not_saved' => '"[%1]" n a pas pu être sauvegardé.',
'symlinked' => 'Un lien symbolique depuis "[%2]" vers "[%1]" a été crée.',
'not_symlinked' => 'Un lien symbolique depuis "[%2]" vers "[%1]" n a pas pu être créé.',
'permission_for' => 'Droits de "[%1]":',
'permission_set' => 'Droits de "[%1]" ont été changés en [%2].',
'permission_not_set' => 'Droits de "[%1]" n ont pas pu être changés en[%2].',
'not_readable' => '"[%1]" ne peut pas être ouvert.'
    );

  case 'it':

    $date_format = 'd-m-Y H:i:s';

    return array(
'directory' => 'Directory',
'file' => 'File',
'filename' => 'Nome File',

'size' => 'Dimensioni',
'permission' => 'Permessi',
'owner' => 'Proprietario',
'group' => 'Gruppo',
'other' => 'Altro',
'functions' => 'Funzioni',

'read' => 'leggi',
'write' => 'scrivi',
'execute' => 'esegui',

'create_symlink' => 'crea link simbolico',
'delete' => 'cancella',
'rename' => 'rinomina',
'move' => 'sposta',
'copy' => 'copia',
'edit' => 'modifica',
'download' => 'download',
'upload' => 'upload',
'create' => 'crea',
'change' => 'cambia',
'save' => 'salva',
'set' => 'imposta',
'reset' => 'reimposta',
'relative' => 'Percorso relativo per la destinazione',

'yes' => 'Si',
'no' => 'No',
'back' => 'indietro',
'destination' => 'Destinazione',
'symlink' => 'Link simbolico',
'no_output' => 'no output',

'user' => 'User',
'password' => 'Password',
'add' => 'aggiungi',
'add_basic_auth' => 'aggiungi autenticazione base',

'uploaded' => '"[%1]" è stato caricato.',
'not_uploaded' => '"[%1]" non è stato caricato.',
'already_exists' => '"[%1]" esiste già.',
'created' => '"[%1]" è stato creato.',
'not_created' => '"[%1]" non è stato creato.',
'really_delete' => 'Cancello questi file ?',
'deleted' => "Questi file sono stati cancellati:\n[%1]",
'not_deleted' => "Questi file non possono essere cancellati:\n[%1]",
'rename_file' => 'File rinominato:',
'renamed' => '"[%1]" è stato rinominato in "[%2]".',
'not_renamed' => '"[%1] non è stato rinominato in "[%2]".',
'move_files' => 'Sposto questi file:',
'moved' => "Questi file sono stati spostati in \"[%2]\":\n[%1]",
'not_moved' => "Questi file non possono essere spostati in \"[%2]\":\n[%1]",
'copy_files' => 'Copio questi file',
'copied' => "Questi file sono stati copiati in \"[%2]\":\n[%1]",
'not_copied' => "Questi file non possono essere copiati in \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" non può essere modificato.',
'executed' => "\"[%1]\" è stato eseguito con successo:\n{%2}",
'not_executed' => "\"[%1]\" non è stato eseguito con successo\n{%2}",
'saved' => '"[%1]" è stato salvato.',
'not_saved' => '"[%1]" non è stato salvato.',
'symlinked' => 'Il link siambolico da "[%2]" a "[%1]" è stato creato.',
'not_symlinked' => 'Il link siambolico da "[%2]" a "[%1]" non è stato creato.',
'permission_for' => 'Permessi di "[%1]":',
'permission_set' => 'I permessi di "[%1]" sono stati impostati [%2].',
'permission_not_set' => 'I permessi di "[%1]" non sono stati impostati [%2].',
'not_readable' => '"[%1]" non può essere letto.'
    );

  case 'nl':

    $date_format = 'n/j/y H:i:s';

    return array(
'directory' => 'Directory',
'file' => 'Bestand',
'filename' => 'Bestandsnaam',

'size' => 'Grootte',
'permission' => 'Bevoegdheid',
'owner' => 'Eigenaar',
'group' => 'Groep',
'other' => 'Anderen',
'functions' => 'Functies',

'read' => 'lezen',
'write' => 'schrijven',
'execute' => 'uitvoeren',

'create_symlink' => 'maak symlink',
'delete' => 'verwijderen',
'rename' => 'hernoemen',
'move' => 'verplaatsen',
'copy' => 'kopieren',
'edit' => 'bewerken',
'download' => 'downloaden',
'upload' => 'uploaden',
'create' => 'aanmaken',
'change' => 'veranderen',
'save' => 'opslaan',
'set' => 'instellen',
'reset' => 'resetten',
'relative' => 'Relatief pat naar doel',

'yes' => 'Ja',
'no' => 'Nee',
'back' => 'terug',
'destination' => 'Bestemming',
'symlink' => 'Symlink',
'no_output' => 'geen output',

'user' => 'Gebruiker',
'password' => 'Wachtwoord',
'add' => 'toevoegen',
'add_basic_auth' => 'add basic-authentification',

'uploaded' => '"[%1]" is verstuurd.',
'not_uploaded' => '"[%1]" kan niet worden verstuurd.',
'already_exists' => '"[%1]" bestaat al.',
'created' => '"[%1]" is aangemaakt.',
'not_created' => '"[%1]" kan niet worden aangemaakt.',
'really_delete' => 'Deze bestanden verwijderen?',
'deleted' => "Deze bestanden zijn verwijderd:\n[%1]",
'not_deleted' => "Deze bestanden konden niet worden verwijderd:\n[%1]",
'rename_file' => 'Bestandsnaam veranderen:',
'renamed' => '"[%1]" heet nu "[%2]".',
'not_renamed' => '"[%1] kon niet worden veranderd in "[%2]".',
'move_files' => 'Verplaats deze bestanden:',
'moved' => "Deze bestanden zijn verplaatst naar \"[%2]\":\n[%1]",
'not_moved' => "Kan deze bestanden niet verplaatsen naar \"[%2]\":\n[%1]",
'copy_files' => 'Kopieer deze bestanden:',
'copied' => "Deze bestanden zijn gekopieerd naar \"[%2]\":\n[%1]",
'not_copied' => "Deze bestanden kunnen niet worden gekopieerd naar \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" kan niet worden bewerkt.',
'executed' => "\"[%1]\" is met succes uitgevoerd:\n{%2}",
'not_executed' => "\"[%1]\" is niet goed uitgevoerd:\n{%2}",
'saved' => '"[%1]" is opgeslagen.',
'not_saved' => '"[%1]" is niet opgeslagen.',
'symlinked' => 'Symlink van "[%2]" naar "[%1]" is aangemaakt.',
'not_symlinked' => 'Symlink van "[%2]" naar "[%1]" is niet aangemaakt.',
'permission_for' => 'Bevoegdheid voor "[%1]":',
'permission_set' => 'Bevoegdheid van "[%1]" is ingesteld op [%2].',
'permission_not_set' => 'Bevoegdheid van "[%1]" is niet ingesteld op [%2].',
'not_readable' => '"[%1]" kan niet worden gelezen.'
    );

  case 'se':

    $date_format = 'n/j/y H:i:s';
 
    return array(
'directory' => 'Mapp',
'file' => 'Fil',
'filename' => 'Filnamn',
 
'size' => 'Storlek',
'permission' => 'Säkerhetsnivå',
'owner' => 'Ägare',
'group' => 'Grupp',
'other' => 'Andra',
'functions' => 'Funktioner',
 
'read' => 'Läs',
'write' => 'Skriv',
'execute' => 'Utför',
 
'create_symlink' => 'Skapa symlink',
'delete' => 'Radera',
'rename' => 'Byt namn',
'move' => 'Flytta',
'copy' => 'Kopiera',
'edit' => 'Ändra',
'download' => 'Ladda ner',
'upload' => 'Ladda upp',
'create' => 'Skapa',
'change' => 'Ändra',
'save' => 'Spara',
'set' => 'Markera',
'reset' => 'Töm',
'relative' => 'Relative path to target',
 
'yes' => 'Ja',
'no' => 'Nej',
'back' => 'Tillbaks',
'destination' => 'Destination',
'symlink' => 'Symlink',
'no_output' => 'no output',
 
'user' => 'Användare',
'password' => 'Lösenord',
'add' => 'Lägg till',
'add_basic_auth' => 'add basic-authentification',
 
'uploaded' => '"[%1]" har laddats upp.',
'not_uploaded' => '"[%1]" kunde inte laddas upp.',
'already_exists' => '"[%1]" finns redan.',
'created' => '"[%1]" har skapats.',
'not_created' => '"[%1]" kunde inte skapas.',
'really_delete' => 'Radera dessa filer?',
'deleted' => "De här filerna har raderats:\n[%1]",
'not_deleted' => "Dessa filer kunde inte raderas:\n[%1]",
'rename_file' => 'Byt namn på fil:',
'renamed' => '"[%1]" har bytt namn till "[%2]".',
'not_renamed' => '"[%1] kunde inte döpas om till "[%2]".',
'move_files' => 'Flytta dessa filer:',
'moved' => "Dessa filer har flyttats till \"[%2]\":\n[%1]",
'not_moved' => "Dessa filer kunde inte flyttas till \"[%2]\":\n[%1]",
'copy_files' => 'Kopiera dessa filer:',
'copied' => "Dessa filer har kopierats till \"[%2]\":\n[%1]",
'not_copied' => "Dessa filer kunde inte kopieras till \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" kan inte ändras.',
'executed' => "\"[%1]\" har utförts:\n{%2}",
'not_executed' => "\"[%1]\" kunde inte utföras:\n{%2}",
'saved' => '"[%1]" har sparats.',
'not_saved' => '"[%1]" kunde inte sparas.',
'symlinked' => 'Symlink från "[%2]" till "[%1]" har skapats.',
'not_symlinked' => 'Symlink från "[%2]" till "[%1]" kunde inte skapas.',
'permission_for' => 'Rättigheter för "[%1]":',
'permission_set' => 'Rättigheter för "[%1]" ändrades till [%2].',
'permission_not_set' => 'Permission of "[%1]" could not be set to [%2].',
'not_readable' => '"[%1]" kan inte läsas.'
    );

  case 'sp':

    $date_format = 'j/n/y H:i:s';

    return array(
'directory' => 'Directorio',
'file' => 'Archivo',
'filename' => 'Nombre Archivo',

'size' => 'Tamaño',
'permission' => 'Permisos',
'owner' => 'Propietario',
'group' => 'Grupo',
'other' => 'Otros',
'functions' => 'Funciones',

'read' => 'lectura',
'write' => 'escritura',
'execute' => 'ejecución',

'create_symlink' => 'crear enlace',
'delete' => 'borrar',
'rename' => 'renombrar',
'move' => 'mover',
'copy' => 'copiar',
'edit' => 'editar',
'download' => 'bajar',
'upload' => 'subir',
'create' => 'crear',
'change' => 'cambiar',
'save' => 'salvar',
'set' => 'setear',
'reset' => 'resetear',
'relative' => 'Path relativo',

'yes' => 'Si',
'no' => 'No',
'back' => 'atrás',
'destination' => 'Destino',
'symlink' => 'Enlace',
'no_output' => 'sin salida',

'user' => 'Usuario',
'password' => 'Clave',
'add' => 'agregar',
'add_basic_auth' => 'agregar autentificación básica',

'uploaded' => '"[%1]" ha sido subido.',
'not_uploaded' => '"[%1]" no pudo ser subido.',
'already_exists' => '"[%1]" ya existe.',
'created' => '"[%1]" ha sido creado.',
'not_created' => '"[%1]" no pudo ser creado.',
'really_delete' => '¿Borra estos archivos?',
'deleted' => "Estos archivos han sido borrados:\n[%1]",
'not_deleted' => "Estos archivos no pudieron ser borrados:\n[%1]",
'rename_file' => 'Renombra archivo:',
'renamed' => '"[%1]" ha sido renombrado a "[%2]".',
'not_renamed' => '"[%1] no pudo ser renombrado a "[%2]".',
'move_files' => 'Mover estos archivos:',
'moved' => "Estos archivos han sido movidos a \"[%2]\":\n[%1]",
'not_moved' => "Estos archivos no pudieron ser movidos a \"[%2]\":\n[%1]",
'copy_files' => 'Copiar estos archivos:',
'copied' => "Estos archivos han sido copiados a  \"[%2]\":\n[%1]",
'not_copied' => "Estos archivos no pudieron ser copiados \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" no pudo ser editado.',
'executed' => "\"[%1]\" ha sido ejecutado correctamente:\n{%2}",
'not_executed' => "\"[%1]\" no pudo ser ejecutado correctamente:\n{%2}",
'saved' => '"[%1]" ha sido salvado.',
'not_saved' => '"[%1]" no pudo ser salvado.',
'symlinked' => 'Enlace desde "[%2]" a "[%1]" ha sido creado.',
'not_symlinked' => 'Enlace desde "[%2]" a "[%1]" no pudo ser creado.',
'permission_for' => 'Permisos de "[%1]":',
'permission_set' => 'Permisos de "[%1]" fueron seteados a [%2].',
'permission_not_set' => 'Permisos de "[%1]" no pudo ser seteado a [%2].',
'not_readable' => '"[%1]" no pudo ser leído.'
    );

  case 'dk':

    $date_format = 'n/j/y H:i:s';

    return array(
'directory' => 'Mappe',
'file' => 'Fil',
'filename' => 'Filnavn',

'size' => 'Størrelse',
'permission' => 'Rettighed',
'owner' => 'Ejer',
'group' => 'Gruppe',
'other' => 'Andre',
'functions' => 'Funktioner',

'read' => 'læs',
'write' => 'skriv',
'execute' => 'kør',

'create_symlink' => 'opret symbolsk link',
'delete' => 'slet',
'rename' => 'omdøb',
'move' => 'flyt',
'copy' => 'kopier',
'edit' => 'rediger',
'download' => 'download',
'upload' => 'upload',
'create' => 'opret',
'change' => 'skift',
'save' => 'gem',
'set' => 'sæt',
'reset' => 'nulstil',
'relative' => 'Relativ sti til valg',

'yes' => 'Ja',
'no' => 'Nej',
'back' => 'tilbage',
'destination' => 'Distination',
'symlink' => 'Symbolsk link',
'no_output' => 'ingen resultat',

'user' => 'Bruger',
'password' => 'Kodeord',
'add' => 'tilføj',
'add_basic_auth' => 'tilføj grundliggende rettigheder',

'uploaded' => '"[%1]" er blevet uploaded.',
'not_uploaded' => '"[%1]" kunnu ikke uploades.',
'already_exists' => '"[%1]" findes allerede.',
'created' => '"[%1]" er blevet oprettet.',
'not_created' => '"[%1]" kunne ikke oprettes.',
'really_delete' => 'Slet disse filer?',
'deleted' => "Disse filer er blevet slettet:\n[%1]",
'not_deleted' => "Disse filer kunne ikke slettes:\n[%1]",
'rename_file' => 'Omdød fil:',
'renamed' => '"[%1]" er blevet omdøbt til "[%2]".',
'not_renamed' => '"[%1] kunne ikke omdøbes til "[%2]".',
'move_files' => 'Flyt disse filer:',
'moved' => "Disse filer er blevet flyttet til \"[%2]\":\n[%1]",
'not_moved' => "Disse filer kunne ikke flyttes til \"[%2]\":\n[%1]",
'copy_files' => 'Kopier disse filer:',
'copied' => "Disse filer er kopieret til \"[%2]\":\n[%1]",
'not_copied' => "Disse filer kunne ikke kopieres til \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" kan ikke redigeres.',
'executed' => "\"[%1]\" er blevet kørt korrekt:\n{%2}",
'not_executed' => "\"[%1]\" kan ikke køres korrekt:\n{%2}",
'saved' => '"[%1]" er blevet gemt.',
'not_saved' => '"[%1]" kunne ikke gemmes.',
'symlinked' => 'Symbolsk link fra "[%2]" til "[%1]" er blevet oprettet.',
'not_symlinked' => 'Symbolsk link fra "[%2]" til "[%1]" kunne ikke oprettes.',
'permission_for' => 'Rettigheder for "[%1]":',
'permission_set' => 'Rettigheder for "[%1]" blev sat til [%2].',
'permission_not_set' => 'Rettigheder for "[%1]" kunne ikke sættes til [%2].',
'not_readable' => '"[%1]" Kan ikke læses.'
    );

  case 'tr':

    $date_format = 'n/j/y H:i:s';

    return array(
'directory' => 'Klasör',
'file' => 'Dosya',
'filename' => 'dosya adi',

'size' => 'boyutu',
'permission' => 'Izin',
'owner' => 'sahib',
'group' => 'Grup',
'other' => 'Digerleri',
'functions' => 'Fonksiyonlar',

'read' => 'oku',
'write' => 'yaz',
'execute' => 'çalistir',

'create_symlink' => 'yarat symlink',
'delete' => 'sil',
'rename' => 'ad degistir',
'move' => 'tasi',
'copy' => 'kopyala',
'edit' => 'düzenle',
'download' => 'indir',
'upload' => 'yükle',
'create' => 'create',
'change' => 'degistir',
'save' => 'kaydet',
'set' => 'ayar',
'reset' => 'sifirla',
'relative' => 'Hedef yola göre',

'yes' => 'Evet',
'no' => 'Hayir',
'back' => 'Geri',
'destination' => 'Hedef',
'symlink' => 'Kýsa yol',
'no_output' => 'çikti yok',

'user' => 'Kullanici',
'password' => 'Sifre',
'add' => 'ekle',
'add_basic_auth' => 'ekle basit-authentification',

'uploaded' => '"[%1]" yüklendi.',
'not_uploaded' => '"[%1]" yüklenemedi.',
'already_exists' => '"[%1]" kullanilmakta.',
'created' => '"[%1]" olusturuldu.',
'not_created' => '"[%1]" olusturulamadi.',
'really_delete' => 'Bu dosyalari silmek istediginizden eminmisiniz?',
'deleted' => "Bu dosyalar silindi:\n[%1]",
'not_deleted' => "Bu dosyalar silinemedi:\n[%1]",
'rename_file' => 'Adi degisen dosya:',
'renamed' => '"[%1]" adili dosyanin yeni adi "[%2]".',
'not_renamed' => '"[%1] adi degistirilemedi "[%2]" ile.',
'move_files' => 'Tasinan dosyalar:',
'moved' => "Bu dosyalari tasidiginiz yer \"[%2]\":\n[%1]",
'not_moved' => "Bu dosyalari tasiyamadiginiz yer \"[%2]\":\n[%1]",
'copy_files' => 'Kopyalanan dosyalar:',
'copied' => "Bu dosyalar kopyalandi \"[%2]\":\n[%1]",
'not_copied' => "Bu dosyalar kopyalanamiyor \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" düzenlenemiyor.',
'executed' => "\"[%1]\" basariyla çalistirildi:\n{%2}",
'not_executed' => "\"[%1]\" çalistirilamadi:\n{%2}",
'saved' => '"[%1]" kaydedildi.',
'not_saved' => '"[%1]" kaydedilemedi.',
'symlinked' => '"[%2]" den "[%1]" e kýsayol oluþturuldu.',
'not_symlinked' => '"[%2]"den "[%1]" e kýsayol oluþturulamadý.',
'permission_for' => 'Izinler "[%1]":',
'permission_set' => 'Izinler "[%1]" degistirildi [%2].',
'permission_not_set' => 'Izinler "[%1]" degistirilemedi [%2].',
'not_readable' => '"[%1]" okunamiyor.'
    );

  case 'cs':

    $date_format = 'd.m.y H:i:s';

    return array(
'directory' => 'Adresář',
'file' => 'Soubor',
'filename' => 'Jméno souboru',

'size' => 'Velikost',
'permission' => 'Práva',
'owner' => 'Vlastník',
'group' => 'Skupina',
'other' => 'Ostatní',
'functions' => 'Funkce',

'read' => 'Čtení',
'write' => 'Zápis',
'execute' => 'Spouštění',

'create_symlink' => 'Vytvořit symbolický odkaz',
'delete' => 'Smazat',
'rename' => 'Přejmenovat',
'move' => 'Přesunout',
'copy' => 'Zkopírovat',
'edit' => 'Otevřít',
'download' => 'Stáhnout',
'upload' => 'Nahraj na server',
'create' => 'Vytvořit',
'change' => 'Změnit',
'save' => 'Uložit',
'set' => 'Nastavit',
'reset' => 'zpět',
'relative' => 'Relatif',

'yes' => 'Ano',
'no' => 'Ne',
'back' => 'Zpět',
'destination' => 'Destination',
'symlink' => 'Symbolický odkaz',
'no_output' => 'Prázdný výstup',

'user' => 'Uživatel',
'password' => 'Heslo',
'add' => 'Přidat',
'add_basic_auth' => 'přidej základní autentizaci',

'uploaded' => 'Soubor "[%1]" byl nahrán na server.',
'not_uploaded' => 'Soubor "[%1]" nebyl nahrán na server.',
'already_exists' => 'Soubor "[%1]" už exituje.',
'created' => 'Soubor "[%1]" byl vytvořen.',
'not_created' => 'Soubor "[%1]" nemohl být  vytvořen.',
'really_delete' => 'Vymazat soubor?',
'deleted' => "Byly vymazány tyto soubory:\n[%1]",
'not_deleted' => "Tyto soubory nemohly být vytvořeny:\n[%1]",
'rename_file' => 'Přejmenuj soubory:',
'renamed' => 'Soubor "[%1]" byl přejmenován na "[%2]".',
'not_renamed' => 'Soubor "[%1]" nemohl být přejmenován na "[%2]".',
'move_files' => 'Přemístit tyto soubory:',
'moved' => "Tyto soubory byly přemístěny do \"[%2]\":\n[%1]",
'not_moved' => "Tyto soubory nemohly být přemístěny do \"[%2]\":\n[%1]",
'copy_files' => 'Zkopírovat tyto soubory:',
'copied' => "Tyto soubory byly zkopírovány do \"[%2]\":\n[%1]",
'not_copied' => "Tyto soubory nemohly být zkopírovány do \"[%2]\":\n[%1]",
'not_edited' => 'Soubor "[%1]" nemohl být otevřen.',
'executed' => "SOubor \"[%1]\" byl spuštěn :\n{%2}",
'not_executed' => "Soubor \"[%1]\" nemohl být spuštěn:\n{%2}",
'saved' => 'Soubor "[%1]" byl uložen.',
'not_saved' => 'Soubor "[%1]" nemohl být uložen.',
'symlinked' => 'Byl vyvořen symbolický odkaz "[%2]" na soubor "[%1]".',
'not_symlinked' => 'Symbolický odkaz "[%2]" na soubor "[%1]" nemohl být vytvořen.',
'permission_for' => 'Práva k "[%1]":',
'permission_set' => 'Práva k "[%1]" byla změněna na [%2].',
'permission_not_set' => 'Práva k "[%1]" nemohla být změněna na [%2].',
'not_readable' => 'Soubor "[%1]" není možno přečíst.'
    );

  case 'ru':

    $date_format = 'd.m.y H:i:s';

    return array(
'directory' => 'Каталог',
'file' => 'Файл',
'filename' => 'Имя файла',

'size' => 'Размер',
'permission' => 'Права',
'owner' => 'Хозяин',
'group' => 'Группа',
'other' => 'Другие',
'functions' => 'Функция',

'read' => 'читать',
'write' => 'писать',
'execute' => 'выполнить',

'create_symlink' => 'Сделать симлинк',
'delete' => 'удалить',
'rename' => 'переименовать',
'move' => 'передвинуть',
'copy' => 'копировать',
'edit' => 'редактировать',
'download' => 'скачать',
'upload' => 'закачать',
'create' => 'сделать',
'change' => 'поменять',
'save' => 'сохранить',
'set' => 'установить',
'reset' => 'сбросить',
'relative' => 'относительный путь к цели',

'yes' => 'да',
'no' => 'нет',
'back' => 'назад',
'destination' => 'цель',
'symlink' => 'символический линк',
'no_output' => 'нет вывода',

'user' => 'Пользователь',
'password' => 'Пароль',
'add' => 'добавить',
'add_basic_auth' => 'Добавить HTTP-Basic-Auth',

'uploaded' => '"[%1]" был закачен.',
'not_uploaded' => '"[%1]" невозможно было закачять.',
'already_exists' => '"[%1]" уже существует.',
'created' => '"[%1]" был сделан.',
'not_created' => '"[%1]" не возможно сделать.',
'really_delete' => 'Действительно этот файл удалить?',
'deleted' => "Следующие файлы были удалены:\n[%1]",
'not_deleted' => "Следующие файлы не возможно было удалить:\n[%1]",
'rename_file' => 'Переименовываю файл:',
'renamed' => '"[%1]" был переименован на "[%2]".',
'not_renamed' => '"[%1] невозможно было переименовать на "[%2]".',
'move_files' => 'Передвигаю следующие файлы:',
'moved' => "Следующие файлы были передвинуты в каталог \"[%2]\":\n[%1]",
'not_moved' => "Следующие файлы невозможно было передвинуть в каталог \"[%2]\":\n[%1]",
'copy_files' => 'Копирую следущие файлы:',
'copied' => "Следущие файлы былы скопированы в каталог \"[%2]\" :\n[%1]",
'not_copied' => "Следующие файлы невозможно было скопировать в каталог \"[%2]\" :\n[%1]",
'not_edited' => '"[%1]" не может быть отредактирован.',
'executed' => "\"[%1]\" был успешно исполнен:\n{%2}",
'not_executed' => "\"[%1]\" невозможно было запустить на исполнение:\n{%2}",
'saved' => '"[%1]" был сохранен.',
'not_saved' => '"[%1]" невозможно было сохранить.',
'symlinked' => 'Симлинк с "[%2]" на "[%1]" был сделан.',
'not_symlinked' => 'Невозможно было сделать симлинк с "[%2]" на "[%1]".',
'permission_for' => 'Права доступа "[%1]":',
'permission_set' => 'Права доступа "[%1]" были изменены на [%2].',
'permission_not_set' => 'Невозможно было изменить права доступа к "[%1]" на [%2] .',
'not_readable' => '"[%1]" невозможно прочитать.'
    );

  case 'pl':

    $date_format = 'd.m.y H:i:s';

    return array(
'directory' => 'Katalog',
'file' => 'Plik',
'filename' => 'Nazwa pliku',
'size' => 'Rozmiar',
'permission' => 'Uprawnienia',
'owner' => 'Właściciel',
'group' => 'Grupa',
'other' => 'Inni',
'functions' => 'Funkcje',

'read' => 'odczyt',
'write' => 'zapis',
'execute' => 'wykonywanie',

'create_symlink' => 'utwórz dowiązanie symboliczne',
'delete' => 'kasuj',
'rename' => 'zamień',
'move' => 'przenieś',
'copy' => 'kopiuj',
'edit' => 'edytuj',
'download' => 'pobierz',
'upload' => 'Prześlij',
'create' => 'Utwórz',
'change' => 'Zmień',
'save' => 'Zapisz',
'set' => 'wykonaj',
'reset' => 'wyczyść',
'relative' => 'względna ścieżka do celu',

'yes' => 'Tak',
'no' => 'Nie',
'back' => 'cofnij',
'destination' => 'miejsce przeznaczenia',
'symlink' => 'dowiązanie symboliczne',
'no_output' => 'nie ma wyjścia',

'user' => 'Urzytkownik',
'password' => 'Hasło',
'add' => 'dodaj',
'add_basic_auth' => 'dodaj podstawowe uwierzytelnianie',

'uploaded' => '"[%1]" został przesłany.',
'not_uploaded' => '"[%1]" nie może być przesłane.',
'already_exists' => '"[%1]" już istnieje.',
'created' => '"[%1]" został utworzony.',
'not_created' => '"[%1]" nie można utworzyć.',
'really_delete' => 'usunąć te pliki?',
'deleted' => "Pliki zostały usunięte:\n[%1]",
'not_deleted' => "Te pliki nie mogą być usunięte:\n[%1]",
'rename_file' => 'Zmień nazwę pliku:',
'renamed' => '"[%1]" zostało zmienione na "[%2]".',
'not_renamed' => '"[%1] nie można zmienić na "[%2]".',
'move_files' => 'Przenieś te pliki:',
'moved' => "Pliki zostały przeniesione do \"[%2]\":\n[%1]",
'not_moved' => "Pliki nie mogą być przeniesione do \"[%2]\":\n[%1]",
'copy_files' => 'Skopiuj te pliki:',
'copied' => "Pliki zostały skopiowane \"[%2]\":\n[%1]",
'not_copied' => "Te pliki nie mogą być kopiowane do \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" nie można edytować.',
'executed' => "\"[%1]\" zostało wykonane pomyślnie:\n{%2}",
'not_executed' => "\"[%1]\" nie może być wykonane:\n{%2}",
'saved' => '"[%1]" został zapisany.',
'not_saved' => '"[%1]" nie można zapisać.',
'symlinked' => 'Dowiązanie symboliczne "[%2]" do "[%1]" zostało utworzone.',
'not_symlinked' => 'Dowiązanie symboliczne "[%2]" do "[%1]" nie moze być utworzone.',
'permission_for' => 'Uprawnienia "[%1]":',
'permission_set' => 'Uprawnienia "[%1]" zostały ustalone na [%2].',
'permission_not_set' => 'Uprawnienia "[%1]" nie mogą być ustawione na [%2].',
'not_readable' => '"[%1]" nie można odczytać.'
    );

  case 'en':
  default:

    $date_format = 'n/j/y H:i:s';

    return array(
'directory' => 'Directory',
'file' => 'File',
'filename' => 'Filename',

'size' => 'Size',
'permission' => 'Permission',
'owner' => 'Owner',
'group' => 'Group',
'other' => 'Others',
'functions' => 'Functions',
'access' => 'Access',

'read' => 'read',
'write' => 'write',
'execute' => 'execute',

'create_symlink' => 'create symlink',
'delete' => 'delete',
'rename' => 'rename',
'move' => 'move',
'copy' => 'copy',
'edit' => 'edit',
'download' => 'download',
'upload' => 'upload',
'create' => 'create',
'change' => 'change',
'save' => 'save',
'set' => 'set',
'reset' => 'reset',
'relative' => 'Relative path to target',

'yes' => 'Yes',
'no' => 'No',
'back' => 'back',
'destination' => 'Destination',
'symlink' => 'Symlink',
'no_output' => 'no output',

'user' => 'User',
'password' => 'Password',
'add' => 'add',
'add_basic_auth' => 'add basic-authentification',

'uploaded' => '"[%1]" has been uploaded.',
'not_uploaded' => '"[%1]" could not be uploaded.',
'already_exists' => '"[%1]" already exists.',
'created' => '"[%1]" has been created.',
'not_created' => '"[%1]" could not be created.',
'really_delete' => 'Delete these files?',
'deleted' => "These files have been deleted:\n[%1]",
'not_deleted' => "These files could not be deleted:\n[%1]",
'rename_file' => 'Rename file:',
'renamed' => '"[%1]" has been renamed to "[%2]".',
'not_renamed' => '"[%1] could not be renamed to "[%2]".',
'move_files' => 'Move these files:',
'moved' => "These files have been moved to \"[%2]\":\n[%1]",
'not_moved' => "These files could not be moved to \"[%2]\":\n[%1]",
'copy_files' => 'Copy these files:',
'copied' => "These files have been copied to \"[%2]\":\n[%1]",
'not_copied' => "These files could not be copied to \"[%2]\":\n[%1]",
'not_edited' => '"[%1]" can not be edited.',
'executed' => "\"[%1]\" has been executed successfully:\n{%2}",
'not_executed' => "\"[%1]\" could not be executed successfully:\n{%2}",
'saved' => '"[%1]" has been saved.',
'not_saved' => '"[%1]" could not be saved.',
'symlinked' => 'Symlink from "[%2]" to "[%1]" has been created.',
'not_symlinked' => 'Symlink from "[%2]" to "[%1]" could not be created.',
'permission_for' => 'Permission of "[%1]":',
'permission_set' => 'Permission of "[%1]" was set to [%2].',
'permission_not_set' => 'Permission of "[%1]" could not be set to [%2].',
'not_readable' => '"[%1]" can not be read.',
'read_access_missing' => "You do not have [%1] access to [%2]."
//'read_access_missing' => "You do not have [%1] access to [%2]. Get read access? <form><input='submit' text='Get read access'/></form>"
    );

  }

}

function getimage ($image) {
  switch ($image) {
  case 'file':
    return base64_decode('R0lGODlhEQANAJEDAJmZmf///wAAAP///yH5BAHoAwMALAAAAAARAA0AAAItnIGJxg0B42rsiSvCA/REmXQWhmnih3LUSGaqg35vFbSXucbSabunjnMohq8CADsA');
  case 'folder':
    return base64_decode('R0lGODlhEQANAJEDAJmZmf///8zMzP///yH5BAHoAwMALAAAAAARAA0AAAIqnI+ZwKwbYgTPtIudlbwLOgCBQJYmCYrn+m3smY5vGc+0a7dhjh7ZbygAADsA');
  case 'hidden_file':
    return base64_decode('R0lGODlhEQANAJEDAMwAAP///5mZmf///yH5BAHoAwMALAAAAAARAA0AAAItnIGJxg0B42rsiSvCA/REmXQWhmnih3LUSGaqg35vFbSXucbSabunjnMohq8CADsA');
  case 'link':
    return base64_decode('R0lGODlhEQANAKIEAJmZmf///wAAAMwAAP///wAAAAAAAAAAACH5BAHoAwQALAAAAAARAA0AAAM5SArcrDCCQOuLcIotwgTYUllNOA0DxXkmhY4shM5zsMUKTY8gNgUvW6cnAaZgxMyIM2zBLCaHlJgAADsA');
  case 'smiley':
    return base64_decode('R0lGODlhEQANAJECAAAAAP//AP///wAAACH5BAHoAwIALAAAAAARAA0AAAIslI+pAu2wDAiz0jWD3hqmBzZf1VCleJQch0rkdnppB3dKZuIygrMRE/oJDwUAOwA=');
  case 'arrow':
    return base64_decode('R0lGODlhEQANAIABAAAAAP///yH5BAEKAAEALAAAAAARAA0AAAIdjA9wy6gNQ4pwUmav0yvn+hhJiI3mCJ6otrIkxxQAOw==');
  }
}

function html_header () {
  global $site_charset;

  echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=$site_charset" />

<title>webadmin.php</title>

<style type="text/css">
body { font: small sans-serif; text-align: center }
img { width: 17px; height: 13px }
a, a:visited { text-decoration: none; color: navy }
hr { border-style: none; height: 1px; background-color: silver; color: silver }
#main { margin-top: 6pt; margin-left: auto; margin-right: auto; border-spacing: 1px }
#main th { background: #eee; padding: 3pt 3pt 0pt 3pt }
.listing th, .listing td { padding: 1px 3pt 0 3pt }
.listing th { border: 1px solid silver }
.listing td { border: 1px solid #ddd; background: white }
.listing .checkbox { text-align: center }
.listing .filename { text-align: left }
.listing .size { text-align: right }
.listing th.permission { text-align: left }
.listing td.permission { font-family: monospace }
.listing .owner { text-align: left }
.listing .group { text-align: left }
.listing .functions { text-align: left }
.listing .access { text-align: center }
.listing_footer td { background: #eee; border: 1px solid silver }
#directory, #upload, #create, .listing_footer td, #error td, #notice td { text-align: left; padding: 3pt }
#directory { background: #eee; border: 1px solid silver }
#upload { padding-top: 1em }
#create { padding-bottom: 1em }
.small, .small option { font-size: x-small }
textarea { border: none; background: white }
table.dialog { margin-left: auto; margin-right: auto }
td.dialog { background: #eee; padding: 1ex; border: 1px solid silver; text-align: center }
#permission { margin-left: auto; margin-right: auto }
#permission td { padding-left: 3pt; padding-right: 3pt; text-align: center }
td.permission_action { text-align: right }
#symlink { background: #eee; border: 1px solid silver }
#symlink td { text-align: left; padding: 3pt }
#red_button { width: 120px; color: #400 }
#green_button { width: 120px; color: #040 }
#error td { background: maroon; color: white; border: 1px solid silver }
#notice td { background: green; color: white; border: 1px solid silver }
#notice pre, #error pre { background: silver; color: black; padding: 1ex; margin-left: 1ex; margin-right: 1ex }
code { font-size: 12pt }
td { white-space: nowrap }
</style>

<script type="text/javascript">
<!--
function activate (name) {
  if (document && document.forms[0] && document.forms[0].elements['focus']) {
    document.forms[0].elements['focus'].value = name;
  }
}
function permission_clicked(){
   var e = document.getElementById("askpermission");
   e.name='action';
}
//-->
</script>

</head>
<body>


END;

}

function html_footer () {

  echo <<<END
</body>
</html>
END;

}

function notice ($phrase) {
  global $cols;

  $args = func_get_args();
  array_shift($args);

  return '<tr id="notice">
  <td colspan="' . $cols . '">' . phrase($phrase, $args) . '</td>
</tr>
';

}

function error ($phrase) {
  global $cols;

  $args = func_get_args();
  array_shift($args);

  return '<tr id="error">
  <td colspan="' . $cols . '">' . phrase($phrase, $args) . '</td>
</tr>
';

}

function error_with_form ($phrase, $form="") {
  global $cols;

  $args = func_get_args();
  array_shift($args);
  array_shift($args);

  return '<tr id="error">
  <td colspan="' . $cols . '">' . phrase($phrase, $args) . $form . '</td>
</tr>
';

}

/**
* case_failure 
* 0 : login
* 1 : register
* 2 : forgot password
* 3 : reset password
*/

function show_register ($msg, $case_failure, $email, $passwords, $justification, $clear_fields, $show_login, $onlyMessage=false) {
    global $vars,$debug;
    if($debug)
    {
       $args = func_get_args();
       log_this(date(DATE_ATOM). ' call show_register - ' . return_var_dump($args));
    }
    
    if($show_login){
       html_header();
   
       if($clear_fields){
          $email = "";
       	  $passwords = "";
          $justification = "";
       }
       echo '
        <script type="text/javascript">
        <!--
        function toggle(id,msg){
           var e = document.getElementById("login_div");
           var f = document.getElementById("register_div");
           var g = document.getElementById("msg_login");
           var h = document.getElementById("msg_register");
           var i = document.getElementById("forgot_div");
           var j = document.getElementById("msg_forgot");
           var k = document.getElementById("reset_div");
           var l = document.getElementById("msg_reset");
        
        
           if(id == 1){
              e.style.display =  "none";
              i.style.display =  "none";
              k.style.display =  "none";
              f.style.display =  "block";
              g.innerHTML="";
              l.innerHTML="";
              j.innerHTML="";
           } else if (id == 2){
              f.style.display =  "none";
              k.style.display =  "none";
              e.style.display =  "none";
              i.style.display =  "block";
              g.innerHTML="";
              h.innerHTML="";
              l.innerHTML="";
           } else if (id == 3){
              i.style.display =  "none";
              f.style.display =  "none";
              e.style.display =  "none";
              k.style.display =  "block";
              g.innerHTML="";
              h.innerHTML="";
              j.innerHTML="";
           } else{
              f.style.display =  "none";
              k.style.display =  "none";
              i.style.display =  "none";
              e.style.display =  "block";
              l.innerHTML="";
              h.innerHTML="";
              j.innerHTML="";
           }
        }
          //-->
        </script>
        <body style="margin-left: auto; margin-right: auto;">
        <h1 style="margin-bottom: 0"><a href="'. $vars['site']['base_url'] . '">webadmin.php</a></h1>
        <span style="padding: 15px;">
        <div id="login_div" align="center">
        <form action="' . $vars['site']['base_url'] . '?action=login" method="post">
        <table>
        <tr>
        <td>Email</td>
        <td><input id="login_email" type="text" name="email" class="input" autocomplete="off" value="' .$email. '"/></td>
        <tr/>
        <tr>
        <td>Password </td>
        <td><input id="login_password" type="password" name="password" class="input" autocomplete="off" value="' . $passwords . '"/></td>
        <td><a href="javascript:toggle(2);">Forgot Password</a></td>
        </tr>
        <tr>
        <td>
        <input type="submit" class="button" value="Login" /></td><td colspan="2"><a href="javascript:toggle(1);">Register</a></td></tr>
        <tr>
        <td colspan="3">
        <span class="msg" id="msg_login">' . $msg . '</span>
        </td></tr>
        </table>
        </form>
        </div>
        
        <div id="register_div" style="display:none" align="center">
        <form action="' . $vars['site']['base_url'] . '?action=register" method="post">
        <table>
        <tr>
        <td>Email</td>
        <td><input id="register_email" type="text" name="email" class="input" autocomplete="off" value="' .$email. '"/><br/>
        <tr>
        <td>Password</td>
        <td><input id="register_password" type="password" name="password" class="input" autocomplete="off" value="'.$passwords.'"/></td></tr>
        <tr><td>Justification </td><td>
        <input type="text" id="register_justification" name="justification" class="input" autocomplete="off" value="' .$justification. '"/><td/></tr><tr><td>
        <a href="javascript:toggle();">Login</a></td><td>
        <input type="hidden" name="register" value="true" />
        <input type="submit" class="button" value="Register" /></td></tr>
        <tr><td colspan="3">
        <span class="msg" id="msg_register">' . $msg . '</span></td></tr>
        </table>
        </form>
        </div>
        <div id="forgot_div" style="display:none" align="center">
        <form action="' . $vars['site']['base_url'] . '?action=forgot" method="post">
        <table>
        <tr>
        <td>Email</td>
        <td><input id="forgot_email" type="text" name="email" class="input" autocomplete="off" value="' .$email. '"/><br/>
        </td></tr>
        <tr><td>
        <input type="hidden" name="forgot" value="true" />
        <input type="submit" class="button" value="Recover password" /></td></tr>
        <tr><td colspan="3">
        <span class="msg" id="msg_forgot">' . $msg . '</span></td></tr>
        </table>
        </form>
        </div>
<div id="reset_div" style="display:none" align="center">
<form action="' . $vars['site']['base_url'] . '?action=presetvalues" method="post">
<table>
<tr>
<td>Password</td>
<td><input id="reset_password" type="password" name="password" class="input" autocomplete="off" value=""/></td></tr>
<tr>
<td>Confirm Password</td>
<td><input id="reset_cpassword" type="password" name="cpassword" class="input" autocomplete="off" value=""/>
<input id="key" type="hidden" name="key" value="' . $_GET['key']  .'"/></td></tr>
</td></tr>
<tr><td>
<a href="javascript:toggle();">Login</a></td><td>
<input type="hidden" name="presetvalues" value="true" />
<input type="submit" class="button" value="Change password" /></td></tr>
<tr><td colspan="3">
</table>
</form>
</div>

<span class="msg" id="msg_reset">' . $msg . '</span></td></tr>
        </span>
         ';
    html_footer();
    if($onlyMessage)
       echo '<script>toggle(' .$case_failure. ', "", true);</script>';
    else
       echo '<script>toggle(' .$case_failure. ');</script>';
    global $vars, $die_and_reload;
        if($die_and_reload){
             echo '<script type="text/javascript">
             function doReload(){
             <!--
             window.location = "' . $vars['site']['base_url'] . '"
             //-->
             }
             setInterval("doReload()", 3000);
             </script>';
        }
        die();
  }
}

function get_connection(){
    global $vars;
    try { 
             $connection = @mysqli_connect($vars['db']['host'],$vars['db']['user'],$vars['db']['password'],$vars['db']['dbname']);
             } catch (Exception $exc) {
             $msg = "ERROR 101. Please contact your site administrator.";
             }
    return $connection;
}

function close_connection($con){
    mysqli_close($con); 
}

function get_read_access($msg_show_register,$files, $query, $err_text, $verb, $cfile){
  global $vars,$debug;
  if(!isset($_SESSION) or (array_key_exists('login', $_SESSION) == false)){
        #show_register("Please login to copy the files.","" , "", "", "","",true);
        show_register($msg_show_register,"" , "", "", "","",true);
  }

  if($debug){
     log_this(date(DATE_ATOM). ' query - ' . $query);
  }

  $con = get_connection();
  $read_access=true;
  $owner_authorized_awaiting = false;
  foreach ($files as $file) {
	if($debug)
	   log_this(date(DATE_ATOM). ' query - ' . $query . ' - going into');
        $res = mysqli_query($con, $query);
        if(mysqli_num_rows($res)<1){
            $read_access=false;
	    
            if(strpos($query, "owner_authorized='1'") === FALSE) {
		
            } else{
		$qx = str_replace("owner_authorized='1'", "owner_authorized='0'", $query);
		if($debug)
  	           log_this(date(DATE_ATOM). ' qx - ' . $qx . ' ** ');
	        $rx = mysqli_query($con, $qx);
        	if(mysqli_num_rows($rx)<1){
			
		}else{
		  $owner_authorized_awaiting = true; 
		}
            }

            break;    
        }
  }
  if($debug){
     log_this(date(DATE_ATOM). ' read_access - ' . $read_access . ' ** ');
     log_this( $read_access ? 'true' : 'false');

  }

  if(!$read_access){
     $isdir = ""; 
     if (isset($_POST) && array_key_exists('is_dir', $_POST))
     {
        $isdir = "<input type='hidden' id='is_dir' name='is_dir' value='true' />";
     }

     if (isset($_POST) && array_key_exists('create_type', $_POST) && ($_POST['create_type'] == 'directory' )) 
     {
        $isdir = "<input type='hidden' id='is_dir' name='is_dir' value='true' />";
     }
 
     if (isset($_POST) && array_key_exists('rwaccess', $_POST))
     {
        $isdir = "<input type='hidden' id='rwaccess' name='rwaccess' value='true' />";
     }

     if (isset($_POST) && array_key_exists('create_type', $_POST) && ($_POST['create_type'] == 'file' )) 
     {
        $isdir = "<input type='hidden' id='rwaccess' name='rwaccess' value='true' />";
     }
 
 
     if($debug){
        log_this(date(DATE_ATOM). ' isdir - ' . $isdir . ' ** ' .  ' isfile - ' . $isfile);
     }


     $access_text = "Ask permission?";
     $disabled = "";
     if($owner_authorized_awaiting){
	    $access_text = "Permission awaiting";
        $disabled = "disabled";
     }
     $form="&nbsp;&nbsp;&nbsp;&nbsp;<form action='" .$vars['site']['base_url']. "'><table><tr><td><input type='submit' value='$access_text' $disabled onclick='return permission_clicked();'/>
<input id='askpermission' type='hidden' value='askpermission'/>
<input type='hidden'  name='fileperm' value='" . $cfile. "'/>
<input type='hidden'  name='perm' value='$verb'/>
". $isdir . "
</td></tr></table>
</form>";
     if($debug)
        log_this(date(DATE_ATOM). ' form - ' . $form . ' ** ');

     listing_page(error_with_form($err_text,$form, $verb,$cfile)); 
     die();
  }
  mysqli_close($con);
}
 
function email_with_attach($orig, $new, $file){
  global $vars;
    $content = chunk_split(base64_encode($orig));

    // a random hash will be necessary to send mixed content
    $separator = md5(time());

    // carriage return type (we use a PHP end of line constant)
    $eol = PHP_EOL;

    // main header (multipart mandatory)
    $headers = "From: webadmin <root@localhost>" . $eol;
    $headers .= "MIME-Version: 1.0" . $eol;
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol . $eol;
    $headers .= "Content-Transfer-Encoding: 7bit" . $eol;
    $headers .= "This is a MIME encoded message." . $eol . $eol;

    $message="Hi, <br/>User " . $_SESSION['mail']. ' has modified the file ' . $file . '. Attached herewith are the two versions of the file.<br/><br/>Thanks.';
    // message
    $headers .= "--" . $separator . $eol;
    $headers .= "Content-Type: text/html; charset=\"iso-8859-1\"" . $eol;
    $headers .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
    $headers .= $message . $eol . $eol;

    // attachment
    $headers .= "--" . $separator . $eol;
    $headers .= "Content-Type: application/octet-stream; name=\"" . $file . ".original". "\"" . $eol;
    $headers .= "Content-Transfer-Encoding: base64" . $eol;
    $headers .= "Content-Disposition: attachment" . $eol . $eol;
    $headers .= $content . $eol . $eol;
  #  $headers .= "--" . $separator . "--";

    $headers .= "--" . $separator . $eol;
    $headers .= "Content-Type: application/octet-stream; name=\"" . $file . ".new". "\"" . $eol;
    $headers .= "Content-Transfer-Encoding: base64" . $eol;
    $headers .= "Content-Disposition: attachment" . $eol . $eol;
    
    $content_new = chunk_split(base64_encode($new));
    $headers .= $content_new . $eol . $eol;
    $headers .= "--" . $separator . "--";

    $to=$vars['site']['admin_mail'];
    $subject="webadmin.php - File modified.";
    $body="Hi, <br/>User " . $_SESSION['mail']. ' has modified the file ' . $file . '. Attached herewith are the two versions of the file.<br/><br/>Thanks.';
    mail($to, $subject, $body, $headers); 
}



function get_all_paths($path){
   global $win,$delim;
   $in ="";
   
   if ($win) {
       $in="(";
	   $pi = pathinfo($path);
	   $txt = $pi['filename'];
	   $ext = $pi['extension'];
	  
	   if($txt!="" && $ext!="")
		  $str = substr($path, 0, strpos($path, $txt.'.' .$ext));
	   else{
		  $path = relative2absolute($path);
		  $str = $path;
	   }
       $terms = explode($delim, $str);
	   $p="";
	   
	   foreach ($terms as $t){
		 if($t == '') continue;
		 $p.= $t . $delim;		 
		 $in.= "'". addslashes($p). "',"; 
	   }
	   $in = substr($in, 0, strlen($in)-1);
	   if(!is_dir($path))
		  $in.=",'" . addslashes($path) .  "')";
	   else{ 
		  $path = relative2absolute($path);
		  $in.=")";
	   }
	   
   
   } else {
	   $pi = pathinfo($path);
	   $txt = $pi['filename'];
	   $ext = $pi['extension'];
	  
	   if($txt!="" && $ext!="")
		  $str = substr($path, 0, strpos($path, $txt.'.' .$ext));
	   else{
		  $path = relative2absolute($path);
		  $str = $path;
	   }
	   $in="('/', ";
	   $terms = explode('/', $str);
	   $p="";
	   
	   foreach ($terms as $t){
		 if($t == '') continue;
		 $p.= $t . '/';
		 $in.="'/" .$p. "',"; 
	   }
	   $in = substr($in, 0, strlen($in)-1);
	   if(!is_dir($path))
		  $in.=",'" . $path .  "')";
	   else{ 
		  $path = relative2absolute($path);
		  $in.=")";
	   }
   }

   #echo $in;
   return $in;
}

function log_this($message){
  error_log($message, 3, "my-errors.log");
}

function return_var_dump(){
   $args=func_get_args(); //for <5.3.0 support ...
   ob_start();
   call_user_func_array('var_dump',$args);
   return ob_get_clean();
}

/**
*   $body='Hi, <br/> <br/> A new user - ' . $email. ' wants to activate his/her account. Please authorize by visiting the attached link. <br/> <br/> <a href="%%URL%%">%%URL%%</a>';

$values : These are the strings that need to be replaced.
  ('USERNAME' => 'purple.coder@yahoo.co.uk', 'URL1' => 'test.com/test.php', 'URL2' => 'test.com/test2.php')
 
*
*/

function mail_admin($sub , $msg, $values, $query){
   global $vars, $debug;
   $to=$vars['site']['admin_mail'];
   $subject=$sub;
   $map = array();
   $pattern = '[[%s]]';
   $id = "";
   foreach($values as $var => $value)
   {
      if(strpos($var, 'URL')!== false){
          if($id==""){
	     $id = md5(uniqid().time());
             $query = strtr($query, array('[[OWNER_KEY]]' => $id));
             if($debug)
		   log_this(date(DATE_ATOM). ' query - ' . $query);
             $con = get_connection();
             mysqli_query($con, $query);
	     mysqli_close($con);
          }
          $value.="&ownerk=" . $id;
      }
      $map[sprintf($pattern, $var)] = $value;
   }
   $msg = strtr($msg, $map); 
   $body = $msg;
   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
   
   mail($to, $subject, $body, $headers,'-froot@localhost');
   if($debug){
	  log_this(date(DATE_ATOM). ' mail subject - ' . $subject);   
	  log_this(date(DATE_ATOM). ' mail to - ' . $to);   
	  log_this(date(DATE_ATOM). ' mail body - ' . $body);   
   }
}
?>
