<?php

$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

if($argc!=2) {
    usage();
    exit;
}

$prj_path = $argv[1];
$prj_path .= $prj_path[strlen($prj_path)-1] == '/' ? '' : '/';
$prj_path = $prj_path[0] == '/' ? $prj_path : getcwd().'/'.$prj_path;
$prj = basename($prj_path);

if( file_exists($prj_path) ) {
    _P("$prj_path already exists");
    exit;
}

_P("Generating $prj");
$cmd = "mkdir -p $prj_path/dokin";
shell_exec($cmd);
if(!file_exists($prj_path)) {
    _P("could not create $prj_path");
    exit;
}

# copy myself
$p = $_SERVER['SCRIPT_FILENAME'];
$p = dirname($p);
$p = dirname($p);
$p = dirname($p);
chdir($p);

# copy dokin itself
clean_r_copy('dokin',$prj_path.'dokin',array('tmpl'));

# create required subdirectory
$aDirs = array( 'config', 'controllers', 'docroot', 'layouts', 'lib', 'logs', 'models', 'templates', );
foreach( $aDirs as $sDir) {
    shell_exec("mkdir -p $prj_path/app/$sDir");
}

shell_exec("chmod a+w $prj_path/app/logs");

# copy content of tmpl in docroot
clean_r_copy('dokin/bin/tmpl/docroot',$prj_path.'app/docroot');

# copy content of tmpl/config to config 
clean_r_copy('dokin/bin/tmpl/config',$prj_path.'app/config');

# replace __prj__ with $prj
if(file_exists($prj_path.'app/config/DBConfig.php')) {
    $sContent = file_get_contents($prj_path.'app/config/DBConfig.php');
    $sContent = str_replace('__prj__',$prj,$sContent);
    file_put_contents($prj_path.'app/config/DBConfig.php',$sContent);
}

if (false) {
# default layouts
clean_r_copy('dokin/bin/tmpl/layouts',$prj_path.'app/layouts');

# generate controller
$sContent = file_get_contents('dokin/bin/tmpl/controllers/Controller.php');
$sContent = str_replace('__NAME__','DefaultController',$sContent);
file_put_contents($prj_path.'app/controllers/DefaultController.php',$sContent);

$sContent = "<html><head><title>$prj</title></head><body><h1>$prj</h1></body></html>";
file_put_contents($prj_path.'app/templates/default.php',$sContent);
}

function clean_r_copy($src,$dst,$excl = array()) {
    if($src[strlen($src)-1] != '/') {
        $src .= '/';
    }

    if($dst[strlen($dst)-1] != '/') {
        $dst .= '/';
    }

    $dh = opendir($src);
    if(!$dh) {
        _P("Could not find myself");
        exit;
    }
    while( ($file=readdir($dh)) != false ) {
        if ($file != '.' && $file != '..' && $file != '.svn') {
            $parts = explode('/',$dst);
            array_shift($parts);
            array_shift($parts);
            $n = implode('/',$parts);
#_P(" ?? $dst $file $n ".$excl[0]);
            if(!in_array($file,$excl) && !in_array($n.$file,$excl)) {
                if (is_dir($src.$file)) {
                    clean_r_copy($src.$file,$dst.$file,$excl);
                } else {
                    if(!file_exists($dst)) {
#_P("Creating dir $dst");
                        shell_exec("mkdir -p $dst");
                    }
#_P($file.' -> '.$dst.$file);
                    shell_exec("cp -a $src$file $dst$file");
                }
            }
        }
    }
}



function usage() {
    print "dokin <project>\n";
}

function _P($msg) {
    print "$msg\n";
}

?>
