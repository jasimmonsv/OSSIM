#!/usr/bin/perl
use utf8;
no utf8;
use HTML::Entities;
use Data::Dumper;

# 2010/01/25 DK
# (c) Alienvault

# \
# \n

# Get a valid google translation api key from google

#$api_key = "AIzaSyAZZk0oyH8qPRw6AZXEyyxy9ECQk8zgVAM";
$api_key = "AIzaSyBttmec0vD6rexKc6d9kquDt6YXCRWa0rw";
$debug = 1;

# 

sub usage(){

print "\n$0 source.po destination.po target_language\n\n";
print "List languages with '-l' as source.po\n";
print "\n\n\n";

exit;

}


sub google_translate($ $){
$translate_str = shift;
$language = shift;
$out = "";
$orig_translate_str = $translate_str;

$translate_str =~ s/"/6T6T5R5R4S4S/g;
$translate_str =~ s/\\/6Z6Z5R5R4Z4Z/g;

if($debug){print "Translate: $translate_str\n";} 
 
open(CMD,"wget -q --no-check-certificate -t 2 -erobots=off -l 0 -T 15 -U Mozilla \"https://www.googleapis.com/language/translate/v2?key=$api_key&q=$translate_str&source=en&target=$language&callback=handleResponse&prettyprint=true\" -O - | grep translatedText |");
#  print "wget -q --no-check-certificate -U Mozilla \"https://www.googleapis.com/language/translate/v2?key=$api_key&q=$translate_str&source=en&target=$language&callback=handleResponse&prettyprint=true\" -O - | grep translatedText |";

while(<CMD>){
if(/translatedText":\s*"([^"]+)"/){
$out = $1;
}
}
close CMD;

$out =~ s/6T6T5R5R4S4S/"/g;
$out =~ s/6Z6Z5R5R4Z4Z/\\/g;

if($out eq ""){
return $orig_translate_str;
}else{
return $out;
}
}


sub available_languages(){
# Update this list from http://code.google.com/apis/language/translate/v1/reference.html#LangNameArray
$languages = << "END";

Available list of target languages. Use the short version as 'target_language' param.

var google.language.Languages = {
  'AFRIKAANS' : 'af',
  'ALBANIAN' : 'sq',
  'AMHARIC' : 'am',
  'ARABIC' : 'ar',
  'ARMENIAN' : 'hy',
  'AZERBAIJANI' : 'az',
  'BASQUE' : 'eu',
  'BELARUSIAN' : 'be',
  'BENGALI' : 'bn',
  'BIHARI' : 'bh',
  'BRETON' : 'br',
  'BULGARIAN' : 'bg',
  'BURMESE' : 'my',
  'CATALAN' : 'ca',
  'CHEROKEE' : 'chr',
  'CHINESE' : 'zh',
  'CHINESE_SIMPLIFIED' : 'zh-CN',
  'CHINESE_TRADITIONAL' : 'zh-TW',
  'CORSICAN' : 'co',
  'CROATIAN' : 'hr',
  'CZECH' : 'cs',
  'DANISH' : 'da',
  'DHIVEHI' : 'dv',
  'DUTCH': 'nl',  
  'ENGLISH' : 'en',
  'ESPERANTO' : 'eo',
  'ESTONIAN' : 'et',
  'FAROESE' : 'fo',
  'FILIPINO' : 'tl',
  'FINNISH' : 'fi',
  'FRENCH' : 'fr',
  'FRISIAN' : 'fy',
  'GALICIAN' : 'gl',
  'GEORGIAN' : 'ka',
  'GERMAN' : 'de',
  'GREEK' : 'el',
  'GUJARATI' : 'gu',
  'HAITIAN_CREOLE' : 'ht',
  'HEBREW' : 'iw',
  'HINDI' : 'hi',
  'HUNGARIAN' : 'hu',
  'ICELANDIC' : 'is',
  'INDONESIAN' : 'id',
  'INUKTITUT' : 'iu',
  'IRISH' : 'ga',
  'ITALIAN' : 'it',
  'JAPANESE' : 'ja',
  'JAVANESE' : 'jw',
  'KANNADA' : 'kn',
  'KAZAKH' : 'kk',
  'KHMER' : 'km',
  'KOREAN' : 'ko',
  'KURDISH': 'ku',
  'KYRGYZ': 'ky',
  'LAO' : 'lo',
  'LATIN' : 'la',
  'LATVIAN' : 'lv',
  'LITHUANIAN' : 'lt',
  'LUXEMBOURGISH' : 'lb',
  'MACEDONIAN' : 'mk',
  'MALAY' : 'ms',
  'MALAYALAM' : 'ml',
  'MALTESE' : 'mt',
  'MAORI' : 'mi',
  'MARATHI' : 'mr',
  'MONGOLIAN' : 'mn',
  'NEPALI' : 'ne',
  'NORWEGIAN' : 'no',
  'OCCITAN' : 'oc',
  'ORIYA' : 'or',
  'PASHTO' : 'ps',
  'PERSIAN' : 'fa',
  'POLISH' : 'pl',
  'PORTUGUESE' : 'pt',
  'PORTUGUESE_PORTUGAL' : 'pt-PT',
  'PUNJABI' : 'pa',
  'QUECHUA' : 'qu',
  'ROMANIAN' : 'ro',
  'RUSSIAN' : 'ru',
  'SANSKRIT' : 'sa',
  'SCOTS_GAELIC' : 'gd',
  'SERBIAN' : 'sr',
  'SINDHI' : 'sd',
  'SINHALESE' : 'si',
  'SLOVAK' : 'sk',
  'SLOVENIAN' : 'sl',
  'SPANISH' : 'es',
  'SUNDANESE' : 'su',
  'SWAHILI' : 'sw',
  'SWEDISH' : 'sv',
  'SYRIAC' : 'syr',
  'TAJIK' : 'tg',
  'TAMIL' : 'ta',
  'TATAR' : 'tt',
  'TELUGU' : 'te',
  'THAI' : 'th',
  'TIBETAN' : 'bo',
  'TONGA' : 'to',
  'TURKISH' : 'tr',
  'UKRAINIAN' : 'uk',
  'URDU' : 'ur',
  'UZBEK' : 'uz',
  'UIGHUR' : 'ug',
  'VIETNAMESE' : 'vi',
  'WELSH' : 'cy',
  'YIDDISH' : 'yi',
  'YORUBA' : 'yo',
  'UNKNOWN' : ''
};


END

print $languages;
print "\n\n";

exit;

}

if($ARGV[0] eq "-l"){
available_languages();
}


if(!$ARGV[2]){

usage();

}

if($ARGV[0] eq $ARGV[1]){

print "This won't work if you overwrite the output file as you input it... go get some computer manual";
system("touch RTFM_RTFM_RTFM\n");
exit;

}

open(IPUT, "<$ARGV[0]") or die "Can't open $ARGV[0] for input: $!";
@iput = <IPUT>;
close IPUT;
open(OPUT, ">$ARGV[1]") or die "Can't open $ARGV[1] for input: $!";

#### Start doing stuff

$inside_msgid = 0;
$inside_msgstr = 0;
$translate_str = 0;

$i=0;
$j=0;

$size = $#iput + 1;

while ($i < $size) {
    $line = $iput[$i];
    if ($line =~ /^#/) { print OPUT "$line"; $i++; next; }
 
    chop($line);
    $msgid = '';
    $msgstr = '';

    if($line =~ /^msgid\s*"(.*)"/){
        if($debug){print "Entering parsing\n";}
        $j++;
        $msgid = $1;
        $inside_msgid = 1;
    while($inside_msgid && $i < $size){
        $i++;
        while($i < $size){
            $next = $iput[$i];
            chop($next);
            if($next =~ /^msgstr\s*""$/){
                $second_line = $iput[$i+1];
                chop($second_line);
                if ($second_line =~ /^"(.+)"$/){
                    if($debug){print "Got a translated string\n";}
                    $inside_msgid = 0;
                    $inside_msgstr = 1;
                    $translate_str = 0;
                    $msgstr = $1;
                    $i++;
                }
                else {
                    if($debug){print "No translation, move over to translate\n";}
                    $inside_msgid = 0;
                    $inside_msgstr = 0;
                    $translate_str = 1;
                }
                last;
            } elsif ($next =~ /^msgstr\s*"(.+)"$/){
                if($debug){print "Got a translated string\n";}
                $inside_msgid = 0;
                $inside_msgstr = 1;
                $translate_str = 0;
                $msgstr = $1;
                last;
            } elsif ($next =~ /^"(.*)"$/){
                if($debug){print "msgid continues\n";}
                $msgid .= $1;
            } else {
                print "This should never be reached, malformed .po?\n";
            }
            $i++;
        }
        $i++;
        while($inside_msgstr && $i < $size){
            while($i < $size){
                $next = $iput[$i];
                chop($next);
                if($next =~ /^"(.*)"$/){
                    if($debug){print "msgstr continues\n";}
                    $msgstr .= "\"\n\"".$1;
                } elsif($next eq "") {
                    if($debug){print "msgstr done\n";}
                    $inside_msgstr = 0;	
                    last;
                } else {
                    print "Error inside msgstr: $next\n";
                }
                $i++;
            }
        }

        if($translate_str && ($msgid ne "")){
        # Translation hook
        $msgstr = google_translate($msgid,$ARGV[2]);
        utf8::decode($msgstr);  
        $msgstr = encode_entities($msgstr);
        $msgstr =~ s/\\u\u003c/</g;
        $msgstr =~ s/\\u\u003d/=/g;
        $msgstr =~ s/\\u\u003e/>/g;
    #   $msgstr =~ s/\\\\/\\/g;
        $msgstr =~ s/\\ n/\\n/g;
        $msgstr =~ s/\\"/'/g;
        $msgstr =~ s/\\ N/\\n/g;
        $msgstr =~ s/\\ " /\\"/g;
        $msgstr =~ s/ \\ //g;
        $msgstr =~ s/ " /\\"/g;
        $msgstr =~ s/&quot;/"/g;
    #   $msgstr =~ s/\\u//g;
    
        print OPUT "#Translated by Google API\n";
        }

    if($debug){
        print "########################### WRITE TRANSLATION############\n";
    }
    print OPUT "msgid \"$msgid\"\n";
    print OPUT "msgstr \"$msgstr\"\n\n";

    #   $j++; if($j>1000){ exit;}
        }
    } else {

    if($debug){print "Printing another line\n";}
    
    }

    $inside_msgid = 0;
    $inside_msgstr = 0;
    $translate_str = 0;
    
    if ($line !~ /^#/)  { $i++; }
}

#### Cleanup

#close IPUT;
close OPUT;
