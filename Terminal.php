<?php

$_COLORS['NORMAL'] = chr(27).'[0m';
$_COLORS['BOLD'] = chr(27).'[1m';
$_COLORS['LIGHT_BLACK'] = chr(27).'[1;30m';
$_COLORS['BLACK'] = chr(27).'[0;30m';
$_COLORS['LIGHT_RED'] = chr(27).'[1;31m';
$_COLORS['RED'] = chr(27).'[0;31m';
$_COLORS['LIGHT_GREEN'] = chr(27).'[1;32m';
$_COLORS['GREEN'] = chr(27).'[0;32m';
$_COLORS['LIGHT_YELLOW'] = chr(27).'[1;33m';
$_COLORS['YELLOW'] = chr(27).'[0;33m';
$_COLORS['LIGHT_BLUE'] = chr(27).'[1;34m';
$_COLORS['BLUE'] = chr(27).'[0;34m';
$_COLORS['LIGHT_MAGENTA'] = chr(27).'[1;35m';
$_COLORS['MAGENTA'] = chr(27).'[0;35m';
$_COLORS['LIGHT_CYAN'] = chr(27).'[1;36m';
$_COLORS['CYAN'] = chr(27).'[0;36m';
$_COLORS['WHITE'] = chr(27).'[1;37m';
$_COLORS['GREY'] = chr(27).'[0;37m';

function normal($sString)
{
    global $_COLORS;
    return $_COLORS['normal'].$sString;
}

function bold($sString) 
{
    global $_COLORS;
    return $_COLORS['BOLD'].$sString.$_COLORS['NORMAL'];
}

function light_black($sString) 
{
    global $_COLORS;
    return $_COLORS['LIGHT_BLACK'].$sString.$_COLORS['NORMAL'];
}

function black($sString) 
{
    global $_COLORS;
    return $_COLORS['BLACK'].$sString.$_COLORS['NORMAL'];
}

function light_red($sString) 
{
    global $_COLORS;
    return $_COLORS['LIGHT_RED'].$sString.$_COLORS['NORMAL'];
}

function red($sString) 
{
    global $_COLORS;
    return $_COLORS['RED'].$sString.$_COLORS['NORMAL'];
}

function light_green($sString) 
{
    global $_COLORS;
    return $_COLORS['LIGHT_GREEN'].$sString.$_COLORS['NORMAL'];
}

function green($sString) 
{
    global $_COLORS;
    return $_COLORS['GREEN'].$sString.$_COLORS['NORMAL'];
}

function light_yellow($sString) 
{
    global $_COLORS;
    return $_COLORS['LIGHT_YELLOW'].$sString.$_COLORS['NORMAL'];
}

function yellow($sString) 
{
    global $_COLORS;
    return $_COLORS['YELLOW'].$sString.$_COLORS['NORMAL'];
}

function light_blue($sString) 
{
    global $_COLORS;
    return $_COLORS['LIGHT_BLUE'].$sString.$_COLORS['NORMAL'];
}

function blue($sString) 
{
    global $_COLORS;
    return $_COLORS['BLUE'].$sString.$_COLORS['NORMAL'];
}

function light_magenta($sString) 
{
    global $_COLORS;
    return $_COLORS['LIGHT_MAGENTA'].$sString.$_COLORS['NORMAL'];
}

function magenta($sString) 
{
    global $_COLORS;
    return $_COLORS['MAGENTA'].$sString.$_COLORS['NORMAL'];
}

function light_cyan($sString) 
{
    global $_COLORS;
    return $_COLORS['LIGHT_CYAN'].$sString.$_COLORS['NORMAL'];
}

function cyan($sString) 
{
    global $_COLORS;
    return $_COLORS['CYAN'].$sString.$_COLORS['NORMAL'];
}

function white($sString) 
{
    global $_COLORS;
    return $_COLORS['WHITE'].$sString.$_COLORS['NORMAL'];
}

function grey($sString) 
{
    global $_COLORS;
    return $_COLORS['GREY'].$sString.$_COLORS['NORMAL'];
}


