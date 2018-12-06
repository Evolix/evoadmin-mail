# /etc/cron.d/evoadmin-mail

*/30 * * * * root [ -x /usr/lib/evoadmin-mail/get-size-no-quota.sh ] && EVOADMINMAIL_DIR=/var/lib/evoadmin-mail /usr/lib/evoadmin-mail/get-size-no-quota.sh
