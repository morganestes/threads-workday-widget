<?php
// Variables used in this script:
//   $summary     - text title of the event
//   $datestart   - the starting date (in seconds since unix epoch)
//   $dateend     - the ending date (in seconds since unix epoch)
//   $address     - the event's address
//   $uri         - the URL of the event (add http://)
//   $description - text description of the event
//   $filename    - the name of this file for saving (e.g. my-event-name.ics)
//
// Notes:
//  - the UID should be unique to the event, so in this case I'm just using
//    uniqid to create a uid, but you could do whatever you'd like.
//
//  - iCal requires a date format of "yyyymmddThhiissZ". The "T" and "Z"
//    characters are not placeholders, just plain ol' characters. The "T"
//    character acts as a delimeter between the date (yyyymmdd) and the time
//    (hhiiss), and the "Z" states that the date is in UTC time. Note that if
//    you don't want to use UTC time, you must prepend your date-time values
//    with a TZID property. See RFC 5545 section 3.3.5
//
//  - The Content-Disposition: attachment; header tells the browser to save/open
//    the file. The filename param sets the name of the file, so you could set
//    it as "my-event-name.ics" or something similar.
//
//  - Read up on RFC 5545, the iCalendar specification. There is a lot of helpful
//    info in there, such as formatting rules. There are also many more options
//    to set, including alarms, invitees, busy status, etc.
//
//      https://www.ietf.org/rfc/rfc5545.txt
namespace ThreadsOKC\Workday;

/**
 * Class Calendar
 *
 * @package ThreadsOKC\Workday
 */
class Calendar {

	private $summary = '';
	private $date_start = '';
	private $date_end = '';
	private $address = '';
	private $uri = '';
	private $description = '';
	private $filename = '';


	public function __construct( $time_zone = 'America/Chicago' ) {

		$this->summary     = wp_kses_stripslashes( $_POST['summary'] );
		$this->date_start  = wp_kses_stripslashes( $_POST['datestart'] );
		$this->date_end    = wp_kses_stripslashes( $_POST['dateend'] );
		$this->address     = wp_kses_stripslashes( $_POST['address'] );
		$this->uri         = esc_url_raw( $_POST['uri'] );
		$this->description = wp_kses_stripslashes( $_POST['description'] );
		$this->filename    = wp_kses_stripslashes( $_POST['filename'] );

		date_default_timezone_set( $time_zone );
	}


	/**
	 * Converts a unix timestamp to an ics-friendly format.
	 * NOTE: "Z" means that this timestamp is a UTC timestamp. If you need
	 * to set a locale, remove the "\Z" and modify DTEND, DTSTAMP and DTSTART
	 * with TZID properties (see RFC 5545 section 3.3.5 for info).
	 *
	 * @param $timestamp
	 *
	 * @return bool|string
	 */
	private function date_to_cal( $timestamp ) {
		return date( 'Ymd\THisP', $timestamp );
	}

// 3. Echo out the ics file's contents
	public function create_ics() {
		if ( empty( $_POST ) || ! wp_verify_nonce( $_POST['threads-next-workday_nonce'], 'build_calendar' ) ) {
			print 'Sorry, your nonce did not verify.';
			wp_die( 'Sorry, your nonce did not verify.', 'Error' );
		}

		$br          = "\n";
		$date_start  = $this->date_to_cal( $this->date_start );
		$date_end    = $this->date_to_cal( $this->date_end );
		$uid         = uniqid();
		$time_stamp  = $this->date_to_cal( time() );
		$location    = $this->address;
		$description = $this->description;
		$uri         = $this->uri;
		$summary     = $this->summary;

		$ical = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTSTART:{$date_start}
DTEND:{$date_end}
UID:{$uid}
DTSTAMP:{$time_stamp}
LOCATION:{$location}
DESCRIPTION:{$description}
URL;VALUE=URI:{$uri}
SUMMARY:{$summary}
END:VEVENT
END:VCALENDAR';
EOF;

		// 1. Set the correct headers for this file
		header( 'Content-type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $this->filename );

		ob_start();
		echo $ical;
		ob_end_flush();
		exit;
	}
}
