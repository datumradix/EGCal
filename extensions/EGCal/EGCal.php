<?php

/**
 *  @class     GCal
 *  @author    Charles R. Portwood II  <charlesportwoodii@ethreal.net>
 *                                     <crp07c@acu.edu>
 *  @version   1.0.0
 *  @package   EGCal (An extension for Yii)	
 *
 *  //---------------------------------------------------------------------//
 *  Public Methods
 *  isConnected()		// Returns bool if connection was successful
 *  getResponseCode()		// Returns HTTP response code from last request
 *  find()			// Finds a list of events based on an id, and a date range
 *
 **/
class EGCal
{
	// Calendar ID that you wish to connect to
	private $calendar_id;
	
	// Username of the calendar owner (example@gmail.com)
	private $username;
	
	// Auth Password
	private $password;
	
	// Source
	private $source;
	
	// Reporting Level
	private $level;
	
	// Authentication Data
	private $auth;
	
	// Connection Status
	private $connected;
	
	// Response Code
	private $response_code;
	
	// CURL Headers
	private $headers;
	
	/**
	 *  Constructor. Called on new GCal(). Sets up initial connection
	 *
	 *  @param string $username 	Google Username
	 *  @param string $password	Google Password
	 *  @param string $source 	Identifies your client application for support purposes. This string should take the form of your companyName-applicationName-versionID.
	 *				http://code.google.com/googleapps/domain/calendar_resource/docs/1.0/calendar_resource_developers_guide_protocol.html#client_login
	 *  @param mixed $level		Indicates the log level to use, 0, 1 (true, false)
	 *  @call new GCal('username', 'password')
	 *  @call new GCal('username', 'password', 1)
	 *  @call new GCal('username', 'password', 0, 'companyName-appName-versionId')
	 *  @return void
	 **/
	public function __construct($username, $password, $level = 0, $source = NULL)
	{		
		if ($source == NULL)
		{
			$this->source = str_replace(' ', '_',Yii::app()->name);
		}
		else
		{
			$this->source = $source;
		}
		
		// Set the warning level
		$this->level = $level;
		
		// Perform the connection
		$this->connected = $this->connect($username, $password);
				
	}
	
	/**
	 *  Performs Connection to Google Calendar
	 *
	 *  @param string $username
	 *  @param string $username
	 *  @return bool $this->connection
	 **/
	private function connect($username=NULL, $password=NULL)
	{
		$this->connected = false;
		$this->username = $username;
		
		$this->password = $password;
		
		$content = array(
			'accountType' => 'HOSTED_OR_GOOGLE',
			'Email' => $this->username,
			'Passwd' => $this->password,
			'source' => $this->source,
			'service' => 'cl'
		);
			
		Yii::import('application.extensions.EGCal.Curl');
		$curl = new Curl('https://www.google.com/accounts/ClientLogin');	
		$response = $curl->run('POST', $content);
		
		$this->response_code = $curl->status;
		
		if ($curl->status == '403')
		{			
			if ($this->level > 0)
			{
				echo 'Could not establish connection to Google Calendar.' . "\n";
				echo 'Response Code: ' . $curl->status . "\n";
			}
			
			return false;
		}
		else
		{
			parse_str(str_replace(array("\n", "\r\n"), '&', $response), $response);
			$this->auth = $response['Auth'];
			$this->setHeaders();
			return true;
		}		
	}
	
	/**
	 *  Prepares the headers one time so we do not keep re-creating the headers
	 *
	 **/
	private function setHeaders()
	{
		$this->headers = array(
		    "Authorization: GoogleLogin auth=" . $this->auth,
		    "GData-Version: 2.6",
		    'Content-Type: application/json'
		);
	}
	/**
	 *  Simple debug helper
	 *
	 *  @param mixed $options
	 *  @return print_r($option)
	 **/
	protected function debug($options)
	{
		print_r($options);
	}
	
	/**
	 *  Public method to retrieve connection status
	 *
	 *  @return bool $this->connected
	 **/
	public function isConnected()
	{
		return $this->connected;
	}
	
	/**
	 *  Public method to retrieve the last response code
	 *
	 *  @return int/string $this->response_code
	 **/
	public function getResponseCode()
	{
		return $this->response_code;
	}
	
	/**
	 *  Method to find events based upon a date range and calendar_id
	 *
	 *  @param array $options
	 *	@subparam datetime min
	 *	@subparam datetime max
	 *	@subparam string order (a,d) (Ascending, Descending)
	 *	@subparam int limit (50)
	 *	@subparam string calendar_id
	 *
	 *  Example $options
	 *	array(
	 *		'min'=>date('c', strtotime("8 am")), 
	 *		'max'=>date('c', strtotime("5 pm")),
	 *		'limit'=>5,
	 *		'order'=>'d',
	 *		'calendar_id'=>'en.usa#holiday@group.v.calendar.google.com'
	 *	)
	 *
	 *  @return array $results
	 **/
	public function find($options=array())
	{
		if ($this->isConnected())
		{
			if (!empty($options) && is_array($options) && $options['calendar_id'] != NULL)
			{
				// Parse the options to a usable format
				$min = (!isset($options['min'])) ? date('Y-m-d\T00:i:s') : date('Y-m-d\TH:i:s', strtotime($options['min']));
				$max = (!isset($options['max'])) ? date('Y-m-d\T23:59:59') : date('Y-m-d\TH:i:s', strtotime($options['max']));
				$limit = (!isset($options['limit'])) ? 50 : $options['limit'];
				$order = (!isset($options['order'])) ? 'a' : $options['order'];
				
				$calendar_id = $options['calendar_id'];
				
				// Build the Calendar URL
				$url = "http://www.google.com/calendar/feeds/$calendar_id/private/full?orderby=starttime&sortorder=$order&singleevents=true&start-min=$min&start-max=$max&max-results=$limit&alt=jsonc";
				
				// Load the CURL Library
				Yii::import('application.extensions.GCal.Curl');
				$curl = new Curl($url);
								
				// Set the headers
				$curl->setHeader($this->headers, $url, false);
				
				// Make the request
				$response = json_decode($curl->run('GET'),true);
				
				// Set the response code for debugging purposes
				$this->response_code = $curl->status;
				
				// We should receive a 200 response. If we don't, return a blank array
				if ($this->response_code != '200')
					return array();
				
				// Build the results array
				$results = array(
					'totalResults'=>$response['data']['totalResults'],
					'events'=>array()
				);
		
				// Parse the response, and use it to populate our results
				foreach ($response['data']['items'] as $item) {
					$tmp = array(
						'id' => $item['id'],
						'start' => $item['when'][0]['start'],
						'end' => $item['when'][0]['end'],
						'title' => $item['title'],
						//'location' => $item['location']
					);
					$results['events'][] = $tmp;
				}
				
				// Return the results as an array
				return $results;
		
			}
			else
			{
				// Debug Output
				if ($this->level > 0)
				{
					if (empty($options))
					{
						echo 'No options were specified' . "\n";
					}
					
					if ($options['calendar_id'] == NULL)
					{
						echo 'Calendar ID is not set.' . "\n";
					}
				}
				
				return false;
			}
		}
		else
		{
			// Debug Output
			if ($this->level > 0)
			{
				echo 'Cannot complete query. No connection has been established.' . "\n";
			}
			return false;
		}
	}
	
	/**
	 *
	 *
	 **/
	public function create($options, $type = 1)
	{
		if ($this->isConnected())
		{
			// Verify the options are properly  set
			if (!empty($options) && is_array($options))
			{
		
				// Verify the required fields are set to something
				if (!isset($options['title']))
				{
					if ($this->level > 0)
					{
						echo 'No title was specified for event creation' . "\n";
					}
					return array();
				}
			
				if (!isset($options['start']))
				{
					if ($this->level > 0)
					{
						echo 'No start time specified for event creation' . "\n";
					}
					return array();
				}
			
				if (!isset($options['end']))
				{
					if ($this->level > 0)
					{
						echo 'No end time specified for event creation' . "\n";
					}
					return array();
				}
			
				// End isset validation
			
				// Retrieve and set the calendar_id and URL
				$calendar_id = $options['calendar_id'];
			
				$url = "http://www.google.com/calendar/feeds/$calendar_id/private/full?alt=jsonc";
			
				// Load the CURL Library
				Yii::import('application.extensions.GCal.Curl');
				$curl = new Curl($url);
				
				// Create a blank data set
				$data = array();
				
				// If we are creating a single event, or doing anything else not specified below
				if ($type == 1 || $type > 3)
				{
					// Build the data query
					$data = array(
						'data'=>array(
							'title'=>$options['title'],
							'details'=>isset($options['details']) ? $options['details'] : '',
							'location'=>isset($options['location']) ? $options['location'] : '',
							'status'=>isset($options['status']) ? $options['status'] : '',
							'when'=>array(
								'start'=>date('Y-m-d\TH:i:s', strtotime($options['start'])),
								'end'=>date('Y-m-d\TH:i:s', strtotime($options['end']))
							)
						)
					);
					
				}
				/*
				else if ($type == 2) // Quick Events
				{
			
				}
				else if ($type == 3) // Recurring Events
				{
					{
					  "data": {
					    "recurrence": "DTSTART;VALUE=DATE:20100505\r\nDTEND;VALUE=DATE:20100506\r\nRRULE:FREQ=WEEKLY;BYDAY=Tu;UNTIL=20100904\r\n"
					  }
					}
					
					$data = array(
						'data'=>array(
							'title'=>$options['title'],
							'details'=>isset($options['details']) ? $options['details'] : '',
							'location'=>isset($options['location']) ? $options['location'] : '',
							'status'=>isset($options['status']) ? $options['status'] : '',
							'recurrence'=>"DTSTART;VALUE=DATE:" . date('Y-m-d', strtotime($options['start'])) . "\r\nDTEND;VALUE=DATE:" . date('Y-m-d', strtotime($options['end'])). "\r\nRRULE:FREQ=weekly;BYDAY=Tu;UNTIL=" . date('2012-01-30') . "\r\n"
						)
					);
				}
				*/
								
				// Set the initial headers
				$curl->setHeader($this->headers, $url, TRUE, TRUE, 30);
					
				// Make an initial request to get the GSESSIONID			
				$response = $curl->run('POST', json_encode($data));
								
				$last_url =  $curl->last_url;			// Error code is 200, but is preceeded by a 301 for the gSessionId
				unset($curl);
				
				// Rebuild the Object to create to create the actual create Request
				
				$curl = new Curl($url);
				$curl->setHeader($this->headers, $last_url, TRUE);
					
				// Make an initial request to get the gSessionId	
				$response = json_decode($curl->run('POST', json_encode($data)), TRUE);

				error_reporting(0);
				return array(
					'id'=>$response['data']['id'],	
					'title'=>$response['data']['title'],
					'details'=>$response['data']['details'],
					'location'=>$response['data']['location'],
					'start'=>$response['data']['when'][0]['start'],
					'end'=>$response['data']['when'][0]['end']
				);
			}
			else
			{
				if ($this->level > 0)
				{
					echo 'Options are not properly set' . "\n";
				}
			}
		}
		else
		{
			if ($this->level > 0)
			{
				echo 'No connection has been started' . "\n";
			}
		}
	}
	
	/*
	public function update();
	{
	
	}
	
	public function delete()
	{
	
	}
	*/
}

?>
