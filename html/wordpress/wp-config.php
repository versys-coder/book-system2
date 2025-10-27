<?php
// Begin AIOWPSEC Firewall
if (file_exists('/var/www/html/wordpress/aios-bootstrap.php')) {
	include_once('/var/www/html/wordpress/aios-bootstrap.php');
}
// End AIOWPSEC Firewall
//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', 'BK6rCnqx9kv13tmN2NQD7Amz2kVmbBlALvGGEsBgNabzWE9gWne3lAKgCnCRH8eb');
//END Really Simple Security key
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress_db' );

/** Database username */
define( 'DB_USER', 'stas' );

/** Database password */
define( 'DB_PASSWORD', 'potofvo15' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'wb]1+0s;4i1uuM%^-iC$R>,#$M6ea%@/:H=a<9SZYyrybL)?UfEh,0b4>% /N.+O' );
define( 'SECURE_AUTH_KEY',  'CkJ^F>{|.$FjsCG#y4 ]4yd{%/II^VP/Uf/h,*C|r$ZR IImlw_+o06$J2zni!yC' );
define( 'LOGGED_IN_KEY',    '+iG;N_Y4nxY{|FS#Tt{HfckyC3b;*Kq3-7uRN0zi>aq9KoXh^E,n6$(I~5Kl J,<' );
define( 'NONCE_KEY',        'mJ@z}?D8TiZzz- ,>Y+c*-Pj:NX10d6$mY,+p(jloQZJw)ia`stwq%<ab }D%XV~' );
define( 'AUTH_SALT',        'hBc~_szU!k!!~p>`E)}_>)6w@UQGb6:oy+[_E.9>:l7YU}oUaXgZLwiTp}3p!DsW' );
define( 'SECURE_AUTH_SALT', '>>G8:lx<y!qh%-55dcs,E)qm+I9EiY?H{WK4FrMmd:B1Q9/GdGVESH)m&aRCG+Og' );
define( 'LOGGED_IN_SALT',   '6(H>!*Y;)xo(B]@|mWWc8zqK}2+`2D3IJ%+[e0U{pft(YL}(%v_j$r)zU4HPu,Sd' );
define( 'NONCE_SALT',       'SHw^uyxp5EOKHJm32mY!l@iU/PZtl.t@2T)vw1KiK3j$;P;k.Wt>6^0psSgogF#.' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';