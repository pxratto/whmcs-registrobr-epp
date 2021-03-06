<?php

# Copyright (c) 2012, AllWorldIT and (c) 2013, NIC.br (R)
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# 
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

# This module is a fork from whmcs-coza-epp (http://devlabs.linuxassist.net/projects/whmcs-coza-epp)
# whmcs-coza-epp developed by Nigel Kukard (nkukard@lbsd.net)



# Official Website for whmcs-registrobr-epp
# https://github.com/registrobr/whmcs-registrobr-epp


# More information on NIC.br(R) domain registration services, Registro.br(TM), can be found at http://registro.br
# Information for registrars available at http://registro.br/provedor/epp

# NIC.br(R) is a not-for-profit organization dedicated to domain registrations and fostering of the Internet in Brazil. No WHMCS services of any kind are available from NIC.br(R).


# WHMCS hosting, theming, module development, payment gateway 
# integration, customizations and consulting all available from 
# http://allworldit.com


# Configuration array

function registrobr_getConfigArray() {
	$configarray = array(
		"Username" => array( "Type" => "text", "Size" => "4", "Description" => "Provider ID(numerical)" ),
		"Password" => array( "Type" => "password", "Size" => "20", "Description" => "EPP Password" ),
		"TestMode" => array( "Type" => "yesno" , "Description" => "If active connects to beta.registro.br instead of production server"),
		"Certificate" => array( "Type" => "text", "Description" => "Path of certificate .pem" ),
		"Passphrase" => array( "Type" => "password", "Size" => "20", "Description" => "Passphrase to the certificate file" ),
		"CPF" => array( "Type" => "dropdown", "Options" => "1,2,3,4,5,6,7,8,9", "Description" => "Custom field index for individuals Tax Payer IDs", "Default" => "1"),
        "CNPJ" => array( "Type" => "dropdown", "Options" => "1,2,3,4,5,6,7,8,9", "Description" => "Custom field index for corporations Tax Payer IDs (can be same as above)", "Default" => "1"),
        "TechC" => array( "FriendlyName" => "Tech Contact", "Type" => "text", "Size" => "20", "Description" => "Tech Contact used in new registrations" ),
        "Language" => array ( "Type" => "radio", "Options" => "English,Portuguese", "Description" => "Escolha Portuguese para mensagens em Portugu&ecircs", "Default" => "English"),
        "FriendlyName" => array("Type" => "System", "Value"=>"Registro.br"),
        "Description" => array("Type" => "System", "Value"=>"http://registro.br/provedor/epp/"),
	);
    return $configarray;

}
   
    
# Function to return current nameservers

function registrobr_GetNameservers($params) {

    # Create new EPP client
    $client = _registrobr_Client();
    if (PEAR::isError($client)) 
    {
        $values["error"]=_registrobr_lang('getnsconnerror').$client;
        return $values;
    }
	
	$request = '
    <epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	<command>
		<info>
			<domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
				<domain:name hosts="all">'.$params["sld"].".".$params["tld"].'</domain:name>
			</domain:info>
		</info>
    <clTRID>'.mt_rand().mt_rand().'</clTRID>
	</command>
    </epp>
    ';

    $response = $client->request($request);
  
	# Check results	

	if(!is_array($response)) {

		# Parse XML
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($response);
		$ns = $doc->getElementsByTagName('hostName');

		# Extract nameservers
		$i =0;
		$values = array();
		foreach ($ns as $nn) {
			$i++;
			$values["ns{$i}"] = $nn->nodeValue;
		}
    }
	return $values;
}

# Function to save set of nameservers

function registrobr_SaveNameservers($params) {

    # Grab variables
    $tld = $params["tld"];
    $sld = $params["sld"];

    # Generate XML for nameservers
    if ($nameserver1 = $params["ns1"]) { 
        $add_hosts = '
        <domain:hostAttr>
        <domain:hostName>'.$nameserver1.'</domain:hostName>
        </domain:hostAttr>
        ';
	}

	if ($nameserver2 = $params["ns2"]) { 
		$add_hosts .= '
        <domain:hostAttr>
        <domain:hostName>'.$nameserver2.'</domain:hostName>
        </domain:hostAttr>
        ';

	}

	if ($nameserver3 = $params["ns3"]) { 
        $add_hosts .= '
        <domain:hostAttr>
        <domain:hostName>'.$nameserver3.'</domain:hostName>
        </domain:hostAttr>
        ';
    }

	if ($nameserver4 = $params["ns4"]) { 
        $add_hosts .= '
        <domain:hostAttr>
        <domain:hostName>'.$nameserver4.'</domain:hostName>
        </domain:hostAttr>';
	}

	if ($nameserver5 = $params["ns5"]) { 
		$add_hosts .= '
        <domain:hostAttr>
        <domain:hostName>'.$nameserver5.'</domain:hostName>
        </domain:hostAttr>';
	}

	# Grab list of current nameservers
	$client = _registrobr_Client();
    if (PEAR::isError($client)) {
            $values["error"]=_registrobr_lang('setnsconnerror').$client;
            return $values ;
    }

	$request = '
    <epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0"
	xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
        <command>
            <info>
                <domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                    <domain:name hosts="all">'.$sld.'.'.$tld.'</domain:name>
                </domain:info>
            </info>
            <clTRID>'.mt_rand().mt_rand().'</clTRID>
        </command>
    </epp>
    ';

    $response = $client->request($request);

    # Parse XML
	$doc= new DOMDocument();
	$doc->loadXML($response);
    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;

    # Check if result is ok
	if($coderes != '1000') {
            $errormsg = _registrobr_lang('setnsgeterrorcode').$coderes._registrobr_lang('msg').$msg."'";
            if (!empty($reason)) {
                $errormsg.= _registrobr_lang("reason").$reason."'";
            } ;
            logModuleCall("registrobr",$errormsg,$request,$response);
            $values["error"] = $errormsg;
            return $values;
    }

    # Generate list of nameservers to remove
    $hostlist = $doc->getElementsByTagName('hostName');
    foreach ($hostlist as $host) {
        $rem_hosts .= '
        <domain:hostAttr>
        <domain:hostName>'.$host->nodeValue.'</domain:hostName>
        </domain:hostAttr>
        ';

    }

	# Build request
	$request='
              <epp xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                    <command>
                        <update>
                            <domain:update>
                                <domain:name>'.$sld.'.'.$tld.'</domain:name>
                                <domain:add>
                                    <domain:ns>'.$add_hosts.' </domain:ns>
                                </domain:add>								  
                                <domain:rem>
                                    <domain:ns>'.$rem_hosts.'</domain:ns>
                                </domain:rem>
                            </domain:update>
                        </update>
                        <clTRID>'.mt_rand().mt_rand().'</clTRID>
                    </command>
            </epp>
            ';

    # Make request
    $response = $client->request($request);

	# Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($response);
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes != '1000') {
            $errormsg = _registrobr_lang('setnsupdateerrorcode').$coderes._registrobr_lang('msg').$msg."'";
            if (!empty($reason)) {
                $errormsg.= _registrobr_lang("reason").$reason."'";
            } ;
            logModuleCall("registrobr",$errormsg,$request,$response);
            $values["error"] = $errormsg;
            return $values;
    } else { 
        $values['status'] = _registrobr_lang("updatepending");
    }
    return $values;
}
       
# Function to register domain

function registrobr_RegisterDomain($params) {

	# Setup include dir
    $include_path = ROOTDIR . '/modules/registrars/registrobr';
    set_include_path($include_path . PATH_SEPARATOR . get_include_path());

	# Include CPF and CNPJ stuff we need
    require_once 'isCnpjValid.php';
    require_once 'isCpfValid.php';

	# Grab module parameters
    $moduleparams = getregistrarconfigoptions('registrobr');
    $RegistrantTaxID = $params['customfields'.$moduleparams['CPF']];
    if (!isCpfValid($RegistrantTaxID)) {
                        $RegistrantTaxID = $params['customfields'.$moduleparams['CNPJ']] ;
        
                        if (!isCnpjValid($RegistrantTaxID)) {
                            $values["error"] =_registrobr_lang("cpfcnpjrequired");
                            logModuleCall("registrobr",$values["error"],$params);
                            return $values;
                        }
    }
  
    $RegistrantTaxIDDigits = preg_replace("/[^0-9]/","",$RegistrantTaxID);
    if (isCpfValid($RegistrantTaxIDDigits)==TRUE) {
                        $RegistrantTaxID = substr($RegistrantTaxIDDigits,0,3).".".substr($RegistrantTaxIDDigits,3,3).".".substr($RegistrantTaxIDDigits,6,3)."-".substr($RegistrantTaxIDDigits,9,2);
    } else {
                        $RegistrantTaxID = substr($RegistrantTaxIDDigits,0,2).".".substr($RegistrantTaxIDDigits,2,3).".".substr($RegistrantTaxIDDigits,5,3)."/".substr($RegistrantTaxIDDigits,8,4)."-".substr($RegistrantTaxIDDigits,12,2);
    }

    # Grab variaibles
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = $params["regperiod"];

    # Get registrant details	
    $RegistrantFirstName = $params["firstname"];
    $RegistrantLastName = $params["lastname"];
    $RegistrantContactName = $params["firstname"]." ".$params["lastname"];
    if (isCpfValid($RegistrantTaxIDDigits)==TRUE) {
                        $RegistrantOrgName = substr($RegistrantContactName,0,40);

    } else {
                        $RegistrantOrgName = substr($params["companyname"],0,50);
                        if (empty($RegistrantOrgName)) {
                            $values['error'] = _registrobr_lang("companynamerequired");
                            return $values;
                        }   
    }

    $parts=preg_split("/[0-9.]/",$params["address1"],NULL,PREG_SPLIT_NO_EMPTY);
    $RegistrantAddress1=$parts[0];
    $parts=preg_split("/[^0-9.]/",$params["address1"],NULL,PREG_SPLIT_NO_EMPTY);
    $RegistrantAddress2=$parts[0];
    $RegistrantAddress3 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantStateProvince = _registrobr_StateProvince($params["state"]);
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = substr($params["fullphonenumber"],1);
    
    #Get an EPP connection
    $client = _registrobr_Client();
    if (PEAR::isError($client)) {
            $values["error"] = _registrobr_lang("registerconnerror").$client;
            logModuleCall("registrobr",$values["error"]);
            return $values ;
    }
    
    # Does the company or individual is already in the .br database ?
    $request = '
    <epp xmlns="urn:ietf:params:xml:ns:epp-1.0" 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
    xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                        <command>
                            <info>
                                <contact:info xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" 
                                xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 
                                contact-1.0.xsd">
                                    <contact:id>'.$RegistrantTaxIDDigits.'</contact:id>
                                    </contact:info>
                            </info>
                            <extension>
                                <brorg:info xmlns:brorg="urn:ietf:params:xml:ns:brorg-1.0" 
                                xsi:schemaLocation="urn:ietf:params:xml:ns:brorg-1.0 
                                brorg-1.0.xsd"> 
                                    <brorg:organization>'.$RegistrantTaxID.'</brorg:organization>
                                </brorg:info>
                            </extension>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                      </command>
    </epp>
    ';
 
    $response = $client->request($request);

    # Parse XML result
    $doc= new DOMDocument();
    $doc->loadXML($response);
    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes == '1000') {
            # If it's already on the database, verify new domains can be registered 
            $orgprov = ltrim($doc->getElementsByTagName('clID')->item(0)->nodeValue,"0");
            if ($orgprov!=$moduleparams["Username"]) {
                        $values["error"]=_registrobr_lang("notallowed");
                        logModuleCall("registrobr",$values["error"],$request,$response);
                        return $values ;
            } 

    } elseif($coderes != '2303') {
                        $errormsg = _registrobr_lang('registergetorgerrorcode').$coderes._registrobr_lang('msg').$msg."'";
                        if (!empty($reason)) {
                            $errormsg.= _registrobr_lang("reason").$reason."'";
                        } ;
                        logModuleCall("registrobr",$errormsg,$request,$response);
                        $values["error"] = $errormsg;
                        return $values;
    } else {
        
                # Company or individual not in the database, proceed to org contact creation
                $request='<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" 
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                        <command>
                            <create>
                                <contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" 
                                xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 
                                contact-1.0.xsd"> 
                                    <contact:id>dummy</contact:id>
                                    <contact:postalInfo type="loc">
                                        <contact:name>'.$RegistrantContactName.'</contact:name>
                                        <contact:addr>
                                            <contact:street>'.$RegistrantAddress1.'</contact:street>
                                            <contact:street>'.$RegistrantAddress2.'</contact:street>
                                            <contact:street>'.$RegistrantAddress3.'</contact:street>
                                            <contact:city>'.$RegistrantCity.'</contact:city>
                                            <contact:sp>'.$RegistrantStateProvince.'</contact:sp>
                                            <contact:pc>'.$RegistrantPostalCode.'</contact:pc>
                                            <contact:cc>'.$RegistrantCountry.'</contact:cc>
                                        </contact:addr>
                                    </contact:postalInfo>
                                    <contact:voice>'.$RegistrantPhone.'</contact:voice>
                                    <contact:email>'.$RegistrantEmailAddress.'</contact:email>
                                    <contact:authInfo>
                                        <contact:pw/>
                                    </contact:authInfo>
                                </contact:create>
                            </create>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                        </command>
                        </epp>';
                
                $response = $client->request($request);

                # Parse XML result
                $doc= new DOMDocument();
                $doc->loadXML($response);
                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
                $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
                if($coderes != '1000') {
                        $errormsg = _registrobr_lang("registercreateorgcontacterrorcode").$coderes._registrobr_lang('msg').$msg."'";
                        if (!empty($reason)) {
                            $errormsg.= _registrobr_lang("reason").$reason."'";
                        }
                        logModuleCall("registrobr",$errormsg,$request,$response);
                        $values["error"] = $errormsg;
                        return $values;
                }                   
            
                # Org creation
                $request='<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" 
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                            <command>
                                <create>
                                    <contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" 
                                    xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 
                                    contact-1.0.xsd"> 
                                        <contact:id>'.$RegistrantTaxIDDigits.'</contact:id>
                                        <contact:postalInfo type="loc">
                                            <contact:name>'.$RegistrantOrgName.'</contact:name>
                                            <contact:addr>
                                                <contact:street>'.$RegistrantAddress1.'</contact:street>
                                                <contact:street>'.$RegistrantAddress2.'</contact:street>
                                                <contact:street>'.$RegistrantAddress3.'</contact:street>
                                                <contact:city>'.$RegistrantCity.'</contact:city>
                                                <contact:sp>'.$RegistrantStateProvince.'</contact:sp>
                                                <contact:pc>'.$RegistrantPostalCode.'</contact:pc>
                                                <contact:cc>'.$RegistrantCountry.'</contact:cc>
                                            </contact:addr>
                                        </contact:postalInfo>
                                        <contact:voice>'.$RegistrantPhone.'</contact:voice>
                                        <contact:email>'.$RegistrantEmailAddress.'</contact:email>
                                        <contact:authInfo>
                                            <contact:pw/>
                                        </contact:authInfo>
                                    </contact:create>
                                </create>
                                <extension>
                                    <brorg:create xmlns:brorg="urn:ietf:params:xml:ns:brorg-1.0" 
                                    xsi:schemaLocation="urn:ietf:params:xml:ns:brorg-1.0 
                                    brorg-1.0.xsd"> 
                                        <brorg:organization>'.$RegistrantTaxID.'</brorg:organization>
                                        <brorg:contact type="admin">'.$doc->getElementsByTagName('id')->item(0)->nodeValue.'</brorg:contact>
                                    </brorg:create>
                                </extension>
                                <clTRID>'.mt_rand().mt_rand().'</clTRID>
                            </command>
                        </epp>';

                $response = $client->request($request);

                # Parse XML result
                $doc= new DOMDocument();
                $doc->loadXML($response);
                $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
                $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
                if($coderes != '1001') {
                        $errormsg = _registrobr_lang("registercreateorgerrorcode").$coderes._registrobr_lang('msg').$msg."'";
                        if (!empty($reason)) {
                            $errormsg.= _registrobr_lang("reason").$reason."'";
                        }
                        logModuleCall("registrobr",$errormsg,$request,$response);
                        $values["error"] = $errormsg;
                        return $values;
                }           
    }
    # Generate XML for namseverss

	if ($nameserver1 = $params["ns1"]) { 
                        $add_hosts = '
                        <domain:hostAttr>
                            <domain:hostName>'.$nameserver1.'</domain:hostName>
                        </domain:hostAttr>
                        ';
	}
	if ($nameserver2 = $params["ns2"]) { 
                        $add_hosts .= '
                        <domain:hostAttr>
                            <domain:hostName>'.$nameserver2.'</domain:hostName>
                        </domain:hostAttr>
                        ';
	}
	if ($nameserver3 = $params["ns3"]) { 
                        $add_hosts .= '
                        <domain:hostAttr>
                            <domain:hostName>'.$nameserver3.'</domain:hostName>
                        </domain:hostAttr>
                        ';
	}
	if ($nameserver4 = $params["ns4"]) { 
                        $add_hosts .= '
                        <domain:hostAttr>
                            <domain:hostName>'.$nameserver4.'</domain:hostName>
                        </domain:hostAttr>';
	}
	if ($nameserver5 = $params["ns5"]) { 
                        $add_hosts .= '
                        <domain:hostAttr>
                            <domain:hostName>'.$nameserver5.'</domain:hostName>
                        </domain:hostAttr>';
	}

    # Carry on to domain registration
    $request = '
                 <epp xmlns="urn:ietf:params:xml:ns:epp-1.0" 
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                        <command>
                            <create>
                                <domain:create xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" 
                                xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                                    <domain:name>'.$sld.'.'.$tld.'</domain:name>
                                    <domain:period unit="y">'.$regperiod.'</domain:period>
                                    <domain:ns>'.$add_hosts.'</domain:ns>';
                                    if (strlen($moduleparams['TechC'])>2) $request.=' <domain:contact type="tech">'.$moduleparams['TechC'].'</domain:contact>';
                                    $request.='
                                    <domain:authInfo>
                                        <domain:pw/>
                                    </domain:authInfo>
                                </domain:create>
                            </create>
                            <extension>
                                <brdomain:create xmlns:brdomain="urn:ietf:params:xml:ns:brdomain-1.0" 
                                xsi:schemaLocation="urn:ietf:params:xml:ns:brdomain-1.0 
                                brdomain-1.0.xsd"> 
                                    <brdomain:organization>'.$RegistrantTaxID.'</brdomain:organization>
                                </brdomain:create>
                            </extension>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                        </command>
                    </epp>
                ';

    $response = $client->request($request);
    $doc= new DOMDocument();
    $doc->loadXML($response);
    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes != '1001') {
                        $errormsg = _registrobr_lang("registererrorcode").$coderes._registrobr_lang('msg').$msg."'";
                        if (!empty($reason)) {
                            $errormsg.= _registrobr_lang("reason").$reason."'";
                        }
                        logModuleCall("registrobr",$errormsg,$request,$response);
                        $values["error"] = $errormsg;
                        return $values;
    }
    $values["status"] = $msg;
	return $values;
}
                                      
# Function to renew domain

function registrobr_RenewDomain($params) {

	# Grab variables
	$tld = $params["tld"];
	$sld = $params["sld"];
	$regperiod = $params["regperiod"];

    # Get an EPP Connection                    
    $client = _registrobr_Client();
    if (PEAR::isError($client)) {
                        $values["error"] = _registrobr_lang("renewconnerror").$client;
                        logModuleCall("registrobr",$values["error"]);
                        return $values ;
    }
                        
    $request='
            <epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" 
            xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                        <command>
                            <info>
                                <domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                                    <domain:name hosts="all">'.$sld.'.'.$tld.'</domain:name>
                                </domain:info>
                            </info>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                        </command>
            </epp>
            ';

    $response = $client->request($request);
                                     
    # Parse XML result
	$doc= new DOMDocument();
	$doc->loadXML($response);
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes != '1000') {
                        $errormsg = _registrobr_lang("renewinfoerrorcode").$coderes._registrobr_lang('msg').$msg."'";
                        if (!empty($reason)) {
                            $errormsg.= _registrobr_lang("reason").$reason."'";
                        }
                        logModuleCall("registrobr",$errormsg,$request,$response);
                        $values["error"] = $errormsg;
                        return $values;
    }

	# Sanitize expiry date
	$expdate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);

	# Send request to renew
	$request='
            <epp xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                <command>
                    <renew>
                        <domain:renew>
                            <domain:name>'.$sld.'.'.$tld.'</domain:name>
                            <domain:curExpDate>'.$expdate.'</domain:curExpDate>
                            <domain:period unit="y">'.$regperiod.'</domain:period>
                        </domain:renew>
                    </renew>
                    <clTRID>'.mt_rand().mt_rand().'</clTRID>
                </command>
            </epp>
            ';
                                      
    $response = $client->request($request);
   
    # Parse XML result	
	$doc= new DOMDocument();
	$doc->loadXML($response);
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes != '1000') {
                        $errormsg = _registrobr_lang("renewerrorcode").$coderes._registrobr_lang('msg').$msg."'";
                        if (!empty($reason)) {
                            $errormsg.= _registrobr_lang("reason").$reason."'";
                        }
                        logModuleCall("registrobr",$errormsg,$request,$response);
                        $values["error"] = $errormsg;
                        return $values;
    }
    $values["status"] = $msg;
    return $values;

}

# Function to grab contact details

function registrobr_GetContactDetails($params) {

    # Setup include dir
	$include_path = ROOTDIR . '/modules/registrars/registrobr';
	set_include_path($include_path . PATH_SEPARATOR . get_include_path());

	# Include CPF and CNPJ stuff we need
	require_once 'isCnpjValid.php';
	require_once 'isCpfValid.php';
  
	# Grab variables	
	$tld = $params["tld"];
	$sld = $params["sld"];

	# Grab contact details
	$client = _registrobr_Client();
    if (PEAR::isError($client)) {
        $values["error"] = _registrobr_lang("getcontactconnerror").$client;
        logModuleCall("registrobr",$values["error"]);
        return $values ;
    }
    
    $request = '
        <epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0"
		xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <info>
                    <domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:name hosts="all">'.$sld.'.'.$tld.'</domain:name>
                    </domain:info>
                </info>
            </command>
        </epp>
        ';

    $response = $client->request($request);
                                                              
	# Parse XML result		
	$doc= new DOMDocument();
	$doc->loadXML($response);
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes != '1000') {
        $errormsg = _registrobr_lang("getcontacterrorcode").$coderes._registrobr_lang('msg').$msg."'";
        if (!empty($reason)) {
            $errormsg.= _registrobr_lang("reason").$reason."'";
        }
        logModuleCall("registrobr",$errormsg,$request,$response);
        $values["error"] = $errormsg;
        return $values;
    }
    
    # Grab module parameters
    $moduleparams = getregistrarconfigoptions('registrobr');
     
    # Verify provider
    $prov = ltrim($doc->getElementsByTagName('clID')->item(0)->nodeValue,"0");
    if ($prov!=$moduleparams["Username"]) {
        $values["error"] = _registrobr_lang("getcontactnotallowed");
        logModuleCall("registrobr",$values["error"],$request,$response);
        return $values;
    }
    
    $domaininfo=array();
    for ($i=0; $i<=2; $i++) $domaininfo[$doc->getElementsByTagName('contact')->item($i)->getAttribute('type')]=$doc->getElementsByTagName('contact')->item($i)->nodeValue;
    $Contacts["Admin"]=$domaininfo["admin"];
    $Contacts["Tech"]=$domaininfo["tech"];
    
    
    
    # Get TaxPayer ID for obtaining Reg Info
    $RegistrantTaxID=$doc->getElementsByTagName('organization')->item(0)->nodeValue;

    # Returned CNPJ has extra zero at left
    if(isCpfValid($RegistrantTaxID)!=TRUE) { $RegistrantTaxID=substr($RegistrantTaxID,1); };
    $RegistrantTaxIDDigits = preg_replace("/[^0-9]/","",$RegistrantTaxID);

	# Grab reg info
    $request = '
                <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                    <command>
                        <info>
                            <contact:info xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"
                            xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0
                            contact-1.0.xsd">
                                <contact:id>'.$RegistrantTaxIDDigits.'</contact:id>
                            </contact:info>
                        </info>
                        <extension>
                            <brorg:info xmlns:brorg="urn:ietf:params:xml:ns:brorg-1.0"
                            xsi:schemaLocation="urn:ietf:params:xml:ns:brorg-1.0
                            brorg-1.0.xsd">
                                <brorg:organization>'.$RegistrantTaxID.'</brorg:organization>
                            </brorg:info>
                        </extension>
                        <clTRID>'.mt_rand().mt_rand().'</clTRID>
                    </command>
                </epp>
                ';

    $response = $client->request($request);
       
	# Parse XML result

	$doc= new DOMDocument();
	$doc->loadXML($response);
    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes != '1000') {
        $errormsg = _registrobr_lang("getcontactorginfoerrorcode").$coderes._registrobr_lang('msg').$msg."'";
        if (!empty($reason)) {
            $errormsg.= _registrobr_lang("reason").$reason."'";
        }
        logModuleCall("registrobr",$errormsg,$request,$response);
        $values["error"] = $errormsg;
        return $values;
    }

    $Contacts["Registrant"]=$doc->getElementsByTagName('contact')->item(0)->nodeValue;
   
    
    # Companies have both company name and contact name, individuals only have their own name 
    if (isCnpjValid($RegistrantTaxIDDigits)==TRUE) {
        $values["Registrant"][_registrobr_lang("companynamefield")] = $doc->getElementsByTagName('name')->item(0)->nodeValue;
    } else { $values["Registrant"][_registrobr_lang("fullnamefield")] = $doc->getElementsByTagName('name')->item(0)->nodeValue; }

        
    #Get Org, Adm and Tech Contacts
    foreach ($Contacts as $type => $value) {
                    $request = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                                xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                                    <command>
                                        <info>
                                            <contact:info xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"
                                            xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0
                                            contact-1.0.xsd">
                                                <contact:id>'.$value.'</contact:id>
                                            </contact:info>
                                        </info>
                                        <clTRID>'.mt_rand().mt_rand().'</clTRID>
                                    </command>
                            </epp>';

                    $response = $client->request($request);

                    # Parse XML result
                    $doc= new DOMDocument();
                    $doc->loadXML($response);
                    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
                    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
                    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
                    if($coderes != '1000') {
                        $errormsg = _registrobr_lang("getcontacttypeerrorcode").$type._registrobr_lang("getcontacterrorcode").$coderes._registrobr_lang('msg').$msg."'";
                        if (!empty($reason)) {
                            $errormsg.= _registrobr_lang("reason").$reason."'";
                        }
                        logModuleCall("registrobr",$errormsg,$request,$response);
                        $values["error"] = $errormsg;
                        return $values;
                    }
    
                    $values[$type][_registrobr_lang("fullnamefield")] = $doc->getElementsByTagName('name')->item(0)->nodeValue;
                    $values[$type][_registrobr_lang("streetnamefield")] = $doc->getElementsByTagName('street')->item(0)->nodeValue;
                    $values[$type][_registrobr_lang("streetnumberfield")] = $doc->getElementsByTagName('street')->item(1)->nodeValue;
                    $values[$type][_registrobr_lang("addresscomplementsfield")] = $doc->getElementsByTagName('street')->item(2)->nodeValue;
                    $values[$type][_registrobr_lang("citynamefield")] = $doc->getElementsByTagName('city')->item(0)->nodeValue;
                    $values[$type][_registrobr_lang("stateprovincefield")] = $doc->getElementsByTagName('sp')->item(0)->nodeValue;
                    $values[$type][_registrobr_lang("zipcodefield")] = $doc->getElementsByTagName('pc')->item(0)->nodeValue;
                    $values[$type][_registrobr_lang("countrycodefield")] = $doc->getElementsByTagName('cc')->item(0)->nodeValue;
                    $values[$type][_registrobr_lang("phonenumberfield")] = $doc->getElementsByTagName('voice')->item(0)->nodeValue;
                    $values[$type]["Email"] = $doc->getElementsByTagName('email')->item(0)->nodeValue;
                    }        
 	return $values;
}

# Function to save contact details

function registrobr_SaveContactDetails($params) {

    logModuleCall("registrobr","debug",$params["contactdetails"],$params["original"]["contactdetails"]);
                  
    # If nothing was changed, return
    if ($params["contactdetails"]==$params["original"]["contactdetails"]) {
        $values["status"] = _registrobr_lang("savecontactnochange");
        return $values;
    }
    
    # Setup include dir
	$include_path = ROOTDIR . '/modules/registrars/registrobr';
	set_include_path($include_path . PATH_SEPARATOR . get_include_path());
    
    # Include CPF and CNPJ stuff we need
    require_once 'isCnpjValid.php';
    require_once 'isCpfValid.php';

    # Grab variables	
    $tld = $params["tld"];
    $sld = $params["sld"];

    # Grab domain, organization and contact details
    $client = _registrobr_Client();
    if (PEAR::isError($client)) {
        $values["error"] = _registrobr_lang("savecontactconnerror").$client;
        logModuleCall("registrobr",$values["error"]);
        return $values ;
    }
    
    $request = '
        <epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0"
        xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
            <command>
                <info>
                    <domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                        <domain:name hosts="all">'.$sld.'.'.$tld.'</domain:name>
                    </domain:info>
                </info>
            </command>
        </epp>
        ';
  
    $response = $client->request($request);

	# Parse XML result		
	$doc= new DOMDocument();
	$doc->loadXML($response);
	$coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes != '1000') {
            $errormsg = _registrobr_lang("savecontactdomaininfoerrorcode").$coderes._registrobr_lang('msg').$msg."'";
            if (!empty($reason)) {
                $errormsg.= _registrobr_lang("reason").$reason."'";
            }
            logModuleCall("registrobr",$errormsg,$request,$response);
            $values["error"] = $errormsg;
            return $values;
    }
        
    # Grab module parameters
    $moduleparams = getregistrarconfigoptions('registrobr');
    
    # Verify provider
    $prov = ltrim($doc->getElementsByTagName('clID')->item(0)->nodeValue,"0");

    if ($prov!=$moduleparams["Username"]) {
        $values["error"] = _registrobr_lang("savecontactnotalloweed");
        logModuleCall("registrobr",$values["error"],$request,$response);
        return $values;
    }
   
    # Grab Admin, Billing, Tech ID

    $Contacts=array();
    for ($i=0; $i<=2; $i++) $Contacts[ucfirst($doc->getElementsByTagName('contact')->item($i)->getAttribute('type'))]=$doc->getElementsByTagName('contact')->item($i)->nodeValue;
    $NewContacts=$Contacts;

    # Get TaxPayer ID for obtaining Reg Info
    $RegistrantTaxID=$doc->getElementsByTagName('organization')->item(0)->nodeValue;

    # Returned CNPJ has extra zero at left
    if(isCpfValid($RegistrantTaxID)!=TRUE) { $RegistrantTaxID=substr($RegistrantTaxID,1); };
    $RegistrantTaxIDDigits = preg_replace("/[^0-9]/","",$RegistrantTaxID);


    # This flag will signal the need for doing a domain update or not
    $DomainUpdate = FALSE ; 

    # This flag will signal the need for doing a brorg update or not
    $OrgUpdate = FALSE ;
    
    # Verify which contacts need updating
    $ContactTypes = array ("Registrant","Admin","Tech");
    foreach ($ContactTypes as $type)  {
        if ($params["contactdetails"][$type]!=$params["original"][$type]) {

        # Start by creating a new contact with the updated information
        $request='<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" 
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                    xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                        <command>
                            <create>
                                <contact:create xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" 
                                xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 
                                contact-1.0.xsd"> 
                                    <contact:id>dummy</contact:id>
                                    <contact:postalInfo type="loc">
                                        <contact:name>'.(empty($params["contactdetails"][$type]["Nome e Sobrenome"]) ? $params["contactdetails"][$type]["Full Name"] : $params["contactdetails"][$type]["Nome e Sobrenome"]).'</contact:name>
                                        <contact:addr>
                                            <contact:street>';
                                            if (empty($params["contactdetails"][$type][_registrobr_lang("streetnamefield")])) {
                                                $parts=preg_split("/[0-9.]/",$params["contactdetails"][$type]["Address 1"],NULL,PREG_SPLIT_NO_EMPTY);
                                                $request.=$parts.'</contact:street>
                                                    <contact:street>';
                                                $parts=preg_split("/[^0-9.]/",$params["contactdetails"][$type]["Address 1"],NULL,PREG_SPLIT_NO_EMPTY);
                                                $request.=$parts.'</contact:street>
                                                    <contact:street>'.$params["contactdetails"][$type]["Address 2"];
                                            } else $request.=$params["contactdetails"][$type][_registrobr_lang("streetnamefield")].'</contact:street>
                                                        <contact:street>'.$params["contactdetails"][$type][_registrobr_lang("streetnumberfield")].'</contact:street>
                                                        <contact:street>'.$params["contactdetails"][$type][_registrobr_lang("addresscomplementsfield")];
                                            $request.='</contact:street>
                                            <contact:city>'.(empty($params["contactdetails"][$type][_registrobr_lang("citynamefield")]) ? $params["contactdetails"][$type]["City"] : $params["contactdetails"][$type][_registrobr_lang("citynamefield")]).'</contact:city>
                                            <contact:sp>'.(empty($params["contactdetails"][$type][_registrobr_lang("stateprovincefield")]) ? _registrobr_StateProvince($params["contactdetails"][$type]["State"]) : $params["contactdetails"][$type][_registrobr_lang("stateprovincefield")]).'</contact:sp>
                                            <contact:pc>'.(empty($params["contactdetails"][$type][_registrobr_lang("zipcodefield")]) ? $params["contactdetails"][$type]["Postcode"] : $params["contactdetails"][$type][_registrobr_lang("zipcodefield")]).'</contact:pc>
                                            <contact:cc>'.(empty($params["contactdetails"][$type][_registrobr_lang("countrycodefield")]) ? $params["contactdetails"][$type]["Country"] : $params["contactdetails"][$type][_registrobr_lang("countrycodefield")]).'</contact:cc>
                                        </contact:addr>
                                    </contact:postalInfo>
                                    <contact:voice>'.substr((empty($params["contactdetails"][$type][_registrobr_lang("phonenumberfield")]) ? $params["contactdetails"][$type]["Phone Number"] : $params["contactdetails"][$type][_registrobr_lang("phonenumberfield")]),1).'</contact:voice>
                                    <contact:email>'.$params["contactdetails"][$type]["Email"].'</contact:email>
                                    <contact:authInfo>
                                                <contact:pw/>
                                    </contact:authInfo>
                                </contact:create>
                            </create>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                        </command>
                </epp>';
        $response = $client->request($request);

        # Parse XML result
        $doc= new DOMDocument();
        $doc->loadXML($response);
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
        if($coderes != '1000') {
            $errormsg = _registrobr_lang("savecontacttypeerrorcode").$type._registrobr_lang("savecontacterrorcode").$coderes._registrobr_lang('msg').$msg."'";
            if (!empty($reason)) {
                $errormsg.= _registrobr_lang("reason").$reason."'";
            }
            logModuleCall("registrobr",$errormsg,$request,$response);
            $values["error"] = $errormsg;
            return $values;
            }
        
        $NewContacts[$type]=$doc->getElementsByTagName('id')->item(0)->nodeValue;
        if ($type!="Registrant") { $DomainUpdate=TRUE; }
        else {
            $OrgUpdate=TRUE;
            $OrgContactXML=$request;
        }   
        }
    }

    if ($DomainUpdate==TRUE) {
        $NewContacts["Billing"]=$NewContacts["Admin"];
        $request='
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0" 
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
            xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                <command>
                    <update>
                        <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" 
                        xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 
                        domain-1.0.xsd"> 
                            <domain:name>'.$params["sld"].".".$params["tld"].'</domain:name>
                            <domain:add>';
                            foreach ($NewContacts as $type => $id) if ($type!="Registrant") $request.='<domain:contact type="'.strtolower($type).'">'.$id.'</domain:contact>' ;
                            $request.='</domain:add>
                            <domain:rem>';
                            foreach ($Contacts as $type => $id) if ($type!="Registrant") $request.='<domain:contact type="'.strtolower($type).'">'.$id.'</domain:contact>' ;
                            $request.='
                            </domain:rem>
                        </domain:update>
                    </update>
                    <clTRID>'.mt_rand().mt_rand().'</clTRID>
                </command>
            </epp>';
        $response = $client->request($request);
        $doc= new DOMDocument();
        $doc->loadXML($response);
        $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
        $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
        $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
        if($coderes != '1000') {
            $errormsg = _registrobr_lang("savecontactdomainupdateerrorcode").$coderes._registrobr_lang('msg').$msg."'";
            if (!empty($reason)) {
                $errormsg.= _registrobr_lang("reason").$reason."'";
            }
            logModuleCall("registrobr",$errormsg,$request,$response);
            $values["error"] = $errormsg;
            return $values;
        }
        

    }
       
    # Has registrant information changed ?
    if ($OrgUpdate==TRUE) {
        # Grab reg info
        $request = '
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                <command>
                    <info>
                        <contact:info xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"
                            xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0
                            contact-1.0.xsd">
                            <contact:id>'.$RegistrantTaxIDDigits.'</contact:id>
                        </contact:info>
                    </info>
                    <extension>
                        <brorg:info xmlns:brorg="urn:ietf:params:xml:ns:brorg-1.0"
                        xsi:schemaLocation="urn:ietf:params:xml:ns:brorg-1.0
                        brorg-1.0.xsd">
                            <brorg:organization>'.$RegistrantTaxID.'</brorg:organization>
                        </brorg:info>
                    </extension>
                    <clTRID>'.mt_rand().mt_rand().'</clTRID>
                </command>
            </epp>
            ';

            $response = $client->request($request);
            
            # Parse XML result
            $doc= new DOMDocument();
            $doc->loadXML($response);
            $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
            $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
            $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
            if($coderes != '1000') {
                $errormsg = _registrobr_lang("savecontactorginfoeerrorcode").$coderes._registrobr_lang('msg').$msg."'";
                if (!empty($reason)) {
                    $errormsg.= _registrobr_lang("reason").$reason."'";
                }
                logModuleCall("registrobr",$errormsg,$request,$response);
                $values["error"] = $errormsg;
                return $values;
            }

            # Get current org contact
            $Contacts["Registrant"]=$doc->getElementsByTagName('contact')->item(0)->nodeValue;
        
            # With current org contact we can now do an org update
        
            # Parse XML org contact request 
            $doc= new DOMDocument();
            $doc->loadXML($OrgContactXML);
            $request='<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" 
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                    xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                        <command>
                            <update>
                                <contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" 
                                xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 
                                contact-1.0.xsd"> 
                                    <contact:id>'.$RegistrantTaxIDDigits.'</contact:id>
                                    <contact:chg>    
                                        <contact:postalInfo type="loc">
                                            <contact:name>';
                                            if (isCpfValid($RegistrantTaxIDDigits)==TRUE) { $request.=$doc->getElementsByTagName('name')->item(0)->nodeValue; }
                                            else { $request.=( empty($params["contactdetails"]["Registrant"][_registrobr_lang("companynamefield")]) ? $params["contactdetails"]["Registrant"]["Company Name"] : $params["contactdetails"]["Registrant"][_registrobr_lang("companynamefield")]);
                                            }
            
                                            $request.='
                                            </contact:name>
                                            <contact:addr>
                                                <contact:street>'.$doc->getElementsByTagName('street')->item(0)->nodeValue.'</contact:street>
                                                <contact:street>'.$doc->getElementsByTagName('street')->item(1)->nodeValue.'</contact:street>
                                                <contact:street>'.$doc->getElementsByTagName('street')->item(2)->nodeValue.'</contact:street>
                                                <contact:city>'.$doc->getElementsByTagName('city')->item(0)->nodeValue.'</contact:city>
                                                <contact:sp>'.$doc->getElementsByTagName('sp')->item(0)->nodeValue.'</contact:sp>
                                                <contact:pc>'.$doc->getElementsByTagName('pc')->item(0)->nodeValue.'</contact:pc>
                                                <contact:cc>'.$doc->getElementsByTagName('cc')->item(0)->nodeValue.'</contact:cc>
                                            </contact:addr>
                                        </contact:postalInfo>
                                        <contact:voice>'.$doc->getElementsByTagName('voice')->item(0)->nodeValue.'</contact:voice>
                                        <contact:email>'.$doc->getElementsByTagName('email')->item(0)->nodeValue.'</contact:email>
                                    </contact:chg>
                                </contact:update>
                            </update>
                            <extension>
                                <brorg:update xmlns:brorg="urn:ietf:params:xml:ns:brorg-1.0" 
                                xsi:schemaLocation="urn:ietf:params:xml:ns:brorg-1.0 
                                brorg-1.0.xsd"> 
                                    <brorg:organization>'.$RegistrantTaxID.'</brorg:organization>
                                    <brorg:add>
                                        <brorg:contact type="admin">'.$NewContacts["Registrant"].'</brorg:contact>
                                    </brorg:add>
                                    <brorg:rem>
                                        <brorg:contact type="admin">'.$Contacts["Registrant"].'</brorg:contact>
                                    </brorg:rem>
                                    <brorg:chg>';
                                        if (isCnpjValid($RegistrantTaxIDDigits)) $request.='<brorg:responsible>'.$doc->getElementsByTagName('name')->item(0)->nodeValue.'</brorg:responsible>';
                                        $request.='
                                    </brorg:chg>
                                </brorg:update>
                            </extension>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                        </command>
                </epp>';

            $response = $client->request($request);

            # Parse XML result

            $doc= new DOMDocument();
            $doc->loadXML($response);
            $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
            $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
            $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
            if($coderes != '1000') {
                $errormsg = _registrobr_lang("savecontactorgupdateeerrorcode").$coderes._registrobr_lang('msg').$msg."'";
                if (!empty($reason)) {
                    $errormsg.= _registrobr_lang("reason").$reason."'";
                }
            logModuleCall("registrobr",$errormsg,$request,$response);
            $values["error"] = $errormsg;
            return $values;
            }           

    }
    $values = array();
    return $values;
}

# Domain Delete (used in .br only for Add Grace Period)

function registrobr_RequestDelete($params) {
    $client = _registrobr_Client();
    if (PEAR::isError($client)) {
        $values["error"] = _registrobr_lang("deleteconnerror").$client;
        logModuleCall("registrobr",$values["error"]);
        return $values;
    }

    $request = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                    <command>
                        <delete>
                            <domain:delete xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" 
                            xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 
                            domain-1.0.xsd"> 
                                <domain:name>'.$params['sld'].'.'.$params['tld'].'</domain:name>
                            </domain:delete>
                        </delete>
                        <clTRID>'.mt_rand().mt_rand().'</clTRID>
                    </command>
                </epp>
                ';

    $response = $client->request($request);

    # Parse XML
    $doc= new DOMDocument();
    $doc->loadXML($response);
    $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
    $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
    $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
    if($coderes != '1000') {
        $errormsg = _registrobr_lang("deleteerrorcode").$coderes._registrobr_lang("msg").$msg."'";
        if (!empty($reason)) {
            $errormsg.= _registrobr_lang("reason").$reason."'";
        } ;
        logModuleCall("registrobr",$errormsg,$request,$response);
        $values["error"] = $errormsg;
        return $values;
    }
    $values["status"] = _registrobr_lang("domaindeleted");
    return $values ;
}

# Function to create internal .br EPP request

function _registrobr_Client() {

	# Setup include dir
	$include_path = ROOTDIR . '/modules/registrars/registrobr';
	set_include_path($include_path . PATH_SEPARATOR . get_include_path());

	# Include EPP stuff we need
	require_once 'Net/EPP/Client.php';
	require_once 'Net/EPP/Protocol.php';

	# Grab module parameters

	$moduleparams = getregistrarconfigoptions('registrobr');
	if (!isset($moduleparams['TestMode']) && !empty($moduleparams['Certificate'])) {
		$errormsg =  _registrobr_lang("specifypath") ;
		logModuleCall ("registrobr",_registrobr_lang("configerr"),$moduleparams,$errormsg);
		return $errormsg ;
    }

    if (!isset($moduleparams['TestMode']) && !file_exists($moduleparams['Certificate'])) {
        $errormsg =  _registrobr_lang("invalidpath")  ;
        logModuleCall ("registrobr",_registrobr_lang("configerr"),$moduleparams,$errormsg);
        return $errormsg ;
    }

	if (!isset($moduleparams['TestMode']) && !empty($moduleparams['Passphrase'])) {
        $errormsg =   _registrobr_lang("specifypassphrase")  ;
        logModuleCall ("registrobr",_registrobr_lang("configerr"),$moduleparams,$errormsg);
        return $errormsg ;
    }

    # Use OT&E if test mode is set

 	if (!isset($moduleparams['TestMode'])) {
          $Server = 'epp.registro.br' ;
		  $Options = array (
                            'ssl' => array (
                                            'passphrase' => $moduleparams['Passphrase'],
                                            'local_cert' => $moduleparams['Certificate']));

    } else {
            $Server = 'beta.registro.br' ;
            $Options = array (
                              'ssl' => array (
                                            'local_cert' =>  dirname(__FILE__) . '/test-client.pem' ));
    }

    # Create SSL context
    $context = stream_context_create ($Options) ;

	# Create EPP client
	$client = new Net_EPP_Client();

	# Connect
	$Port = 700;
	$use_ssl = true;
	$res = $client->connect($Server, $Port, 3 , $use_ssl, $context);

	# Check for error
	if (PEAR::isError($res)) {
		logModuleCall("registrobr",_registrobr_lang("eppconnect"),"tls://".$Server.":".$Port,$res);
		return $res;

	}

	# Perform login
	$request='
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
                <command>
                    <login>
                        <clID>'.$moduleparams['Username'].'</clID>
                        <pw>'.$moduleparams['Password'].'</pw>
                        <options>
                            <version>1.0</version>
                            <lang>';
                            $request.=($moduleparams['Language']=='Portuguese' ? 'pt' : 'en' );
                            $request.='</lang>
                        </options>
                        <svcs>
                            <objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>
                            <objURI>urn:ietf:params:xml:ns:contact-1.0</objURI>
                            <svcExtension>
                                <extURI>urn:ietf:params:xml:ns:brdomain-1.0</extURI>
                                <extURI>urn:ietf:params:xml:ns:brorg-1.0</extURI>
                                <extURI>urn:ietf:params:xml:ns:secDNS-1.0</extURI>
                                <extURI>urn:ietf:params:xml:ns:secDNS-1.1</extURI>
                            </svcExtension>
                        </svcs>
                    </login>
                    <clTRID>'.mt_rand().mt_rand().'</clTRID>
            </command>
        </epp>
        ';

   $response = $client->request($request);
   $doc= new DOMDocument();
   $doc->loadXML($response);
   $coderes = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
   $msg = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
   $reason = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
   if($coderes != '1000') {
                            $errormsg = _registrobr_lang("epplogin").$coderes._registrobr_lang("msg").$msg."'";
                            if (!empty($reason)) {
                                        $errormsg.= _registrobr_lang("reason").$reason."'";
                            }
                            logModuleCall("registrobr",$errormsg,$request,$response);
    }
    return $client;
}

    
function _registrobr_normaliza($string) {
        
    $string = str_replace('&nbsp;',' ',$string);
    $string = trim($string);
    $string = html_entity_decode($string,ENT_QUOTES,'UTF-8');
        
    //Instead of The Normalizer class ... requires (PHP 5 >= 5.3.0, PECL intl >= 1.0.0)
    $normalized_chars = array( 'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f', ' ' => '');
    
    $string = strtr($string,$normalized_chars);
    $string = strtolower($string);
    return $string;
}
    
function _registrobr_StateProvince($sp) {
        
    if (strlen($sp)==2) return $sp;
    $estado = _normaliza($sp);
    $map = array(
                "acre" => "AC",
                "alagoas" => "AL",
                "amazonas" => "AM",
                "amapa" => "AP",
                "bahia" => "BA",
                "baia" => "BA",
                "ceara" => "CE",
                "distritofederal" => "DF",
                "espiritosanto" => "ES",
                "espiritusanto" => "ES",
                "goias" => "GO",
                "goia" => "GO",
                "maranhao" => "MA",
                "matogrosso" => "MT",
                "matogroso" => "MT",
                "matogrossodosul" => "MS",
                "matogrossosul" => "MS",
                "matogrossodesul" => "MS",
                "minasgerais" => "MG",
                "minasgeral" => "MG",
                "para" => "PA",
                "paraiba" => "PB",
                "parana" => "PR",
                "pernambuco" => "PE",
                "pernanbuco" => "PE",
                "piaui" => "PI",
                "riodejaneiro" => "RJ",
                "riograndedonorte" => "RN",
                "riograndenorte" => "RN",
                "rondonia" => "RO",
                "riograndedosul" => "RS",
                "riograndedesul" => "RS",
                "riograndesul" => "RS",
                "roraima" => "RR",
                "santacatarina" => "SC",
                "sergipe" => "SE",
                "saopaulo" => "SP",
                "tocantins" => "TO"
                );
    if(!empty($map[$estado])){
            return $map[$estado];
        } else {
                return $sp;
        }
    }
                            

function _registrobr_lang($msgid) {

    # Grab module parameters
    $moduleparams = getregistrarconfigoptions('registrobr');
    $msgs = array (
                    "epplogin" => array ("Erro no login EPP c&ocute;digo ","EPP login error code "),
                    "msg" => array (" mensagem '"," message '"),
                    "reason" => array (" motivo '"," reason '"),
                    "eppconnect" => array ("Erro de conex&atilde;o EPP","EPP connect error"),
                    "configerr" => array ("Erro nas op&ccedil;&otilde;es de configura&ccedil;&atilde;o","Config options errorr"),
                    "specifypath" => array ("Favor informar o caminho para o arquivo de certificado","Please specifity path to certificate file"),
                    "invalidpath" => array ("Caminho para o arquivo de certificado inv&aacute;lido", "Invalid certificate file path"),
                    "specifypassphrase" => array ("Favor especificar a frase secreta do certificado", "Please specifity certificate passphrase"),
                    "domaindeleted" => array ("Remo&ccedil&atilde;o de dom&iacute;nio bem-sucedida","Sucessful domain deletion"),
                    "deleteerrorcode" => array ("Erro na remo&ccedil;&atilde;o de dom&iacutenio c&oacute;digo ","Domain delete: error code "),
                    "deleteconnerror" => array ("Falha na conex&atilde;o EPP ao tentar remover dom&iacuten;io erro ","Domain delete: EPP connection error "),
                    "getnsconnerror" => array ("Falha na conex&atilde;o EPP ao tentar obter servidores DNS erro ", "get nameservers: EPP connection error "),
                    "setnsconnerror" => array ("Falha na conex&atilde;o EPP ao tentar alterar servidores DNS erro ", "set nameservers: EPP connection error "),
                    "setnsgeterrorcode" => array ("Falha ao tentar obter servidores DNS atuais para alterar servidores DNS c&oacute;digo ", "set nameservers: error getting nameservers code "),
                    "setnsupdateerrorcode" => array ("Falha ao alterar servidores DNS c&oacute;digo ","set nameservers: update servers error code "),
                    "updatepending" => array ("Servidores DNS do dom&iacutenio ser&atildeo atualizados em at&eacute; 30 minutos.","Domain update Pending. Based on .br policy, the estimated time taken is up to 30 minutes."),
                    "cpfcnpjrequired" => array ("Registro de dom&iacute;nios .br requer CPF ou CNPJ","register domain: .br registrations require valid CPF or CNPJ"),
                    "companynamerequired" => array ("Registros com CNPJ requerem nome da empresa preenchido",".br registrations with CNPJ require Company Name to be filled in"),
                    "registerconnerror" => array ("Falha na conex&atilde;o EPP ao tentar registrar dom&iacute;nio erro ", "register domain: EPP connection error "),
                    "notallowed" => array ("Entidade s&ocute; pode registrar dom&iacute;nios por provedor atualmente designado.", "entity can only register domains through designated registrar."),
                    "registergetorgerrorcode" => array ("Falha ao obter status de entidade para registrar dom&iacute;nio erro ","register domain: get org status error code "),
                    "registercreateorgcontacterrorcode" => array ("Falha ao criar contato para entidade erro ","register domain: create org contact error code "),
                    "registercreateorgerrorcode" => array ("Falha ao criar entidade para registrar dom&iacute;nio erro ","register domain: create org error code "),
                    "registererrorcode" => array ("Falha ao registrar dom&iacute;nio erro ","register domain error code "),
                    "renewconnerror" => array ("Falha na conex&atildeo EPP ao renovar dom&iacute;nio erro ", "renew domain: EPP connection error "),
                    "renewinfoerrorcode" => array ("Falha ao obter informa&ccedil;&otilde;es de dom&iacute;nio ao renovar dom&iacute;nio erro ", "renew: domain info error code "),
                    "renewerrorcode" => array ("Falha ao renovar dom&iacute;nio erro ","domain renew: error code "),
                    "getcontactconnerror" => array ("Falha na conex&atilde;o EPP ao obter dados de contato erro ","get contact details: EPP connection error "), 
                    "getcontacterrorcode" => array ("Falha ao obter dados de contato erro ", "get contact details: domain info error code "),
                    "getcontactnotallowed" => array ("Somente provedor designado pode obter dados deste dom&iacute;nio.","get contact details: domain is not designated to this registrar."),
                    "getcontactorginfoerrorcode" => array ("Falha ao obter informa&ccedil;&otilde;es de entidade detentora de dom&iacute;nio erro ","get contact details: organization info error code "),
                    "getcontacttypeerrorcode" => array ("Falha ao obter dados de contato do tipo ","get contact details: "),
                    "getcontacterrorcode" => array ("c&ocute;digo de erro ","contact info error code "),
                    "savecontactnochange" => array ("nenhuma altera&ccedil;&atilde;o","nothing to change"),
                    "savecontactconnerror" => array ("Falha na conex&atilde;o EPP ao gravar contatos erro ", "save contact details: EPP connection error "),
                    "savecontactdomaininfoerrorcode" => array ("Falha ao obter dados de dom&iacute;nio para gravar contatos erro ","set contact details: domain info error code"),
                    "savecontactnotalloweed" => array ("Somente provedor designado pode alterar dados deste dom&iacute;nio.", "Set contact details: domain is not designated to this registrar."),
                    "savecontacttypeerrorcode" => array ("Falha ao criar novo contato do tipo ","save contact details: "),
                    "savecontacterrorcode" => array ("c&ocute;digo de erro ","contact create error code "),
                    "savecontactdomainupdateerrorcode" => array ("Falha ao atualizar dom&iacute;nio ao modificar contatos erro ","set contact: domain update error code "),
                    "savecontactorginfoeerrorcode" => array ("Falha de obten&ccedil;&atilde;o de informa&ccedil;&otilde;es de entidade ao modificar contatos erro ","set contact: org info error code "),
                    "savecontactorgupdateerrorcode" => array ("Falha ao atualizar entidade ao modificar contatos erro ","set contact: org update error code "),
                   
                       
                    "companynamefield" => array ("Razao Social","Company Name"),
                    "fullnamefield" => array ("Nome e Sobrenome","Full Name"),
                    "streetnamefield" => array ("Logradouro","Street Name"),
                    "streetnumberfield" => array ("Numero", "Street Number"),
                    "addresscomplementsfield" => array ("Complemento", "Address Complements"),
                    "citynamefield" => array ("Cidade","City"),
                    "stateprovincefield" => array ("Estado","State or Province"),
                    "zipcodefield" => array ("CEP","Zip code"),
                    "countrycodefield" => array ("Pais","Country"),
                    "phonenumberfield" => array ("Fone","Phone"),
                    );                   
         
    $langmsg = ($moduleparams["Language"]=="Portuguese" ? $msgs["$msgid"][0] : $msgs["$msgid"][1] );
    return $langmsg;
}

?>

