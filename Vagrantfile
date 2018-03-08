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
DEBIAN_FRONTEND=noninteractive apt-get -yq install ansible git
ansible-galaxy install -r /vagrant/test/ansible/requirements.yml
echo "[defaults]" > '/vagrant/test/ansible/ansible.cfg'
echo "roles_path = /etc/ansible/roles/evolix" >> '/vagrant/test/ansible/ansible.cfg'
> /etc/hosts
SCRIPT

  config.vm.define :packmail do |node|
    node.vm.hostname = "evoadmin-mail"
    node.vm.box = "debian/stretch64"

    node.vm.provision "deps", type: "shell", :inline => $deps
    node.vm.provision "ansible", type: "ansible_local" do |ansible|
      ansible.provisioning_path = "/vagrant/test/ansible"
      ansible.playbook = "evoadmin-mail.yml"
      ansible.install_mode = ":default"
      ansible.raw_arguments = [
        "--become"
      ]
    end

  end

end
