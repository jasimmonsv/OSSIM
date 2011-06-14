#!/usr/bin/perl

$columns['acid_event'][17] = "`sid`,`cid`,`timestamp`,`ip_src`,`ip_dst`,`ip_proto`,`layer4_sport`,`layer4_dport`,`ossim_type`,`ossim_priority`,`ossim_reliability`,`ossim_asset_src`,`ossim_asset_dst`,`ossim_risk_c`,`ossim_risk_a`,`plugin_id`,`plugin_sid`";
$columns['acid_event'][18] = "`sid`,`cid`,`timestamp`,`ip_src`,`ip_dst`,`ip_proto`,`layer4_sport`,`layer4_dport`,`ossim_type`,`ossim_priority`,`ossim_reliability`,`ossim_asset_src`,`ossim_asset_dst`,`ossim_risk_c`,`ossim_risk_a`,`plugin_id`,`plugin_sid`,`tzone`";
$columns['acid_event'][19] = "`sid`,`cid`,`timestamp`,`ip_src`,`ip_dst`,`ip_proto`,`layer4_sport`,`layer4_dport`,`ossim_type`,`ossim_priority`,`ossim_reliability`,`ossim_asset_src`,`ossim_asset_dst`,`ossim_risk_c`,`ossim_risk_a`,`plugin_id`,`plugin_sid`,`tzone`,`ossim_correlation`";

$columns['extra_data'][15] = "`sid`,`cid`,`filename`,`username`,`password`,`userdata1`,`userdata2`,`userdata3`,`userdata4`,`userdata5`,`userdata6`,`userdata7`,`userdata8`,`userdata9`,`data_payload`";
$columns['extra_data'][16] = "`sid`,`cid`,`filename`,`username`,`password`,`userdata1`,`userdata2`,`userdata3`,`userdata4`,`userdata5`,`userdata6`,`userdata7`,`userdata8`,`userdata9`,`data_payload`,`context`";


while (<STDIN>) {
    next if /acid_event_input/i;
    my $line = $_;
    # Get line with insert in acid_event
    if ($line =~ /INSERT\s+IGNORE\s+INTO\s+\`acid\_event\`\s+VALUES\s+\(([^\)]+)\)/) {
        # Count columns
        my $values = $1;
        my @values_cols = split(/\,/,$values);
        my $num_cols = @values_cols;
        if ($num_cols == 20 && $values =~ /\d+\,\d+\,\'\d\d\d\d\-\d\d\-\d\d \d\d\:\d\d\:\d\d\',0\,0\,/) {
            $line =~ s/(\d+\,\d+\,\'\d\d\d\d\-\d\d\-\d\d \d\d\:\d\d\:\d\d\'),0\,0/$1/g;
            $values =~ s/(\d+\,\d+\,\'\d\d\d\d\-\d\d\-\d\d \d\d\:\d\d\:\d\d\'),0\,0/$1/;
            @values_cols = split(/\,/,$values);
            $num_cols = @values_cols;
        }
        if (defined $columns['acid_event'][$num_cols]) {
            my $replace_string = $columns['acid_event'][$num_cols];
            $line =~ s/INSERT\s+IGNORE\s+INTO\s+\`acid\_event\`\s+VALUES/INSERT IGNORE INTO `acid_event` ($replace_string) VALUES/;
        }
    }
	# Get line with insert in extra_data
	elsif ($line =~ /INSERT\s+IGNORE\s+INTO\s+\`extra\_data\`\s+VALUES\s+/ && $line !~ /INSERT\s+IGNORE\s+INTO\s+\`extra\_data\`\s+VALUES\s+\(.*,\d+\)\;$/) {
        # Count columns
        my $num_cols = 15;
        if (defined $columns['extra_data'][$num_cols]) {
            my $replace_string = $columns['extra_data'][$num_cols];
            $line =~ s/INSERT\s+IGNORE\s+INTO\s+\`extra\_data\`\s+VALUES/INSERT IGNORE INTO `extra_data` ($replace_string) VALUES/;
        }
    }
    print $line;
}
# $values =~ s/\"[^\"]+\"//g; # Clean strings before split by ','