<?php
/**
 * Give Butter API Client
 *
 * A WordPress-friendly PHP class for interacting with the Give Butter v1 API.
 *
 * @package   WP_Give_Butter_API
 * @version   1.0.0
 * @author    Brandon Hubbard
 * @link      https://docs.givebutter.com/reference/reference-getting-started
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WP_Give_Butter_API
 *
 * Handles all communication with the Give Butter API.
 */
class WP_Give_Butter_API {

	/**
	 * The Give Butter API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * The base URI for the Give Butter API v1.
	 *
	 * @var string
	 */
	private $base_uri = 'https://api.givebutter.com/v1';

	/**
	 * Constructor.
	 *
	 * @param string $api_key Your Give Butter API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = trim( $api_key );
	}

	/**
	 * Performs a request to the Give Butter API.
	 *
	 * @param string $endpoint The API endpoint to call (e.g., '/campaigns').
	 * @param array  $args     Optional. Arguments for the request.
	 * For GET, these are query parameters.
	 * For POST/PUT, this is the request body.
	 * @param string $method   Optional. The HTTP method (GET, POST, PUT, DELETE).
	 * Default 'GET'.
	 * @return array|WP_Error The decoded JSON response or a WP_Error on failure.
	 */
	private function request( $endpoint, $args = array(), $method = 'GET' ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'api_key_missing', 'API key is missing.' );
		}

		$url = $this->base_uri . $endpoint;

		$http_args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json; charset=utf-8',
				'Accept'        => 'application/json',
			),
			'timeout' => 15, // 15 seconds.
		);

		// Add query parameters for GET requests.
		if ( 'GET' === $method && ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		// Encode body for POST/PUT requests.
		if ( ( 'POST' === $method || 'PUT' === $method ) && ! empty( $args ) ) {
			$http_args['body'] = wp_json_encode( $args );
		}

		// Perform the request.
		$response = wp_remote_request( $url, $http_args );

		// Handle WP_Error from the request itself (e.g., cURL error).
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Retrieve details from the response.
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$decoded_body  = json_decode( $response_body, true );

		// Check for API-level errors (non-2xx response codes).
		if ( $response_code < 200 || $response_code >= 300 ) {
			$error_message = 'Give Butter API Error: ' . $response_code;

			// Try to get a specific error message from the API response.
			if ( ! empty( $decoded_body['error']['message'] ) ) {
				$error_message .= ' - ' . $decoded_body['error']['message'];
			} elseif ( ! empty( $decoded_body['message'] ) ) {
				$error_message .= ' - ' . $decoded_body['message'];
			} elseif ( ! empty( $response_body ) ) {
				$error_message .= ' - ' . $response_body;
			}

			return new WP_Error(
				'givebutter_api_error',
				$error_message,
				array(
					'status' => $response_code,
					'body'   => $decoded_body,
				)
			);
		}

		// Success! Return the decoded body.
		return $decoded_body;
	}

	/*
	|--------------------------------------------------------------------------
	| Accounts
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of accounts.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand').
	 * @return array|WP_Error
	 */
	public function get_accounts( $args = array() ) {
		return $this->request( '/accounts', $args, 'GET' );
	}

	/**
	 * Retrieve a single account.
	 *
	 * @param string $id   The ID of the account.
	 * @param array  $args Optional. Query parameters (e.g., 'expand').
	 * @return array|WP_Error
	 */
	public function get_account( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Account ID cannot be empty.' );
		}
		$endpoint = '/accounts/' . urlencode( $id );
		return $this->request( $endpoint, $args, 'GET' );
	}

	/*
	|--------------------------------------------------------------------------
	| Campaigns
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of campaigns.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter').
	 * @return array|WP_Error
	 */
	public function get_campaigns( $args = array() ) {
		return $this->request( '/campaigns', $args, 'GET' );
	}

	/**
	 * Retrieve a single campaign.
	 *
	 * @param string $id   The ID of the campaign.
	 * @param array  $args Optional. Query parameters (e.g., 'expand').
	 * @return array|WP_Error
	 */
	public function get_campaign( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Campaign ID cannot be empty.' );
		}
		$endpoint = '/campaigns/' . urlencode( $id );
		return $this->request( $endpoint, $args, 'GET' );
	}

	/*
	|--------------------------------------------------------------------------
	| Contacts
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of contacts.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter', 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public function list_contacts( $args = array() ) {
		return $this->request( '/contacts', $args, 'GET' );
	}

	/**
	 * Create a new contact.
	 *
	 * @param array $data The contact data.
	 * @return array|WP_Error
	 */
	public function create_contact( $data ) {
		if ( empty( $data ) ) {
			return new WP_Error( 'invalid_argument', 'Contact data cannot be empty.' );
		}
		return $this->request( '/contacts', $data, 'POST' );
	}

	/**
	 * Retrieve a single contact.
	 *
	 * @param string $id   The ID of the contact.
	 * @param array  $args Optional. Query parameters (e.g., 'expand').
	 * @return array|WP_Error
	 */
	public function get_contact( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Contact ID cannot be empty.' );
		}
		$endpoint = '/contacts/' . urlencode( $id );
		return $this->request( $endpoint, $args, 'GET' );
	}

	/**
	 * Update a contact.
	 *
	 * @param string $id   The ID of the contact to update.
	 * @param array  $data The data to update.
	 * @return array|WP_Error
	 */
	public function update_contact( $id, $data ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Contact ID cannot be empty.' );
		}
		if ( empty( $data ) ) {
			return new WP_Error( 'invalid_argument', 'Contact data cannot be empty.' );
		}
		$endpoint = '/contacts/' . urlencode( $id );
		return $this->request( $endpoint, $data, 'PUT' );
	}

	/**
	 * Delete a contact.
	 *
	 * @param string $id The ID of the contact to delete.
	 * @return array|WP_Error
	 */
	public function delete_contact( $id ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Contact ID cannot be empty.' );
		}
		$endpoint = '/contacts/' . urlencode( $id );
		return $this->request( $endpoint, array(), 'DELETE' );
	}

	/*
	|--------------------------------------------------------------------------
	| Donations
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of donations.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter', 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public function list_donations( $args = array() ) {
		return $this->request( '/donations', $args, 'GET' );
	}

	/**
	 * Create a new donation.
	 *
	 * @param array $data The donation data.
	 * @return array|WP_Error
	 */
	public function create_donation( $data ) {
		if ( empty( $data ) ) {
			return new WP_Error( 'invalid_argument', 'Donation data cannot be empty.' );
		}
		return $this->request( '/donations', $data, 'POST' );
	}

	/**
	 * Retrieve a single donation.
	 *
	 * @param string $id   The ID of the donation.
	 * @param array  $args Optional. Query parameters (e.g., 'expand').
	 * @return array|WP_Error
	 */
	public function get_donation( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Donation ID cannot be empty.' );
		}
		$endpoint = '/donations/' . urlencode( $id );
		return $this->request( $endpoint, $args, 'GET' );
	}

	/*
	|--------------------------------------------------------------------------
	| Fundraising Pages
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of fundraising pages.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter', 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public function list_pages( $args = array() ) {
		return $this->request( '/pages', $args, 'GET' );
	}

	/**
	 * Retrieve a single fundraising page.
	 *
	 * @param string $id   The ID of the page.
	 * @param array  $args Optional. Query parameters (e.g., 'expand').
	 * @return array|WP_Error
	 */
	public function get_page( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Page ID cannot be empty.' );
		}
		$endpoint = '/pages/' . urlencode( $id );
		return $this->request( $endpoint, $args, 'GET' );
	}

	/*
	|--------------------------------------------------------------------------
	| Payouts
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of payouts.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter', 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public function list_payouts( $args = array() ) {
		return $this->request( '/payouts', $args, 'GET' );
	}

	/*
	|--------------------------------------------------------------------------
	| Plans (Subscriptions)
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of plans (subscriptions).
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter', 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public function list_plans( $args = array() ) {
		return $this->request( '/plans', $args, 'GET' );
	}

	/**
	 * Retrieve a single plan (subscription).
	 *
	 * @param string $id   The ID of the plan.
	 * @param array  $args Optional. Query parameters (e.g., 'expand').
	 * @return array|WP_Error
	 */
	public function get_plan( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Plan ID cannot be empty.' );
		}
		$endpoint = '/plans/' . urlencode( $id );
		return $this->request( $endpoint, $args, 'GET' );
	}

	/**
	 * Update a plan (subscription).
	 *
	 * @param string $id   The ID of the plan to update.
	 * @param array  $data The data to update.
	 * @return array|WP_Error
	 */
	public function update_plan( $id, $data ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Plan ID cannot be empty.' );
		}
		if ( empty( $data ) ) {
			return new WP_Error( 'invalid_argument', 'Plan data cannot be empty.' );
		}
		$endpoint = '/plans/' . urlencode( $id );
		return $this->request( $endpoint, $data, 'PUT' );
	}

	/**
	 * Cancel a plan (subscription).
	 *
	 * @param string $id The ID of the plan to cancel.
	 * @return array|WP_Error
	 */
	public function cancel_plan( $id ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Plan ID cannot be empty.' );
		}
		$endpoint = '/plans/' . urlencode( $id ) . '/cancel';
		return $this->request( $endpoint, array(), 'POST' );
	}

	/*
	|--------------------------------------------------------------------------
	| Team Members
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of team members.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter', 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public function list_team_members( $args = array() ) {
		return $this->request( '/team-members', $args, 'GET' );
	}

	/*
	|--------------------------------------------------------------------------
	| Tickets
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of tickets.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter', 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public class list_tickets( $args = array() ) {
		return $this->request( '/tickets', $args, 'GET' );
	}

	/*
	|--------------------------------------------------------------------------
	| Transactions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of transactions.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'expand', 'filter', 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public function list_transactions( $args = array() ) {
		return $this->request( '/transactions', $args, 'GET' );
	}

	/**
	 * Retrieve a single transaction.
	 *
	 * @param string $id   The ID of the transaction.
	 * @param array  $args Optional. Query parameters (e.g., 'expand').
	 * @return array|WP_Error
	 */
	public function get_transaction( $id, $args = array() ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Transaction ID cannot be empty.' );
		}
		$endpoint = '/transactions/' . urlencode( $id );
		return $this->request( $endpoint, $args, 'GET' );
	}

	/*
	|--------------------------------------------------------------------------
	| Webhooks
	|--------------------------------------------------------------------------
	*/

	/**
	 * Retrieve a list of webhooks.
	 *
	 * @param array $args Optional. Query parameters (e.g., 'page', 'page_size').
	 * @return array|WP_Error
	 */
	public function list_webhooks( $args = array() ) {
		return $this->request( '/webhooks', $args, 'GET' );
	}

	/**
	 * Create a new webhook.
	 *
	 * @param array $data The webhook data (e.g., 'target_url', 'events').
	 * @return array|WP_Error
	 */
	public function create_webhook( $data ) {
		if ( empty( $data ) || empty( $data['target_url'] ) || empty( $data['events'] ) ) {
			return new WP_Error( 'invalid_argument', 'Webhook data (target_url, events) cannot be empty.' );
		}
		return $this->request( '/webhooks', $data, 'POST' );
	}

	/**
	 * Delete a webhook.
	 *
	 * @param string $id The ID of the webhook to delete.
	 * @return array|WP_Error
	 */
	public function delete_webhook( $id ) {
		if ( empty( $id ) ) {
			return new WP_Error( 'invalid_argument', 'Webhook ID cannot be empty.' );
		}
		$endpoint = '/webhooks/' . urlencode( $id );
		return $this->request( $endpoint, array(), 'DELETE' );
	}

}
