<?php

namespace Trac2GitLab;

use JsonRPC\Client;

/**
 * Trac communicator class
 *
 * @author  dachaz
 */
class Trac
{
    private $client;
    private $url;
    
    /**
     * Constructor
     *
     * @param  string    $url         Trac URL
     */
	public function __construct($url) {
		$this->url = $url;
		$this->client = new Client($url . "/jsonrpc");
	}

    /**
     * Returns all open tickets for the given component name.
     * Each ticket is an array of [id, time_created, time_changed, attributes, comments]
     *
     * @param  string    $component   Name of the component.
     * @return  array
     */
	public function listOpenTicketsForComponent($component) {
		return $this->listTicketsForQuery("component=$component&status=!closed&max=0");
	}

	/**
     * Returns all tickets matching the given query.
     * Each ticket is an array of [id, time_created, time_changed, attributes, comments]
     *
     * @param  string    $query      Custom query to be executed in order to obtain tickets.
     * @return  array
     */
	public function listTicketsForQuery($query) {
		$tickets = array();
		$ticketIds = $this->client->execute('ticket.query', array($query));
		foreach($ticketIds as $id) {
			$tickets[$id] = $this->getTicket($id);
			$tickets[$id][] = $this->getComments($id);
		}
		return $tickets;
	}

	/**
     * Returns an individual ticket matching the given id.
     * The ticket is an array of [id, time_created, time_changed, attributes]
     *
     * @param  int      $id        Id of the ticket.
     * @return  array
     */
	public function getTicket($id) {
		return $this->client->execute('ticket.get', array($id));
	}

	/**
     * Returns an array of comments on an individual ticket.
     * Each individual comment is itself an associative array of [time, author, text]
     *
     * @param  int      $id        Id of the ticket.
     * @return  array
     */
	public function getComments($ticketId) {
		$comments = array();
		$changes = $this->client->execute('ticket.changeLog', array($ticketId));
		foreach ($changes as $change) {
			if ($change[2] == "comment" && trim($change[4])) {
				$comments[] = array(
					"time" => $change[0],
					"author" => $change[1],
					"text" => $change[4]
				);
			}
		}
		return $comments;
	}

	/**
	 * Returns the URL of this Trac installation.
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}
}
?>