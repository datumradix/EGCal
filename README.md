EGCal - A Google Calendar Extension for Yii
=============

About
-----

EGCal is a simple extension that enables Yii Applications to communicate with Google Calendar.

How it Works
------------

EGCal works by making a autorization request to Google Calendar via ClientLogin. All subsequent requests are then
made through a single connection.

Requirements
------------
PHP 5.3+

Usage
=====

### Importing the Class

	Yii::import('application.extensions.EGCal.EGCal');

### Instantiation

You have a couple of options here. All you need to do to get it working is to call:

	$cal = new EGCal('username@gmail.com', 'gmail-password');

If you would like EGCal to provide debugging text:
	
	$cal = new EGCal('username@gmail.com', 'gmail-password', TRUE);

By default, EGCal uses your application name (Yii::app()->name) for the source request identifier. This can be easily alerted by calling (with debugging disable)

	$cal = new EGCal('username@gmail.com', 'gmail-password', FALSE, 'companyName-applicationName-versionID');
				

Retrieving Events
-----------------

Retrieving events can be done by calling find() as such:
            
	$response = $cal->find(
		array(
			'min'=>date('c', strtotime("8 am")), 
			'max'=>date('c', strtotime("5 pm")),
			'limit'=>50,
			'order'=>'a',
			'calendar_id'=>'#stardate@group.v.calendar.google.com'
		)
	);

The fields min, max, and calendar_id are required.
The fields limit and order are option, and default to 50, and ascending respectivly.

The min and max times should be in ISO 8601 date format [ date('c') ], and may require a timezone offset.


### Adjusting for Timezone

Sometimes events may appear to be several hours off. This is due to the timezone of your Google Calendar differing from that of your PHP System Time.
This can be easily corrected by modifying the calendar settings, and/or offseting the min and max request times by a timezone offset.

### Response
Responses will take the form of a php array, containing the total number of events , and an array of events.
Each event will contain the calendar ID, the start and end times, and the title of the event.

For example:

	Array
	(
	    [totalResults] => z
	    [events] => Array
		(
		    [0] => Array
		        (
		            [id] => n9af6k7fpbh4p90snih1vfe1bc
		            [start] => 2011-12-19T08:20:00.000-06:00
		            [end] => 2011-12-19T08:40:00.000-06:00
		            [title] => Meeting with Josh
		        )

		    [1] => Array
		        (
		            [id] => ux5ohbtgbr0u2tk6cyivsi8tj9
		            [start] => 2011-12-19T15:30:00.000-06:00
		            [end] => 2011-12-19T17:00:00.000-06:00
		            [title] => Meeting with Jane
		        )
			
		    [...]
		)
	)

	
Creating Events
---------------


Updating Events
---------------