# Evoadmin mail

Evoadmin mail is a Web Interface for manage an LDAP directory designed for mail accounts.

## Install

Evoadmin-mail requirements are an LDAP server, a Web server and PHP. See [INSTALL](docs/install.md) for configure them.

Multiples services can be configured to use the LDAP directory managed by Evoadmin-mail :

- TODO

## Test

You can deploy a test environmment with Vagrant :

~~~
vagrant up
~~~

Evoadmin mail respond to evoadminmail.packmail.example.com domain on localhost, so update your /etc/hosts :

~~~
127.0.0.1   evoadminmail.packmail.example.com
~~~

Congratulation, Evoadmin mail is now accessible throught https://evoadminmail.packmail.example.com:8443

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

This project is an [Evolix](https://evolix.com) project and is licensed under AGPLv3, see the [LICENSE](LICENSE) file for details.

Evolix trademark and logo are not freely reusable and are protected by copyright.
