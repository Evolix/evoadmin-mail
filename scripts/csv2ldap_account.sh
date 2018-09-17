#!/bin/sh
#
# Script for import evoadmin account from stdin csv file
#
# CSV format must be : mail_address;password
#

set -eu

tmp_file=$(mktemp --suffix=.ldif)
gid_file=$(mktemp)

trap "rm ${tmp_file} ${gid_file}" 0

ldapvi --ldapsearch|grep -E "^gidNumber:"|awk '{ print $2 }'|sort -n|tail -n1 > "${gid_file}"

dc=$(grep "^base:" /root/.ldapvirc|awk '{ print $2 }')

get_gid() {
    domain=${1:-}
    gid=$(ldapvi --ldapsearch --read "cn=${domain},${dc}" 2>/dev/null|grep -E "^gidNumber:"|awk '{ print $2 }')
    if [ -z "${gid}" ]; then
        lastgid=$(cat "${gid_file}")
        gid=$((lastgid + 1))
        echo "${gid}" > "${gid_file}"
    cat >> "${tmp_file}" <<EOF
dn: cn=${domain},${dc}
cn: ${domain}
objectClass: postfixDomain
objectClass: posixGroup
postfixTransport: virtual:
isActive: TRUE
gidNumber: ${gid}

EOF
    fi
    echo "${gid}"
}

grep -v "#" /dev/stdin | while read account; do
    uid=$(echo "${account}"|cut -d';' -f1)
    name=$(echo "${uid}"|cut -d'@' -f1)
    domain=$(echo "${uid}"|cut -d'@' -f2)
    password=$(echo "${account}"|cut -d';' -f2)
    echo "${password}"|grep -qE "^{SSHA}" || password=$(slappasswd -s "${password}")
    gid=$(get_gid "${domain}")
    cat >> "${tmp_file}" <<EOF
dn: uid=${uid},cn=${domain},${dc}
uid: ${uid}
uidNumber: 5000
gidNumber: ${gid}
objectClass: posixAccount
objectClass: organizationalRole
objectClass: mailAccount
cn: ${name}
homeDirectory: /home/vmail/${domain}/${name}/
mailacceptinggeneralid: ${uid}
maildrop: ${uid}
userPassword: ${password}
isAdmin: FALSE
isActive: TRUE
courierActive: TRUE
accountActive: TRUE
authsmtpActive: TRUE
webmailActive: TRUE

EOF
done

ldapvi --add --in "${tmp_file}"
