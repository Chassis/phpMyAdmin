# A Chassis extension to install and configure phpMyAdmin on your server
class phpmyadmin (
	$config,
	$path = '/vagrant/extensions/phpmyadmin',
	$database_user     = $config[database][user],
	$database_password = $config[database][password],
	$paths = $config[paths][base]
) {
	if ( ! empty( $config[disabled_extensions] ) and 'chassis/phpmyadmin' in $config[disabled_extensions] ) {
		$file = absent
		$link = absent
	} else {
		$file = file
		$link = link
	}

	file { "${path}/phpmyadmin/config.inc.php":
		ensure  => $file,
		content => template('phpmyadmin/config.inc.php.erb'),
	}

	# We need to make sure we handle custom paths otherwise WordPress will do a 404 for /phpmyadmin.
	if ( '.' == $paths ) {
			file { '/vagrant/phpmyadmin':
				ensure => $link,
				target => '/vagrant/extensions/phpmyadmin/phpmyadmin',
				notify => Service['nginx'],
			}
	} else {
			file { '/chassis/phpmyadmin':
				ensure => $link,
				target => '/vagrant/extensions/phpmyadmin/phpmyadmin',
				notify => Service['nginx'],
			}
	}
}
