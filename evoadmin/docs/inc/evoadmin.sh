#!/bin/sh

# vim: expandtab softtabstop=4 tabstop=4 shiftwidth=4 showtabline=2
    
PASSWORD='xxx'
DATE=$(date +"%d-%m-%Y")

while getopts "p:qu:g:sadv" option ; do
case $option in

    p)
    READPASS=$OPTARG
    ;;

    q)
    QUOTA='on'
    ;; 

    u)
    USERIS=$OPTARG
    ;;
    
    g)
    GROUPIS=$OPTARG
    ;;

    s)
    SIZE='on'
    ;;

    a)
    ADD='on'
    ;;
    
    d)
    DEL='on'
    ;;

    v)
    VIRTUAL='on'
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

# Mode virtuel : permet pour l'instant la création du répertoire d'un domaine
#                ex : evoadmin.sh -a -v -g example.com
if [ "$VIRTUAL" = "on" ]; then
    if [ "$ADD" == "on" ]; then
        if [[ -z $USERIS && $GROUPIS && ! -e "/home/vmail/$GROUPIS" ]]; then
            DOMAIN_DIR="/home/vmail/$GROUPIS"
            mkdir $DOMAIN_DIR
            # nécessite d'avoir un NSS/LDAP fonctionnel
            chown root:$GROUPIS $DOMAIN_DIR
            chmod 770 $DOMAIN_DIR
        fi
    fi

    exit 0
fi

if [ "$QUOTA" == "on" ]; then
    if [ -n "$USERIS" ]; then   
        NOW=`LANG=C quota $USERIS | tr -d "\n" | sed -e "s/^.*\/dev\///" | tr -s " " | cut -d" " -f2`
        LIMIT=`LANG=C quota $USERIS | tr -d "\n" | sed -e "s/^.*\/dev\///" | tr -s " " | cut -d" " -f3`
        echo "$NOW/$LIMIT"
        exit 0
    fi

    if [ -n "$GROUPIS" ]; then
        # no quota
        if LANG=C quota -g $GROUPIS | grep none > /dev/null; then
                echo "0/0"
                exit 0
        fi
        NOW=`LANG=C quota -g $GROUPIS | tr -d "\n" | sed -e "s/^.*\/dev\///" | tr -s " " | cut -d" " -f2`
        LIMIT=`LANG=C quota -g $GROUPIS | tr -d "\n" | sed -e "s/^.*\/dev\///" | tr -s " " | cut -d" " -f3`
        echo "$NOW/$LIMIT"
        exit 0
    fi

fi

if [ "$SIZE" == "on" ]; then
    NOW=`df | grep "/home" | tr -s " " | cut -d " " -f3`
    LIMIT=`df | grep "/home" | tr -s " " | cut -d " " -f2`
    echo "$NOW/$LIMIT"
    exit 0
fi

if [ "$ADD" == "on" ]; then
    if [[ -n $USERIS && $GROUPIS && ! -e "/home/$USERIS" ]]; then
        mkdir /home/$USERIS
        chmod 0700 /home/$USERIS 
        chown "$USERIS:$GROUPIS" /home/$USERIS
        setquota -u $USERIS 5000000 8000000 0 0 -a
        echo "Mail d'initialisation du compte." |\
            mail -s "Premier message" $USERIS@localhost
        exit 0
    fi
fi


if [ "$DEL" == "on" ]; then
    if [[ -n $USERIS && -e "/home/$USERIS" ]]; then
        mv /home/$USERIS /home/$USERIS.$DATE
        exit 0
    fi
fi

exit 1

