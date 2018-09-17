#!/bin/sh
#
# Script for import evoadmin alias from stdin csv file
#
# CSV format must be : name;mail_address;alias
#

set -eu

tmp_file=$(mktemp --suffix=.ldif)
tmp_dir=$(mktemp --directory)
gid_file=$(mktemp)

trap "rm -r ${tmp_file} ${tmp_dir} ${gid_file}" 0

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
    name=$(echo "${account}"|cut -d';' -f1)
    uid=$(echo "${account}"|cut -d';' -f2)
    alias=$(echo "${account}"|cut -d';' -f3)
    domain=$(echo "${uid}"|cut -d'@' -f2)
    gid=$(get_gid "${domain}")
    [ -f "${tmp_dir}/${name}" ] || cat > "${tmp_dir}/${name}" <<EOF

dn: cn=${name},cn=${domain},${dc}
cn: ${name}
objectClass: mailAlias
isActive: TRUE
EOF
    grep -q "^mailacceptinggeneralid: ${uid}$" "${tmp_dir}/${name}" || echo "mailacceptinggeneralid: ${uid}" >> "${tmp_dir}/${name}"
    grep -q "^maildrop: ${alias}$" "${tmp_dir}/${name}" || echo "maildrop: ${alias}" >> "${tmp_dir}/${name}"
done

cat "${tmp_file}" "${tmp_dir}"/*|ldapvi --add --in
