# Evoadmin mail

Evoadmin mail is a Web Interface for manage an LDAP directory designed for mail accounts.

## Install

See [INSTALL](docs/INSTALL.md).

## Test

You can deploy a test environmment with Vagrant :

~~~
vagrant up
~~~

Evoadmin mail respond to evoadminmail.evoadmin-mail.example.com domain on localhost, so update your /etc/hosts :

~~~
127.0.0.1   evoadminmail.evoadmin-mail.example.com
~~~

Congratulation, Evoadmin mail is now accessible throught https://evoadminmail.evoadmin-mail.example.com:8443

### Authentication

Default admin user is "evoadmin", password is randomly generated and can be recovered from LDAP :

~~~
vagrant ssh
sudo -i
ldapvi --ldapsearch "(uid=evoadmin)" | grep userPassword | awk '{ print $2 }'
~~~

### Deployment

Launch rsync-auto in a terminal for automatic synchronisation of your local code with Vagrant VM :

~~~
vagrant rsync-auto
~~~

## License

This project is an [Evolix](https://evolix.com) project and is licensed under GPLv2+, see the [LICENSE](LICENSE) file for details.

Evolix trademark and logo are not freely reusable and are protected by copyright.
