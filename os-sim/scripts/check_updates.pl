#!/usr/bin/perl

use DBI;
use ossim_conf;

$update_location = "/etc/ossim/updates/update_log.txt";

$check_enable = $ossim_conf::ossim_data->{"update_checks_enable"}; 

if($check_enable ne "yes")
{
exit();
}

$use_proxy = $ossim_conf::ossim_data->{"update_checks_use_proxy"}; 
$update_url = $ossim_conf::ossim_data->{"update_checks_source"}; 
$proxy_str = "";
if($use_proxy eq "yes")
{
$proxy_url = $ossim_conf::ossim_data->{"proxy_url"}; 
$ENV{http_proxy} = $proxy_url;
$proxy_user = quotemeta $ossim_conf::ossim_data->{"proxy_user"}; 
$proxy_password = quotemeta $ossim_conf::ossim_data->{"proxy_password"}; 
$proxy_str = "--proxy-user=$proxy_user --proxy-password=$proxy_password";
}

$update_url =~ s/\\/\\\\/g;
$update_url =~ s/\'/\\\'/g;


system("wget --quiet -O $update_location $proxy_str '$update_url'\n");
