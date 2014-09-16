<?php

class domainRobotDefaultObject {
	public function set($key, $value)
	{
		$this->{$key} = $value;
	}

	public function get($key)
	{
		return $this->{$key};
	}
}

class domainRobotDefaultRequest extends domainRobotDefaultObject {
	private $clientTransactionId;
 	private $authToken;
	
	public function __construct($authToken)
	{
		$this->authToken = $authToken;
		$this->_generateTransactionId();
	}

	private function _generateTransactionId()
	{
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghjklmnpqrstuvwxyz0123456789";
		$pass = "";
		for($i = 0; $i < 12; $i++) {
			$pass .= $chars[mt_rand(0, strlen($chars) - 1)];
		}
		$this->clientTransactionId = date("YmdHis")."-".$pass;
	}

	public function getTransactionId()
	{
		return $this->clientTransactionId;
	}
}

class domainRobotContact extends domainRobotDefaultObject {
	public $accountId;
	public $id;
	public $handle;
	public $type;
	public $name;
	public $organisation;
	public $street;
	public $postOfficeBox;
	public $postalCode;
	public $city;
	public $state;
	public $country;
	public $email;
	public $phone;
	public $fax;
	public $sip;
	public $lastChangeDate;
}

class domainRobotDomainContact extends domainRobotDefaultObject {
	public $type;
 	public $contact;

	public function __construct($type, $contact)
	{
		$this->set("type", $type);
		$this->set("contact", $contact);
	}
}

class domainRobotDomain extends domainRobotDefaultObject {
	public $accountId;
	public $id;
	public $name;
	public $contacts;
	public $nameservers;
	public $status;
	public $transferLockEnabled;
	public $authCode;
	public $createDate;
}

class domainRobotTransferData extends domainRobotDefaultObject {
	public $authInfo;
	public $authInfo2;
	public $foaRecipient;
}

class domainRobotNameserver extends domainRobotDefaultObject {
	public $name;
	public $ips;

	public function __construct($name, $ips = NULL)
	{
		$this->set("name", $name);
		$this->set("ips", $ips);
	}
}

class domainRobotApi {

	private $location = "http://regspeed.de/api/domain/v1/soap";

	private $authToken;
        private $soap = NULL;

	private $lastRequestId = NULL;
	private $lastResponse = NULL;

	public function __construct($authToken)
	{
		$this->authToken = $authToken;
		try {
			if ($client = new SOAPClient(__DIR__."/domainrobot.wsdl", array('location' => $this->location, 'connection_timeout' => 10, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS)))
			{
				$this->soap = $client;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}

	private function _request($action, $request)
	{
		$this->lastRequestId = $request->getTransactionId();
		try {
			$this->lastResponse = $this->soap->{$action}($request);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function getStatus()
	{
		if (isset($this->lastResponse) && isset($this->lastResponse->status)) {
			return $this->lastResponse->status;
		}
		return false;
	}
	
	public function getErrors()
	{
		if (isset($this->lastResponse) && isset($this->lastResponse->errors)) {
			return $this->lastResponse->errors;
		}
		return array();
	}

	public function getWarnings()
	{
		if (isset($this->lastResponse) && isset($this->lastResponse->warnings)) {
			return $this->lastResponse->warnings;
		}
		return array();
	}

	public function getValues()
	{
		if (isset($this->lastResponse) && isset($this->lastResponse->values)) {
			return $this->lastResponse->values;
		}
		return false;
	}

	public function contactCreate($data)
	{
		$contact = new domainRobotContact();
		foreach($data as $key => $value) {
			$contact->set($key, $value);
		}
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("contact", $contact);
		return $this->_request("contactCreate", $request);
	}

	public function contactUpdate($data)
	{
		$contact = new domainRobotContact();
		foreach($data as $key => $value) {
			$contact->set($key, $value);
		}
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("contact", $contact);
		return $this->_request("contactUpdate", $request);
	}

	public function contactDelete($handle, $deleteNow)
	{
		// TODO FindHandle oder nehmen wir auch das Handle als contactId an?
		return false;

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("contactId", $handle);
		$request->set("deleteNow", $deleteNow);
		return $this->_request("contactDelete", $request);
	}

	private function _getContacts($data)
	{
		$owner = new domainRobotDomainContact("owner", $data['owner']);
		$admin = new domainRobotDomainContact("admin", $data['admin']);
		$tech = new domainRobotDomainContact("tech", $data['tech']);
		$zone = new domainRobotDomainContact("zone", $data['zone']);
		$contacts = array($owner, $admin, $tech, $zone);
		return $contacts;
	}

	private function _getNameservers($data)
	{
		$nameservers = array();
		foreach($data['nameservers'] as $ns) {
			$ipv4 = NULL;
			if (isset($ns['ipv4'])) {
				$ipv4 = $ns['ipv4'];
			}
			$ipv6 = NULL;
			if (isset($ns['ipv6'])) {
				$ipv6 = $ns['ipv6'];
			}
			$nameserver = new domainRobotNameserver($ns['name'], $ipv4, $ipv6);
			$nameservers = array_merge($nameservers, array($nameserver));
		}
		return $nameservers;
	}

	public function domainInfo($domain)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		return $this->_request("domainInfo", $request);
	}

	public function domainStatus($domain)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainNames", array($domain));
		return $this->_request("domainStatus", $request);
	}

	public function domainCreate($data, $execDate = NULL)
	{
		$domain = new domainRobotDomain();
		$domain->set("name", $data['name']);
		$domain->set("contacts", $this->_getContacts($data));
		$domain->set("nameservers", $this->_getNameservers($data));
		$domain->set("transferLockEnabled", true);

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domain", $domain);
		$request->set("registrationPeriod", 1);
		$request->set("execDate", $execDate);
		return $this->_request("domainCreate", $request);
	}

	public function domainTransfer($data, $execDate = NULL)
	{
		$domain = new domainRobotDomain();
		$domain->set("name", $data['name']);
		$domain->set("contacts", $this->_getContacts($data));
		$domain->set("nameservers", $this->_getNameservers($data));
		$domain->set("transferLockEnabled", true);

		$transferData = new domainRobotTransferData();
		if (isset($data['authCode'])) {
			$transferData->set("authCode", $data['authCode']);
		}
		$transferData->set("foaRecipient", "both");

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domain", $domain);
		$request->set("transferData", $transferData);
		$request->set("execDate", $execDate);
		return $this->_request("domainTransfer", $request);
	}

	public function domainUpdate($data)
	{
		$domain = new domainRobotDomain();
		$domain->set("name", $data['name']);
		$domain->set("contacts", $this->_getContacts($data));
		$domain->set("nameservers", $this->_getNameservers($data));
		$domain->set("transferLockEnabled", true);

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domain", $domain);
		return $this->_request("domainUpdate", $request);
	}

	public function domainCreateAuthInfo($domain)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		return $this->_request("domainCreateAuthInfo", $request);
	}

	public function domainCreateAuthInfo2($domain)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		return $this->_request("domainCreateAuthInfo2", $request);
	}

	public function domainDelete($domain, $execData = NULL)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("execDate", $execDate);
		return $this->_request("domainDelete", $request);
	}

	public function domainWithdraw($domain, $disconnect = true, $execData = NULL)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("disconnect", $disconnect);
		$request->set("execDate", $execDate);
		return $this->_request("domainWithdraw", $request);
	}

	public function domainSetAutoRenewMode($domain, $mode)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("mode", $mode);
		return $this->_request("domainSetAutoRenewMode", $request);
	}

	public function domainSetTransferLock($domain, $mode)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("mode", $mode);
		return $this->_request("domainSetTransferLock", $request);
	}

	public function domainRestore($domain)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		return $this->_request("domainRestore", $request);
	}

	public function domainChangeTag($domain, $tag)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("tag", $tag);
		return $this->_request("domainChangeTag", $request);
	}

	public function domainTransferOutAck($domain)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		return $this->_request("domainTransferOutAck", $request);
	}

	public function domainTransferOutNack($domain, $reason)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("reason", $reason);
		return $this->_request("domainTransferOutNack", $request);
	}
}
