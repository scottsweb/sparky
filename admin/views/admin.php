<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   sparky
 * @author    Scott Evans <git@scott.ee>
 * @license   GPL-2.0+
 * @link      http://scott.ee
 * @copyright 2014 Scott Evans
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'sparky_options' ); ?>
		<?php do_settings_sections( 'sparky' ); ?>
		<?php submit_button(); ?>
	</form>

</div>
