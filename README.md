# Evoadmin Mail

Evoadmin-mail is a web interface to an LDAP directory designed for
mail accounts.

## Install

Evoadmin-mail requirements are an LDAP server, a web server and
PHP. See [INSTALL](docs/install.md) for instructions.

Multiples services can be configured to use the LDAP directory
managed by Evoadmin-mail:

- TODO

## Test

You can deploy a test environment with Vagrant:

~~~
vagrant up
~~~

Evoadmin-mail uses the evoadminmail.packmail.example.com domain
on localhost, so update your /etc/hosts:

~~~
127.0.0.1   evoadminmail.packmail.example.com
~~~

Congratulation, Evoadmin mail is now accessible through
https://evoadminmail.packmail.example.com:8443

### Authentication

The default admin user is "evoadmin", the password is randomly
generated and can be recovered from LDAP:

~~~
vagrant ssh
sudo -i
ldapvi --ldapsearch "(uid=evoadmin)" | grep userPassword | awk '{ print $2 }'
~~~

### Deployment

Launch rsync-auto in a terminal to automatically synchronise your
local code with the Vagrant VM:

~~~
vagrant rsync-auto
~~~

## License

This is an [Evolix](https://evolix.com) project and is licensed
under the AGPLv3, see the [LICENSE](LICENSE) file for details.

The Evolix trademark and logo are not freely reusable and are
protected by copyright.
