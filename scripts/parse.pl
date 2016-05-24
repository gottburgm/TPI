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
my $output_file = $ARGV[1];

read_file();

sub read_file {
  my $flux;
  my @clean_list;

  open $flux, $file or die "Could not open $file : $!";
  my @file_content = <$flux>;

  foreach $line (@file_content)
  {
    chomp $line;
    
    if ((!($line =~ /sat:/))&&(!($line =~ /0\.000/)))
    {
      my ($latitude, $longitude, $date) = $line =~ /lat: (.*) long: (.*) date: (.*)/sgi;
      my $formated = "$latitude:$longitude:$date";
      push(@clean_list, $formated);
    }
  }
  write_file(@clean_list);
  insert_into_db(@clean_list);
}

sub insert_into_db {
  my @position_strings = @_;
  
  foreach my $position (@position_strings)
  {
    system("perl ./functions.pl --save-position $position");
    sleep(2);
  }
}

sub write_file {
  my @lines_to_write = @_;
  my $flux;
  
  open(my $flux, '>',  $output_file) or die "Could not open file '$output_file' $!";
  
  foreach $line (@lines_to_write)
  {
    print $flux "$line\n";
  }
  
  close $flux;
}