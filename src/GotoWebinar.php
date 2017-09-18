<?php

namespace FlipMinds\GotoWebinar;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use DateTime;
use Exception;

class GotoWebinar
{

	/**
	 * The base url for API calls
	 *
	 * @var string
	 */
	private $apiUrl = 'https://api.getgo.com/G2W/rest/';

	/**
	 * Array holding authentication data
	 *
	 * @var array
	 */
	private $auth;

	/**
	 * Username, password anmd consumerKey as an arry
	 *
	 * @var array
	 */
	private $credentials;


	/**
	 * HTTP response code from the last guzzle request
	 *
	 * @var int
	 */
	private $statusCode;

	/**
	 * Status Code text from the last guzzle request
	 *
	 * @var string
	 */
	private $reasonPhrase;

	/**
	 * Guzzle Client
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * GotoWebinar constructor.
	 *
	 * Pass in two arrays. The first $credentials contains the username, password andConsumerKey. The second is an auth array that could have
	 * been cached (@see getAuth()).  Using the auth array avoids an authentication round trip.
	 *
	 * @param array $credentials
	 * @param array $auth
	 * @throws Exception
	 */
	public function __construct(array $credentials, array $auth = [])
	{

		if (!$credentials['username'] || !$credentials['password'] || !$credentials['consumerKey']) {
			throw new Exception('GotoWebinar: Missing required parameters consumerKey, username & password');
		}
		$this->credentials = $credentials;

		$defaults = [
			'accessToken'  => '',
			'expiresIn'    => 0,
			'refreshToken' => '',
			'organizerKey' => '',
			'accountKey'   => '',
		];

		$this->auth = array_merge($defaults, $auth);;

		$this->client = new Client([
			'base_uri' => $this->apiUrl,
			'headers'  => [
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',

			],
		]);
	}

	private function getClient()
	{
		return $this->client;
	}

	private function authenticate()
	{
		$auth = $this->auth;

		// Are we good to go ?
		if ($auth['accessToken'] && $auth['organizerKey'] && $auth['accountKey'] && $auth['expiresIn'] > time()) {
			return $auth['accessToken'];
		}

		// Nope, looks like we need to authenticate.
		$options = [
			'form_params' => [
				'grant_type' => 'password',
				'user_id'    => $this->credentials['username'],
				'password'   => $this->credentials['password'],
				'client_id'  => $this->credentials['consumerKey'],
			],
		];
		$response = $this->sendRequest('POST', '/oauth/access_token', $options, false);
		if (!$response) {
			throw new Exception('GotoWebinar API: Unable to Authenticate with given credentials');
		}

		// It worked!
		// The GotoWebinar access_token is valid for 356 days
		$expires = new DateTime();
		$expires->modify('+' . $response->expires_in . ' seconds');

		// Use getAuth() to retrieve this array and stash it.
		$this->auth = [
			'accessToken'  => $response->access_token,
			'expiresIn'    => $expires->getTimestamp(),
			'refreshToken' => $response->refresh_token,
			'organizerKey' => $response->organizer_key,
			'accountKey'   => $response->account_key,
		];

		return $response->access_token;
	}


	/**
	 * Get The Authentication Array or caching
	 *
	 * @return array
	 */
	public function getAuth(): array
	{
		return $this->auth;
	}

	/**
	 * Return this Organizer Key, authenticate if not found
	 *
	 * @return string
	 */
	public function getOrganizerKey()
	{
		if (!$this->auth['organizerKey']) {
			$this->authenticate();
		}
		return $this->auth['organizerKey'];
	}

	/**
	 * Return this Account Key, authenticate if not found
	 *
	 * @return string
	 */
	public function getAccountKey()
	{
		if (!$this->auth['accountKey']) {
			$this->authenticate();
		}
		return $this->auth['accountKey'];
	}


	/**
	 * Return the most recent resason phrase @see getStausCode()
	 *
	 * @return string
	 */
	public function getReasonPhrase()
	{
		return $this->reasonPhrase;
	}

	/**
	 * Return the last HTTP response code
	 *
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}


	/**
	 * Send a request to the GotoWebinar API
	 *
	 * @param $method string  GET, POST, PUT, DELETE
	 * @param $uri string  uri endpoint
	 * @param $options see Guzzle documentation
	 * @param bool $authenticated bool Send the auth header (set to false during authentication calls)
	 * @return bool|mixed
	 */
	function sendRequest($method, $uri, $options = [], $authenticated = true)
	{
		if ($authenticated) {
			$authToken = $this->authenticate();
			$options['headers']['Authorization'] = $authToken;
		}

		try {
			$response = $this->getClient()->request($method, $uri, $options);
		} catch (RequestException $e) {
			$response = $e->getResponse();
			$this->reasonPhrase = ($response->getReasonPhrase());
			$this->statusCode = $response->getStatusCode();
			//TODO more here
			return false;
		}
		$this->reasonPhrase = ($response->getReasonPhrase());
		$this->statusCode = $response->getStatusCode();
		return json_decode($response->getBody());
	}

	/**
	 * Get All webinars for the account
	 *
	 * Retrieves the list of webinars for an account within a given date range. Page and size
	 * parameters are optional. Default page is 0 and default size is 20.
	 *
	 * @param $fromTime A required start of datetime range in ISO8601 UTC format, e.g. 2015-07-13T10:00:00Z
	 * @param $toTime A required end of datetime range in ISO8601 UTC format, e.g. 2015-07-13T22:00:00Z
	 * @param int $page The page number to be displayed. The first page is 0.
	 * @param int $size The size of the page.
	 * @return bool|mixed
	 */
	public function getAccountWebinars($fromTime, $toTime, $page = 0, $size = 20)
	{
		return $this->sendRequest(
			'GET',
			'accounts/' . $this->getAccountKey() . '/webinars',
			[
				'query' => get_defined_vars(),
			]
		);
	}

	/**
	 * Get Historical Webinars
	 *
	 * Returns details for completed webinars for the specified organizer and completed webinars of
	 * other organizers where the specified organizer is a co-organizer.
	 *
	 * @param $fromTime A required start of datetime range in ISO8601 UTC format, e.g. 2015-07-13T10:00:00Z
	 * @param $toTime A required end of datetime range in ISO8601 UTC format, e.g. 2015-07-13T22:00:00Z
	 * @return bool|mixed
	 */
	public function getHistoricalWebinars($fromTime, $toTime)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/historicalWebinars',
			[
				'query' => get_defined_vars(),
			]
		);
	}

	/**
	 * Get Upcoming Webinars
	 *
	 * Returns webinars scheduled for the future for the specified organizer and webinars of other
	 * organizers where the specified organizer is a co-organizer.
	 *
	 * @return bool|mixed
	 */
	public function getUpcomingWebinars()
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/upcomingWebinars'
		);
	}

	/**
	 * Get All Webinars
	 *
	 * Returns webinars scheduled for the future for a specified organizer.
	 *
	 * @return bool|mixed
	 */
	public function getAllWebinars()
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars'
		);
	}

	/**
	 * Create a Webinar
	 *
	 * Creates a single session webinar, a sequence of webinars or a series of webinars depending on the type field in
	 * the body: "single_session" creates a single webinar session, "sequence" creates a webinar with multiple meeting
	 * times where attendees are expected to be the same for all sessions, and "series" creates a webinar with multiple
	 * meetings times where attendees choose only one to attend. The default, if no type is declared, is single_session.
	 * A sequence webinar requires a "recurrenceStart" object consisting of a "startTime" and "endTime" key for the first
	 * webinar of the sequence, a "recurrencePattern" of "daily", "weekly", "monthly", and a "recurrenceEnd" which is the
	 * last date of the sequence (for example, 2016-12-01). A series webinar requires a "times" array with a discrete
	 * "startTime" and "endTime" for each webinar in the series. The call requires a webinar subject and description.
	 * The "isPasswordProtected" sets whether the webinar requires a password for attendees to join. If set to True,
	 * the organizer must go to Registration Settings at My Webinars (https://global.gotowebinar.com/webinars.tmpl) and add
	 * the password to the webinar, and send the password to the registrants. The response provides a numeric webinarKey in string format
	 * for the new webinar. Once a webinar has been created with this method, you can accept registrations.
	 *
	 * @param string $subject
	 * @param string $description
	 * @param array $times
	 * @param string $timeZone
	 * @param string $type
	 * @param bool $isPasswordProtected
	 * @return bool|mixed
	 */
	public function createWebinar(string $subject, string $description = '', array $times, string $timeZone, $type = 'single_session', $isPasswordProtected = false)
	{
		return $this->sendRequest(
			'POST',
			'organizers/' . $this->getOrganizerKey() . '/webinars',
			[
				'json' => get_defined_vars(),
			]
		);
	}

	/**
	 * Cancel a Webinar
	 *
	 * Cancels a specific webinar. If the webinar is a series or sequence, this call deletes all scheduled sessions. To send cancellation emails
	 * to registrants set sendCancellationEmails=true in the request. When the cancellation emails are sent, the default generated message is used in
	 * the cancellation email body.
	 *
	 * @param string $webinarKey The key of the webinar
	 * @param bool $sendCancellationEmails Indicates whether cancellation notice emails should be sent. The default value is false
	 * @return bool|mixed
	 */
	public function cancelWebinar(string $webinarKey, $sendCancellationEmails = false)
	{
		return $this->sendRequest(
			'DELETE',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey,
			[
				'query' => compact('sendCancellationEmails'),
			]
		);

	}

	/**
	 * Get Webinar
	 *
	 * Retrieve information on a specific webinar. If the type of the webinar is 'sequence', a sequence of future times will be provided.
	 * Webinars of type 'series' are treated the same as normal webinars - each session in the webinar series has a different webinarKey.
	 * If an organizer cancels a webinar, then a request to get that webinar would return a '404 Not Found' error.
	 *
	 * @param $webinarKey The key of the webinar
	 * @return bool|mixed
	 */
	public function getWebinar($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey
		);

	}

	/**
	 * Update a Webinar
	 *
	 * Updates a webinar. The call requires at least one of the parameters in the request body. The request completely
	 * replaces the existing session, series, or sequence and so must include the full definition of each as for the Create call.
	 * Set notifyParticipants=true to send update emails to registrants.
	 *
	 * @param string $webinarKey
	 * @param string $subject
	 * @param string $description
	 * @param array $times
	 * @param string $timeZone
	 * @param string $type
	 * @param bool $isPasswordProtected
	 * @param bool $notifyParticipants
	 * @return bool|mixed
	 */
	public function updateWebinar(string $webinarKey, string $subject, string $description = '', array $times, string $timeZone, $type = 'single_session', $isPasswordProtected = false, $notifyParticipants = false)
	{

		return $this->sendRequest(
			'PUT',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey,
			[
				'json'  => compact('subject', 'description', 'times', 'timeZone', 'type', 'isPasswordProtected'),
				'query' => compact('notifyParticipants'),
			]
		);
	}


	/**
	 * Get Attendees
	 *
	 * Returns all attendees for all sessions of the specified webinar.
	 *
	 * @param $webinarKey The key of the webinar
	 * @return bool|mixed
	 */
	public function getAttendees($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/attendees'
		);
	}


	/**
	 * Get Audio Information
	 *
	 * Retrieves the audio/conferencing information for a specific webinar.
	 *
	 * @param $webinarKey The key of the webinar
	 * @return bool|mixed
	 */
	public function getAudio($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/audio'
		);
	}

	/**
	 * Update Audio
	 *
	 * Updates the audio/conferencing settings for a specific webinar
	 *
	 * @param $webinarKey string The key of the webinar
	 * @param $type  string Indicates how to connect to the webinar's audio conference = ['PSTN', 'VOIP', 'Hybrid', 'Private'],
	 * @param $pstnInfo array Defines via two-letter Alpha-2-code which toll and toll-free PSTN numbers are available per country
	 * @param $privateInfo array Defines the audio data for an own conferencing system
	 * @param $notifyParticipants Defines whether to send notifications to participants
	 * @return bool|mixed
	 */
	public function updateAudio($webinarKey, $type, $pstnInfo, $privateInfo, bool $notifyParticipants = false)
	{
		return $this->sendRequest(
			'POST',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/audio',
			[
				'json'  => compact('type', 'pstnInfo', 'privateInfo'),
				'query' => compact('notifyParticipants'),
			]
		);
	}

	/**
	 * Get webinar meeting times
	 *
	 * Retrieves the meeting times for a webinar.
	 *
	 * @param $webinarKey  The key of the webinar
	 * @return bool|mixed
	 */
	public function getMeetingTimes($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/meetingtimes'
		);
	}

	/**
	 * Get Performance
	 *
	 * Gets performance details for all sessions of a specific webinar.
	 *
	 * @param $webinarKey
	 * @return bool|mixed
	 */
	public function getPerformance($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/performance'
		);
	}

	/**
	 * Get Organizer Sessions
	 *
	 * Retrieve all completed sessions of all the webinars of a given organizer.
	 *
	 * @param $fromTime string A required start of datetime range in ISO8601 UTC format, e.g. 2015-07-13T10:00:00Z
	 * @param $toTime  string A required end of datetime range in ISO8601 UTC format, e.g. 2015-07-13T22:00:00Z
	 * @return bool|mixed
	 */
	public function getOrganizerSessions($fromTime, $toTime)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/sessions',
			[
				'query' => get_defined_vars(),
			]
		);
	}

	/**
	 * Get Webinar Sessions
	 *
	 * Retrieves details for all past sessions of a specific webinar.
	 *
	 * @param $webinarKey
	 * @return bool|mixed
	 */
	public function getSessions($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/'
		);
	}

	/**
	 * Get Webinar Session
	 *
	 * Retrieves attendance details for a specific webinar session that has ended. If attendees attended
	 * the session ('registrantsAttended'), specific attendance details, such as attendenceTime for a
	 * registrant, will also be retrieved.
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @return bool|mixed
	 */
	public function getSession($webinarKey, $sessionKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey
		);
	}

	/**
	 * Get Session performance
	 *
	 * Get performance details for a session.
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @return bool|mixed
	 */
	public function getSessionPerformance($webinarKey, $sessionKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/performance'
		);
	}

	/**
	 * Get Session Polls
	 *
	 * Retrieve all collated attendee questions and answers for polls from a specific webinar session.
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @return bool|mixed
	 */
	public function getSessionPolls($webinarKey, $sessionKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/polls'
		);
	}

	/**
	 * Get Session Questions
	 *
	 * Retrieve questions and answers for a past webinar session.
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @return bool|mixed
	 */
	public function getSessionQuestions($webinarKey, $sessionKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/questions'
		);
	}

	/**
	 * Get Session Surveys
	 *
	 * Retrieve surveys for a past webinar session.
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @return bool|mixed
	 */
	public function getSessionSurveys($webinarKey, $sessionKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/surveys'
		);
	}


	/**
	 * Get Co-organizers
	 *
	 * Returns the co-organizers for the specified webinar. The original organizer who created the webinar is
	 * filtered out of the list. If the webinar has no co-organizers, an empty array is returned. Co-organizers
	 * that do not have a GoToWebinar account are returned as external co-organizers. For those organizers no surname
	 * is returned.
	 *
	 * @param $webinarKey
	 * @return bool|mixed
	 */
	public function getCoOrganizers($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/coorganizers'
		);
	}


	/**
	 * Create Co-organizers
	 *
	 * Creates co-organizers for the specified webinar. For co-organizers that have a GoToWebinar account you
	 * have to set the parameter 'external' to 'false'. In this case you have to pass the parameter 'organizerKey'
	 * only. For co-organizers that have no GoToWebinar account you have to set the parameter 'external' to 'true'.
	 * In this case you have to pass the parameters 'givenName' and 'email'. Since there is no parameter for
	 * 'surname' you should pass first and last name to the parameter 'givenName'.
	 *
	 * @param $webinarKey  string The key of the webinar
	 * @param $external bool If the co-organizer has no GoToWebinar account, this value has to be set to 'true' ,
	 * @param $organizerKey string The co-organizer's organizer key. This parameter has to be passed only, if 'external' is set to 'false' ,
	 * @param $givenName string The co-organizer's given name. This parameter has to be passed only, if 'external' is set to 'true'
	 * @param $email string The co-organizer's email address. This parameter has to be passed only, if 'external' is set to 'true'
	 * @return bool|mixed
	 */
	public function createCoOrganizers($webinarKey, $external, $organizerKey, $givenName, $email)
	{
		return $this->sendRequest(
			'POST',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/coorganizers',
			[
				'json' => compact('external', 'organizerKey', 'givenName', 'email'),
			]
		);
	}


	/**
	 * Delete Co Organizer
	 *
	 * Deletes an internal co-organizer specified by the coorganizerKey (memberKey).
	 *
	 * @param $webinarKey string The key of the webinar
	 * @param $coorganiserKey The key of the internal or external co-organizer (memberKey)
	 * @param $external bool By default only internal co-organizers (with a GoToWebinar account) can be deleted. If you want to use this call for external
	 *                         co-organizers you have to set this parameter to 'true'.
	 * @return bool|mixed
	 */
	public function deleteCoOrganizers($webinarKey, $coorganiserKey, $external)
	{
		return $this->sendRequest(
			'DELETE',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/coorganizers/' . $coorganiserKey,
			[
				'query' => compact('external'),
			]
		);
	}


	/**
	 * Resend Invitation
	 *
	 * Resends an invitation email to the specified co-organizer
	 *
	 * @param $webinarKey string The key of the webinar
	 * @param $coorganiserKey The key of the internal or external co-organizer (memberKey)
	 * @param $external bool By default only internal co-organizers (with a GoToWebinar account) can be deleted. If you want to use this call for external
	 *                         co-organizers you have to set this parameter to 'true'.
	 * @return bool|mixed
	 */
	public function resendCoOrganizerInvitation($webinarKey, $coorganiserKey, $external)
	{
		return $this->sendRequest(
			'POST',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/coorganizers/' . $coorganiserKey . '/resendInvitation',
			[
				'query' => compact('external'),
			]
		);
	}


	/**
	 * Get Panelists
	 *
	 * Retrieves all the panelists for a specific webinar.
	 *
	 * @param $webinarKey
	 * @return bool|mixed
	 */
	public function getPanelists($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/panelists'
		);
	}

	/**
	 * Create Panelist
	 *
	 * Create panelists for a specified webinar
	 *
	 * @param $webinarKey The key of the webinar
	 * @param $panelists array an array of panelists ('name','email');
	 * @return bool|mixed
	 */
	public function createPanelist($webinarKey, array $panelists)
	{
		return $this->sendRequest(
			'POST',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/panelists',
			[
				'json' => compact('panelists'),
			]
		);
	}


	/**
	 * Delete Panelist
	 *
	 * Removes a webinar panelist.
	 *
	 * @param $webinarKey string The key of the webinar
	 * @param $panelistKey The key of the webinar panelist
	 * @return bool|mixed
	 */
	public function deletePanelist($webinarKey, $panelistKey)
	{
		return $this->sendRequest(
			'DELETE',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/panelists/' . $panelistKey
		);
	}


	/**
	 * Resend Invitation
	 *
	 * Resends an invitation email to the specified panelist
	 *
	 * @param $webinarKey string The key of the webinar
	 * @param $panelistKey The key of the webinar panelist
	 * @return bool|mixed
	 */
	public function resendPanelistInvitation($webinarKey, $panelistKey)
	{
		return $this->sendRequest(
			'POST',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/panelists/' . $panelistKey . '/resendInvitation'
		);
	}


	/**
	 * Get Registrants
	 *
	 * Retrieve registration details for all registrants of a specific webinar. Registrant details will not include all
	 * fields captured when creating the registrant. To see all data, use the API call 'Get Registrant'.
	 * Registrants can have one of the following states;
	 * WAITING - registrant registered and is awaiting approval (where organizer has required approval),
	 * APPROVED - registrant registered and is approved, and
	 * DENIED - registrant registered and was denied.
	 *
	 * @param $webinarKey
	 * @return bool|mixed
	 */
	public function getRegistrants($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/registrants'
		);
	}


	/**
	 * Register for a Webinar
	 *
	 * Register an attendee for a scheduled webinar. The response contains the registrantKey and join URL for the registrant.
	 * An email will be sent to the registrant unless the organizer turns off the confirmation email setting from the GoToWebinar website.
	 * Please note that you must provide all required fields including custom fields defined during the webinar creation.
	 * Use the API call 'Get registration fields' to get a list of all fields, if they are required, and their possible values.
	 * At this time there are two versions of the 'Create Registrant' call. The first version only accepts firstName,
	 * lastName, and email and ignores all other fields. If you have custom fields or want to capture additional information this
	 * version won't work for you. The second version allows you to pass all required and optional fields,
	 * including custom fields defined when creating the webinar. To use the second version you must pass the header value
	 * 'Accept: application/vnd.citrix.g2wapi-v1.1+json' instead of 'Accept: application/json'.
	 * Leaving this header out results in the first version of the API call
	 *
	 * example body
	 *
	 * @param string $webinarKey The key of the webinar
	 * @param string $firstName The registrant's first name
	 * @param string $lastName The registrant's last name
	 * @param string $email The registrant's email address
	 * @param bool $resendConfirmation Indicates whether the confirmation email should be resent when a registrant is re-registered. The default value is false.
	 * @return bool|mixed
	 */
	public function createRegistrant($webinarKey, $firstName, $lastName, $email, $resendConfirmation = false)
	{
		return $this->sendRequest(
			'POST',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/registrants',
			[
				'query' => compact('resendConfirmation'),
				'json'  => compact('firstName', 'lastName', 'email'),
			]
		);
	}


	/**
	 * Delete Registrant
	 *
	 * Removes a webinar registrant from current registrations for the specified webinar. The webinar must be
	 * a scheduled, future webinar.
	 *
	 * @param $webinarKey
	 * @param $registrantKey
	 * @return bool|mixed
	 */
	public function deleteRegistrant($webinarKey, $registrantKey)
	{
		return $this->sendRequest(
			'DELETE',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/registrants/' . $registrantKey

		);
	}

	/**
	 * Get a Registrant
	 *
	 * Retrieve registration details for a specific registrant.
	 *
	 * @param $webinarKey
	 * @param $registrantKey
	 * @return bool|mixed
	 */
	public function getRegistrant($webinarKey, $registrantKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/registrants/' . $registrantKey
		);
	}


	/**
	 * Get registration fields
	 *
	 * Retrieve required, optional registration, and custom questions for a specified webinar.
	 *
	 * @param $webinarKey
	 * @return bool|mixed
	 */
	public function getRegistrationFields($webinarKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/registrants/fields'
		);
	}


	/**
	 * Get Session Attendees
	 *
	 * Retrieve details for all attendees of a specific webinar session.
	 *
	 * @param string $webinarKey The key of the webinar
	 * @param string $sessionKey The key of the webinar session
	 * @return bool|mixed
	 */
	public function getSessionAttendees($webinarKey, $sessionKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/attendees'
		);
	}

	/**
	 * Get Session Attendee
	 *
	 * Retrieve registration details for a particular attendee of a specific webinar session.
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @param $registrantKey
	 * @return bool|mixed
	 */
	public function getSessionAttendee($webinarKey, $sessionKey, $registrantKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/attendees/' . $registrantKey
		);
	}

	/**
	 * Get Session Attendee Polls
	 *
	 * Get poll answers from a particular attendee of a specific webinar session.
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @param $registrantKey
	 * @return bool|mixed
	 */
	public function getSessionAttendeePolls($webinarKey, $sessionKey, $registrantKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/attendees/' . $registrantKey . '/polls'
		);
	}


	/**
	 * Get Session Attendee Questions
	 *
	 * Get questions asked by an attendee during a webinar session.
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @param $registrantKey
	 * @return bool|mixed
	 */
	public function getSessionAttendeeQuestions($webinarKey, $sessionKey, $registrantKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/attendees/' . $registrantKey . '/questions'
		);
	}

	/**
	 * Get Session Attendee Surveys
	 *
	 * Retrieve survey answers from a particular attendee during a webinar session
	 *
	 * @param $webinarKey
	 * @param $sessionKey
	 * @param $registrantKey
	 * @return bool|mixed
	 */
	public function getSessionAttendeeSurveys($webinarKey, $sessionKey, $registrantKey)
	{
		return $this->sendRequest(
			'GET',
			'organizers/' . $this->getOrganizerKey() . '/webinars/' . $webinarKey . '/sessions/' . $sessionKey . '/attendees/' . $registrantKey . '/surveys'
		);
	}


}