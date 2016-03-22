#!/bin/bash

# debug
#set -x

umask 022

tmp_file=$(mktemp)

tmp=$(mktemp -d)

cd $tmp

if [ -f $tmp_file ] ;
  then rm $tmp_file ;
fi

cd /var/tmp

sleep $[ $RANDOM / 1024 ]

wget -q -t 3 http://antispam00.evolix.net/spam/client.access -O $tmp_file
cp $tmp_file /etc/postfix/client.access
rm $tmp_file

wget -q -t 3 http://antispam00.evolix.net/spam/sender.access -O $tmp_file
cp $tmp_file /etc/postfix/sender.access
rm $tmp_file

wget -q -t 3 http://antispam00.evolix.net/spam/recipient.access -O $tmp_file
cp $tmp_file /etc/postfix/recipient.access
rm $tmp_file

wget -q -t 3 http://antispam00.evolix.net/spam/header_kill -O $tmp_file
cp $tmp_file /etc/postfix/header_kill
rm $tmp_file

wget -q -t 3 http://antispam00.evolix.net/spam/sa-blacklist.access  -O sa-blacklist.access
wget -q -t 3 http://antispam00.evolix.net/spam/sa-blacklist.access.md5  -O $tmp_file
if md5sum -c $tmp_file > /dev/null && [ -s sa-blacklist.access ] ; then
        cp sa-blacklist.access /etc/postfix/sa-blacklist.access
fi
rm sa-blacklist.access
rm $tmp_file

/usr/sbin/postmap hash:/etc/postfix/client.access
/usr/sbin/postmap hash:/etc/postfix/sender.access
/usr/sbin/postmap hash:/etc/postfix/recipient.access
/usr/sbin/postmap -r hash:/etc/postfix/sa-blacklist.access

wget -q -t 3 http://antispam00.evolix.net/spam/spamd.cidr  -O spamd.cidr
wget -q -t 3 http://antispam00.evolix.net/spam/spamd.cidr.md5  -O $tmp_file
if md5sum -c $tmp_file > /dev/null && [ -s spamd.cidr ] ; then
        cp spamd.cidr /etc/postfix/spamd.cidr
fi
rm spamd.cidr
rm $tmp_file

getfile() {
    wget -q -t 3 $1 -O $2
    wget -q -t 3 $1.md5 -O $tmp_file
    if md5sum -c $tmp_file > /dev/null ; then
        if test "$2" != "${2%.gz}" && gunzip -t $2 ; then
            gunzip -f $2
        fi
        if test -s ${2%.gz} ; then
            chown clamav:clamav ${2%.gz}
            cp -a ${2%.gz} /var/lib/clamav/
        fi
    fi
}

cd $tmp

# ClamAV
#for file in scam.ndb.gz phish.ndb.gz MSRBL-SPAM.ndb MSRBL-Images.hdb malware.com.br.ndb; do
#    getfile http://antispam00.evolix.net/spam/$file $file
#done
#chown -R clamav:clamav /var/lib/clamav
#/usr/sbin/invoke-rc.d clamav-daemon reload-database > /dev/null

# SpamAssassin
wget -q -t 3 http://antispam00.evolix.net/spam/evolix_rules.cf -O evolix_rules.cf
cp evolix_rules.cf /etc/spamassassin
/etc/init.d/spamassassin reload > /dev/null

rm -rf $tmp
