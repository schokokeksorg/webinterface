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

	public function overrideTransactionId($id)
	{
		$this->clientTransactionId = $id;
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
	public $organization;
	public $street;
	public $postalCode;
	public $city;
	public $state;
	public $country;
	public $phoneNumber;
	public $faxNumber;
	public $emailAddress;
	public $sipUri;
	public $lastChangeDate;
	public $hidden;
	public $extGender;
	public $extCompanyNumber;
	public $extCompanyNumberCountry;
	public $extTradingName;
	public $extTaxId;
	public $extTaxIdCountry;
	public $extDateOfBirth;
	public $extPlaceOfBirth;
	public $extPlaceOfBirthZipCode;
	public $extCountryOfBirth;
	public $extLanguage;
	public $extNationality;
	public $extRemarks;
	public $extIdentificationCardNumber;
	public $extIdentificationCardIssuingAuthority;
	public $extIdentificationCardIssueDate;
	public $extIdentificationCardValidUntil;
	public $extIdentificationCardCountry;
	public $extTradeMarkName;
	public $extTradeMarkRegistrationAuthority;
	public $extTradeMarkRegisterNumber;
	public $extTradeMarkCountry;
	public $extTradeMarkDateOfApplication;
	public $extTradeMarkDateOfRegistration;
	public $extAeroIdentificationNumber;
	public $extAeroPassword;
	public $extCaLegalType;
	public $extCatIntendedUsage;
	public $extUkType;
	public $extProProfession;
	public $extProAuthorityName;
	public $extProAuthorityUrl;
	public $extProLicenseNumber;
	public $extTravelUniqueIdentificationNumber;
	public $extXxxMemberId;
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
	public $accountId = NULL;
	public $id = NULL;
	public $name;
	public $nameUnicode;
	public $contacts;
	public $nameservers;
	public $status;
	public $transferLockEnabled;
	public $authInfo;
	public $addDate;
	public $createDate;
	public $lastChangeDate;
	public $terminableByDate;
	public $currentContractPeriodEnd;
	public $nextContractPeriodStart;
	public $deletionDate;
	public $deletionType;
}

class domainRobotTransferData extends domainRobotDefaultObject {
	public $authInfo;
	public $authInfo2;
	public $foaRecipient;
}

class domainRobotFilter extends domainRobotDefaultObject {
	public $field;
	public $value;
	public $relation;
	public $subFilterConnective;
	public $subFilter;
}

class domainRobotSortOptions extends domainRobotDefaultObject {
	public $field;
	public $order;
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

	private $location = "https://regspeed.de/api/domain/v1/soap";

	private $authToken;
        private $soap = NULL;

	private $lastRequestId = NULL;
	private $lastResponse = NULL;

	private $transactionId = NULL;

	public function __construct($authToken)
	{
		$this->authToken = $authToken;
		try {
			if ($client = new SOAPClient(__DIR__."/domainrobot.wsdl", array('trace' => true, 'location' => $this->location, 'connection_timeout' => 10, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS)))
			{
				$this->soap = $client;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}

	public function setTransactionId($transactionId) {
		$this->transactionId = $transactionId;
	}

	public function resetLocation($url)
	{
		$this->location = $url;
		$this->__construct($this->authToken);
	}

	private function _request($action, $request)
	{
		if (isset($this->transactionId)) {
			$request->overrideTransactionId($this->transactionId);
		}
		$this->lastRequestId = $request->getTransactionId();
		try {
			$this->lastResponse = $this->soap->{$action}($request);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function request($action, $request)
	{
		return $this->_request($action, $request);
	}

	public function getServerTransactionId()
	{
		if (isset($this->lastResponse) && isset($this->lastResponse->status)) {
			return $this->lastResponse->metadata->serverTransactionId;
		}
		return false;
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

	public function getErrorsToString()
	{
		$str = NULL;
		foreach($this->getErrors() as $error) {
			$str .= $error->code.": ".$error->text.";";
		}
		return $str;
	}


	public function getWarnings()
	{
		if (isset($this->lastResponse) && isset($this->lastResponse->warnings)) {
			return $this->lastResponse->warnings;
		}
		return array();
	}

	public function getValue()
	{
		if (isset($this->lastResponse) && isset($this->lastResponse->value)) {
			return $this->lastResponse->value;
		} elseif (isset($this->lastResponse) && isset($this->lastResponse->values)) {
			return $this->lastResponse->values;
		}
		return false;
	}

	public function contactsFindByData($data)
	{
		$filter = new domainRobotFilter();
		$filter->set("field", NULL);
		$filter->set("value", NULL);
		$filter->set("subFilterConnective", "AND");
		$subFilters = array();
		foreach($data as $key => $value) {
			$subFilter = new domainRobotFilter();
			$subFilter->set("field", "contact".ucfirst($key));
			$subFilter->set("value", $value);
			$subFilters[] = $subFilter;
		}
		$filter->set("subFilter", $subFilters);

		$sort = new domainRobotSortOptions();
		$sort->set("field", "contactName");
		$sort->set("order", "ASC");

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("filter", $filter);
		$request->set("limit", 0);
		$request->set("page", 1);
		$request->set("sort", $sort);
		return $this->_request("contactsFind", $request);
	}

	public function contactsFind($nameFilter = NULL)
	{
		$filter = NULL;
		if (strlen($nameFilter)) {
			$filter = new domainRobotFilter();
			$filter->set("field", "contactName");
			$filter->set("value", $nameFilter);
		}

		$sort = new domainRobotSortOptions();
		$sort->set("field", "contactName");
		$sort->set("order", "ASC");

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("filter", $filter);
		$request->set("limit", 0);
		$request->set("page", 1);
		$request->set("sort", $sort);
		return $this->_request("contactsFind", $request);
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

	public function contactInfo($contact)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("contactId", $contact);
		return $this->_request("contactInfo", $request);
	}

	public function contactDelete($contact, $deleteNow)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("contactId", $contact);
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
		if (isset($data['billing'])) {
			$billing = new domainRobotDomainContact("billing", $data['billing']);
			$contacts = array($owner, $admin, $tech, $zone, $billing);
		}
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

	public function domainsFindByName($nameFilter)
	{
		$filter = new domainRobotFilter();
		$filter->set("field", "domainName");
		$filter->set("value", $nameFilter);

		$sort = new domainRobotSortOptions();
		$sort->set("field", "domainName");
		$sort->set("order", "ASC");

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("filter", $filter);
		$request->set("limit", 0);
		$request->set("page", 1);
		$request->set("sort", $sort);
		return $this->_request("domainsFind", $request);
	}

	public function domainsFindByHandle($handleFilter)
	{
		$filter = new domainRobotFilter();
		$filter->set("field", "contactId");
		$filter->set("value", $handleFilter);

		$sort = new domainRobotSortOptions();
		$sort->set("field", "domainName");
		$sort->set("order", "ASC");

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("filter", $filter);
		$request->set("limit", 0);
		$request->set("page", 1);
		$request->set("sort", $sort);
		return $this->_request("domainsFind", $request);
	}

	public function domainInfo($domain)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		return $this->_request("domainInfo", $request);
	}

	public function domainStatus($domains)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainNames", $domains);
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
		if (isset($data['authInfo'])) {
			$transferData->set("authInfo", $data['authInfo']);
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
		if (isset($data['transferLockEnabled'])) {
			$domain->set("transferLockEnabled", $data['transferLockEnabled']);
		}

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

	public function domainDelete($domain, $execDate = NULL)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("execDate", $execDate);
		return $this->_request("domainDelete", $request);
	}

	public function domainWithdraw($domain, $disconnect = true, $execDate = NULL)
	{
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("disconnect", $disconnect);
		$request->set("execDate", $execDate);
		return $this->_request("domainWithdraw", $request);
	}

	public function domainSetAutoRenewMode($domain, $mode)
	{
		// TODO not implemented yet, moeglich ueber domainDelete/cancelJob
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("domainName", $domain);
		$request->set("mode", $mode);
		return $this->_request("domainSetAutoRenewMode", $request);
	}

	public function domainSetTransferLock($domain, $mode)
	{
		// TODO not implemented yet, moeglich ueber domainUpdate
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

	public function jobCancel($jobId) {
		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("jobId", $jobId);
		return $this->_request("jobCancel", $request);
	}

	public function jobsFindByName($domainFilter = NULL)
	{
		$filter = NULL;
		if (strlen($domainFilter)) {
			$filter = new domainRobotFilter();
			$filter->set("field", "jobDomainNameAce");
			$filter->set("value", $domainFilter);
		}

		$sort = new domainRobotSortOptions();
		$sort->set("field", "jobExecutionDate");
		$sort->set("order", "DESC");

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("filter", $filter);
		$request->set("limit", 10);
		$request->set("page", 1);
		$request->set("sort", $sort);
		return $this->_request("jobsFind", $request);
	}

	public function jobsFindById($idFilter)
	{
		$filter = new domainRobotFilter();
		$filter->set("field", "jobId");
		$filter->set("value", $idFilter);
		
		$sort = new domainRobotSortOptions();
		$sort->set("field", "jobDomainNameAce");
		$sort->set("order", "ASC");

		$request = new domainRobotDefaultRequest($this->authToken);
		$request->set("filter", $filter);
		$request->set("limit", 0);
		$request->set("page", 1);
		$request->set("sort", $sort);
		return $this->_request("jobsFind", $request);
	}
}
