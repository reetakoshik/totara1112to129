Description of import of Horde libraries

# Download the Horde git repository. You will probably want to keep this
  around for future updates:
    git clone git@github.com:horde/horde.git
# Checkout the version of horde you require:
    git checkout horde-5.2.7
# Copy the following script and store it on /tmp, change it's execute bit, and run it, passing
  in your path to Horde (the directory you've cloned the repository):
    /tmp/copyhorde.sh ~/git/ext/horde/
# MDL-52361 patched for PHP7 compatibility, after upgrade make sure it's updated upstream and remove this line
# TL-16004 Suppressed php 7.2 deprecation message on all uses of each()
# TL-15981 Fix use of PHP 7.2 deprecated default parameter in idn_to_utf8() and idn_to_ascii(), use INTL_IDNA_VARIANT_UTS46
# lib/horde/framework/Horde/Mail/Rfc822.php fix deprecated continue inside switch

====
#!/bin/sh

source=$1/framework
target=./lib/horde

echo "Copy Horde modules from $source to $target"

modules="Crypt_Blowfish Exception Imap_Client Mail Mime Secret Socket_Client Stream Stream_Filter Stream_Wrapper Support Text_Flowed Translation Util"

rm -rf $target/locale $target/framework
mkdir -p $target/locale $target/framework/Horde

for module in $modules
do
  echo "Copying $module"
  cp -Rf $source/$module/lib/Horde/* $target/framework/Horde
  locale=$source/$module/locale
  if [ -d $locale ]
  then
    cp -Rf $locale/* $target/locale
  fi
done
