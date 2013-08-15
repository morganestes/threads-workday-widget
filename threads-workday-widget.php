<?php
/*
Plugin Name: Threads OKC Workdays
Plugin URI: http://threadsokc.github.io/workday-widget
Description: Adds a widget with the upcoming work day.
Version: 0.1.0
Author: morganestes
Author URI: http://www.morganestes.me
License: GPLv2 or later
*/


namespace ThreadsOKC\Workday;

use ThreadsOKC\Workday\Calendar;
use WP_Widget;

include_once __DIR__ . '/create-ical.php';


/**
 * Adds Threads_Widget widget.
 */
class Threads_Widget extends \WP_Widget {
	public $plugin_dir;
	public $plugin_url;
	public $plugin_name;

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'threads_widget', // Base ID
			'Threads Workdays', // Name
			array( 'description' => __( 'Display the next scheduled workday', 'threadsokc' ), ) // Args
		);

		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_name = plugin_basename( __DIR__ );

	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$title      = apply_filters( 'widget_title', $instance['title'] );
		$date       = $instance['date'];
		$extra_info = apply_filters( 'widget_text', $instance['extra_info'] );

		$display_date = date( 'F d, Y', strtotime( $date ) );
		$ics_date     = date( 'Ymd', strtotime( $date ) );

		$event_info  = array(
			'datestart'   => strtotime( $ics_date . 'T140000' ),
			'dateend'     => strtotime( $ics_date . 'T170000' ),
			'address'     => esc_html( '2221 E. Memorial Rd., Edmond, OK 73013', 'threadsokc' ),
			'uri'         => esc_url( 'http://www.threadsokc.org/events.html' ),
			'filename'    => "threadsokc-workday-{$date}.ics",
			'summary'     => __( 'Threads OKC Workday', 'threadsokc' ),
			'description' => __( '', 'threadsokc' ),
		);
		$ics_file    = plugin_dir_url( __FILE__ ) . 'create-ical.php';
		$ics_url     = add_query_arg( $event_info, $ics_file );
		$cal_img     = $this->plugin_url . 'calendar_add.png';
		$nonce       = wp_create_nonce( $this->plugin_name );
		$nonce_field = wp_nonce_field( 'build_calendar', "{$this->plugin_name}_nonce", false, false );

		$ics_form = <<<HTML
		<form style="display: inline" method="post" name="build-ics" action="#">
<input type="hidden" name="datestart" value="$event_info[datestart]" />
<input type="hidden" name="dateend" value="$event_info[dateend]" />
<input type="hidden" name="address" value="$event_info[address]" />
<input type="hidden" name="uri" value="$event_info[uri]" />
<input type="hidden" name="filename" value="$event_info[filename]" />
<input type="hidden" name="summary" value="$event_info[summary]" />
<input type="hidden" name="description" value="$event_info[description]" />
{$nonce_field}
<input title="Add to calendar" style="border: none; vertical-align: bottom;" type="image" value="build_calendar" src="$cal_img" />
</form>
HTML;

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
		echo "<strong class='date'>$display_date</strong>";
		echo $ics_form;
		if ( ! empty( $extra_info ) ) {
			echo "<p>$extra_info</p>";
		}
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 *
	 * @return string|void
	 */
	public function form( $instance ) {
		if ( $instance ) {
			$title      = esc_attr( $instance['title'] );
			$date       = esc_attr( $instance['date'] );
			$extra_info = esc_textarea( $instance['extra_info'] );
		}
		else {
			$title      = '';
			$date       = '2010-01-01';
			$extra_info = '';
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label><br>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for "<?php echo $this->get_field_name( 'date' ); ?>"><?php _e( 'Date:' ); ?></label><br>
			<input class="widefat" type="date" id="<?php echo $this->get_field_id( 'date' ); ?>" name="<?php echo $this->get_field_name( 'date' ); ?>" value="<?php echo $date; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_name( 'extra_info' ); ?>"><?php _e( 'Extra info:' ); ?></label><br>
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'extra_info' ); ?>" name="<?php echo $this->get_field_name( 'extra_info' ); ?>"><?php echo $extra_info; ?></textarea>
		</p>
	<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance               = array();
		$instance['title']      = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['date']       = ( ! empty( $new_instance['date'] ) ) ? strip_tags( $new_instance['date'] ) : '';
		$instance['extra_info'] = ( ! empty( $new_instance['extra_info'] ) ) ? strip_tags( $new_instance['extra_info'] ) : '';

		return $instance;
	}

} // class Threads_Widget

add_action( 'widgets_init', function () {
	register_widget( 'ThreadsOKC\Workday\Threads_Widget' );
} );

add_action( 'init', function () {
	if ( ( 'POST' == $_SERVER['REQUEST_METHOD'] )
			&& isset( $_POST['threads-next-workday_nonce'] )
	) {
		$calendar = new Calendar();
		$calendar->create_ics();
	}
} );

