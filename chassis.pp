$vconfig = sz_load_config('/vagrant')

class { 'phpmyadmin':
	path => '/vagrant/extensions/phpmyadmin',
	database_user     => $vconfig[database][user],
	database_password => $vconfig[database][password],
}