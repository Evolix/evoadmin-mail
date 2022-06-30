#!/bin/bash
# This is a bash script, not sh compatible!

# vim: expandtab softtabstop=4 tabstop=4 shiftwidth=4 showtabline=2
    
PASSWORD='xxx'
DATE=$(date +"%d-%m-%Y")

while getopts "p:u:D:d" option ; do
case $option in

    p)
    READPASS=$OPTARG
    ;;

    u)
    USERIS=$OPTARG
    ;;
    
    D)
    DOMAINIS=$OPTARG
    ;;

    d)
    DEL='on'
    ;;

    *) 
    echo "script error"
    exit 1
    ;;
esac
done

if [ "$PASSWORD" != "$READPASS" ]; then
    echo "Invalid password"
    echo "Use -p <password>"
    exit 1
fi

# mv pseudo-homeDir to directory.<date> for deleted users
if [ "$DEL" == "on" ]; then
    if [[ -n $USERIS && -n $DOMAINIS && -e "/home/vmail/$DOMAINIS" && -e "/home/vmail/$DOMAINIS/$USERIS" ]]; then
        mv /home/vmail/$DOMAINIS/$USERIS /home/vmail/$DOMAINIS/$USERIS.$DATE
        chown -R root:root /home/vmail/$DOMAINIS/$USERIS.$DATE
        chmod -R 700 /home/vmail/$DOMAINIS/$USERIS.$DATE
    fi
    exit 0
fi

exit 1

