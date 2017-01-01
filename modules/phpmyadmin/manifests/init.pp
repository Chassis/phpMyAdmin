class phpmyadmin (
	$database_user,
	$database_password,
	$path = "/vagrant/extensions/phpmyadmin",
) {
	file { "${path}/phpmyadmin/config.inc.php":
		ensure  => file,
		content => template('phpmyadmin/config.inc.php.erb')
	}

	file { "/vagrant/phpmyadmin":
		ensure => link,
		target => "/vagrant/extensions/phpmyadmin/phpmyadmin",
		notify => Service['nginx'],
	}
}
