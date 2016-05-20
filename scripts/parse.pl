#!/usr/bin/perl

use Term::ANSIColor;
use LWP::UserAgent;
use File::Basename;
use Socket;
use 5.10.0;
use threads;
no warnings 'experimental';
use List::Compare;
use Data::Dump qw(dump);
use DBI;


my $file =  $ARGV[0];
my $flux;
my @clean_list;

open $flux, $file or die "Could not open $file : $!";
my @file_content = <$flux>;

foreach $line (@file_content)
{
  # chomp $line;
  
  if ((!($line =~ /sat:/))&&(!($line =~ /0\.000/)))
  {
    my ($latitude, $longitude, $date) = $line =~ /lat: (.*) long: (.*) date: (.*)/sgi;
    my $date_formated = substr($date,0,4) . ":" . substr($date,4,2) . ":" . substr($date,6,2) . " " . substr($date,8,2) . ":" . substr($date,10,2) . ":" . substr($date,12,2);
    my $formated = "$latitude:$longitude:$date_formated:";
    push(@clean_list, $formated);
  }
}

foreach my $position (@clean_list)
{
  system("perl /var/www/scripts/functions.pl --save-position $position");
  sleep(2);
}