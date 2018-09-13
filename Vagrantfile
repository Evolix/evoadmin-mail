# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant::DEFAULT_SERVER_URL.replace('https://vagrantcloud.com')

# Load ~/.VagrantFile if exist, permit local config provider
vagrantfile = File.join("#{Dir.home}", '.VagrantFile')
load File.expand_path(vagrantfile) if File.exists?(vagrantfile)

Vagrant.configure('2') do |config|
  config.vm.synced_folder "./", "/vagrant", type: "rsync", rsync__exclude: [ '.vagrant', '.git', 'config/config.ini' ]

  config.vm.network "forwarded_port", guest: 80, host: 8080, auto_correct: true
  config.vm.network "forwarded_port", guest: 443, host: 8443, auto_correct: true

  $deps = <<SCRIPT
sed -e '/Rewrite/ s/^#*/#/' -i /etc/apache2/sites-available/evoadminmail.conf
service apache2 reload
[ -d /home/evoadmin-mail/www/htdocs/config ] && php /vagrant/scripts/config-migrate.php > /vagrant/config/config.ini
chmod 644 /vagrant/config/config.ini
chown vagrant:vagrant /vagrant/config/config.ini
rm -rf /home/evoadmin-mail/www/
ln -s /vagrant/ /home/evoadmin-mail/www
SCRIPT

  config.vm.define :packmail do |node|
    node.vm.hostname = "evoadmin-mail"
    node.vm.box = "vlaborie/packmail"

    node.vm.provision "deps", type: "shell", :inline => $deps

  end

end
