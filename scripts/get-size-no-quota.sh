#!/bin/sh

set -eu

DUC=$(command -v duc-nox || command -v duc)
EVOADMINMAIL_DIR=${EVOADMINMAIL_DIR:-'/home/evoadmin-mail'}
VMAIL_DIR=${VMAIL_DIR:-'/home/vmail'}
EVOADMINMAIL_GROUP=${EVOADMINMAIL_GROUP:-'evoadmin-mail'}
CSV_DIR="${EVOADMINMAIL_DIR}/quota"
IDX_FILE="${EVOADMINMAIL_DIR}/duc-vmail.idx"

mkdir -p "${CSV_DIR}" && chgrp "${EVOADMINMAIL_GROUP}" "${CSV_DIR}" && chmod 750 "${CSV_DIR}"

lsof "${IDX_FILE}" >/dev/null 2>&1 || nohup ionice -c3 "${DUC}" index -d "${IDX_FILE}" "${VMAIL_DIR}" >/dev/null 2>&1 &

timeout 10 sh -c -- "while [ ! -f ${IDX_FILE} ]; do sleep 1; done"

"${DUC}" ls --dirs-only -d "${IDX_FILE}" "${VMAIL_DIR}" | awk '{ print $2 ";" $1 ";-1" }' > "${CSV_DIR}/all.csv"
chgrp "${EVOADMINMAIL_GROUP}" "${CSV_DIR}/all.csv"
chmod 640 "${CSV_DIR}/all.csv"

cut -d ";" -f1 "${CSV_DIR}/all.csv" | while read domain; do
    if [ -d "${VMAIL_DIR}/${domain}" ]; then
        "${DUC}" ls --dirs-only -d "${IDX_FILE}" "${VMAIL_DIR}/${domain}" | awk '{ print $2 ";" $1 ";-1" }' > "${CSV_DIR}/${domain}.csv"
        chgrp "${EVOADMINMAIL_GROUP}" "${CSV_DIR}/${domain}.csv"
        chmod 640 "${CSV_DIR}/${domain}.csv"
    fi
done
