<?php

class Ups
{
    private $accessKey, $userId, $password, $wsdl, $operation, $endpointUrl, $type, $rateTypes;

    protected $request = [];
    protected $apiRequest = [];
    
    /**
     * Sets the shipping type to either freight or standard
     *
     */
    public function setShippingType($type)
    {
        $this->type = $type;
    }
    
    /**
     * Sets the rate types in an array ['UPS Ground','UPS Next Day Air'], etc.
     *
     */
    public function setRateTypes($rateTypes)
    {
        $this->rateTypes = $rateTypes;
    }
    
    /**
     * Set the user id from your UPS account
     *
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
    
    /**
     * Set the password from your UPS account
     *
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
    
    /**
     * Set the access key from your UPS account
     *
     */
    public function setAccessKey($key)
    {
        $this->accessKey = $key;
    }
    
    /** 
     * Returns a list of rates and their costs
     *
     */
    public function getRates()
    {
        $codes = $this->selectUpsCodes();
        
        foreach ($codes as $code) {
            $this->setService($code);
            $rates[$code['description']] = $this->processRate();
            $this->unsetService();
        }
        
        return $rates;
    }

    /**
     * Runs an api call to get the rates for a shipment
     *
     */
    public function processRate()
    {   
        return $this->getRate();
    }

    
    /**
     * Configures the wsdl and endpoint urls
     *
     */
    private function setOptions()
    {   
        $rateWsdl = 'require/RateWS.wsdl';
        $rateOption = 'ProcessRate';
        $rateEndpointUrl = 'https://wwwcie.ups.com/webservices/Rate';
        
        $freightRateWsdl = 'require/FreightRate.wsdl';
        $freightRateOption = 'ProcessFreightRate';
        $freightRateEndpointUrl = 'https://wwwcie.ups.com/webservices/FreightRate';

        switch ($this->type) {
            case "standard":
                $this->wsdl = $rateWsdl; 
                $this->operation = $rateOption;
                $this->endpointUrl = $rateEndpointUrl;
                break;
            case "freight":
                $this->wsdl = $freightRateWsdl; 
                $this->operation = $freightRateOption;
                $this->endpointUrl = $freightRateEndpointUrl;
                break;
        }
    }
    
    /**
     * Haven't the foggiest what this is for, but if you need it, it's there
     *
     */
    public function setRequestOption($option)
    {
        $this->request['Request'] = [
            'RequestOption' => $option
        ];
    }
    
    /**
     * @TODO: find a listing of these and update
     *
     */
    public function setPickupType($pickupType)
    {
        $this->request['PickupType'] = [
            'Code' => $pickupType['code'],
            'Description' => $pickupType['description']
        ];
    }
    
    /**
     * Haven't the foggiest what this is for, but if you need it, it's there
     *
     */
    public function setCustomerClassification($classification)
    {
        $this->request['CustomerClassification'] = [
            'Code' => $classification['code'],
            'Classification' => $classification['classification']
        ];
    }

    /**
     * Sets the shipper
     *
     */
    public function setShipper($shipper)
    {
        $this->apiRequest['Shipper'] = [
            'Name' => $shipper['name'],
            'ShipperNumber' => $shipper['shipperNumber'],
            'Address' => [
                'AddressLine' => [$shipper['addressLine']],
                'City' =>  $shipper['city'],
                'StateProvinceCode' => $shipper['stateProvinceCode'],
                'PostalCode' => $shipper['postalCode'],
                'CountryCode' => $shipper['countryCode'],
            ]
        ];
    }
    
    /**
     * Sets the ship from address
     *
     */
    public function setShipFrom($address)
    {
        $this->apiRequest['ShipFrom'] = [
            'Name' => $address['name'],
            'Address' => [
                'AddressLine' => [$address['addressLine']],
                'City' =>  $address['city'],
                'StateProvinceCode' => $address['stateProvinceCode'],
                'PostalCode' => $address['postalCode'],
                'CountryCode' => $address['countryCode'],
            ]
        ];
    }

    /**
     * Sets the shipto address
     *
     */
    public function setShipTo($address)
    {
        $this->apiRequest['ShipTo'] = [
            'Name' => $address['name']
            'Address' => [
                'AddressLine' => [$address['addressLine']],
                'City' =>  $address['city'],
                'StateProvinceCode' => $address['stateProvinceCode'],
                'PostalCode' => $address['postalCode'],
                'CountryCode' => $address['countryCode'],
                'ResidentialAddressIndicator' => ''
            ]
        ];
    }
    
    /**
     * Sets the payment information
     *
     */
    public function setPaymentInformation($payment)
    {
        $this->apiRequest['PaymentInformation'] = [
            'Payer' => [
                'Name' => $payment['name'],
                'Address' => [
                    'AddressLine' => [$payment['payer']['addressLine']],
                    'City' =>  $payment['payer']['city'],
                    'StateProvinceCode' => $payment['payer']['stateProvinceCode'],
                    'PostalCode' => $payment['payer']['postalCode'],
                    'CountryCode' => $payment['payer']['countryCode'],
                ],
            ],
            'ShipmentBillingOption' => [
                'Code' => $payment['shipmentBillingOption']['code'],
                'Description' => $payment['shipmentBillingOption']['description']
            ],
        ];
    }

    /**
     * Sets the service.  Ex. UPS Ground 
     *
     */
    private function setService($service)
    {
        $this->apiRequest['Service'] = [
            'Code' => $service['code'],
            'Description' => $service['description']
        ];

    }
    
    /**
     * @TODO see if you can request multiple services at once
     *
     */
    private function unsetService()
    {
        unset($this->apiRequest['Service']);
    }

    /**
     * Sets the handling unit for a freight shipment
     *
     */
    public function setHandlingUnit($handling)
    {
        $this->apiRequest['HandlingUnitOne'] = [
            'Quantity' => $handling['quantity'],
            'Type' => [
                'Code' => $handling['type']['code'],
                'Description' => $handling['type']['description'],
            ]
        ];
    }
    
    /**
     * Sets the packages for a shipment
     *
     */
    public function setPackages($packages)
    {
        foreach ($packages as $package) {
            
            $results[] = $this->setPackage($package);
        }
        
        $this->apiRequest['Package'] = $results;
    }
    
    /**
     * Sets the package information
     *
     */
    private function setPackage($package)
    {
        return [
            'PackagingType' => [
                'Code' => $package['packagingType']['code'],
                'Description' => $package['packagingType']['description']
            ],
            'Dimensions' => [
                'Length' => $package['dimensions']['length'],
                'Width' => $package['dimensions']['width'],
                'Height' => $package['dimensions']['height'],
                'UnitOfMeasurement' => [
                    'Code' => $package['dimensions']['unitOfMeasurement']['code'],
                    'Description' => $package['dimensions']['unitOfMeasurement']['description'],
                ],
            ],
            'PackageWeight' => [
                'Weight' => $package['packageWeight']['weight'],
                'UnitOfMeasurement' => [
                    'Code' => $package['packageWeight']['unitOfMeasurement']['code'],
                    'Description' => $package['packageWeight']['unitOfMeasurement']['description']
                ]
            ]  
        ];
    }

    /**
     * Sets weight and other information for a freight shipment
     *
     */
    public function setCommodityFreight($options)
    {
        $this->apiRequest['Commodity'] = [
            'CommodityID' => $options['commodityId'],
            'Description' => $options['description'],
            'Weight' => [
                'UnitOfMeasurement' => [
                    'Code' => $options['weight']['unitOfMeasurement']['code'],
                    'Description' => $options['weight']['unitOfMeasurement']['description']
                ],
            'Value' => $options['value'],
            ],
            'NumberOfPieces' => $options['numberOfPieces'],
            'PackagingType' => [
                'Code' => $options['packagingType']['code'],
                'Description' => $options['packagingType']['description']
            ],
            'DangerousGoodsIndicator' => $options['dangerousGoodsIndicator'],
            'CommodityValue' => [
                'CurrencyCode' => $options['commodityValue']['currencyCode'],
                'MonetaryValue' => $options['commidityValue']['monetaryValue']
            ],
            'FreightClass' => $options['freightClass'],
            'NMFCCommodityCode' => $options['nmfcCommodityCode'],
            'Dimensions' => [
                'UnitOfMeasurement' => [
                    'Code' => $options['dimensions']['unitOfMeasurement']['code'],
                    'Description' => $options['dimensions']['unitOfMeasurement']['description']
                ],
                'Length' => $options['dimensions']['length'],
                'Width' => $options['dimensions']['width'],
                'Height' => $options['dimensions']['height']
            ],
        ];
    }
   
    /**
     *
     * Once again, the ups example doesn't explain what this could be set to
     *
     */
    public function setSpecialOptions($options)
    {
        $this->apiRequest['ShipmentServiceOptions'] = $options['shipmentServiceOptions'];
        $this->apiRequest['LargePackageIndicator'] = $options['largePackageIndicator'];
    }
    
    /**
     *
     * Gets the rate for a shipment
     *
     */
    private function getRate()
    {
        $this->setOptions();
        $this->request['Shipment'] = $this->apiRequest;
        
        try {
            $client = new SoapClient(__DIR__.$this->wsdl, [
                'soap_version' => 'SOAP_1_1',
                'trace' => 1,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false, 
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]),
        
            ]);
        
            $client->__setLocation($this->endpointUrl);
        
            $header = new SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0', 'UPSSecurity', [
                'UsernameToken' => [
                    'Username' => $this->userId,
                    'Password' => $this->password
                ],
        
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => $this->accessKey
                ],
            ]);
        
            $client->__setSoapHeaders($header);
            
            $response = $client->__soapCall($this->operation, [$this->request]);
        
            return $response->RatedShipment->TotalCharges;
        
            //die($resp->TotalShipmentCharge->MonetaryValue);
        } catch (SoapFault $e) {
            die($client->__getLastResponse());
        } catch (Exception $ex) {
        
            //ob_start();
            echo $ex;
        }
    }

    /**
     * A list of Standard UPS shipping codes and their descriptions
     *
     */
    private function upsCodes()
    {
        return [
            //US
            ['code' => '01', 'description' => 'UPS Next Day Air'],
            ['code' => '02', 'description' => 'UPS 2nd Day Air'],
            ['code' => '03', 'description' => 'UPS Ground'],
            ['code' => '59', 'description' => 'UPS 2nd Day Air A.M.'],
            ['code' => '12', 'description' => 'UPS 3 Day Select'],
            ['code' => '13', 'description' => 'UPS Next Day Air Saver'],
            ['code' => '14', 'description' => 'UPS Next Day Air Early A.M.'],
            //International
            ['code' => '11', 'description' => 'UPS Standard'],
            ['code' => '07', 'description' => 'UPS Worldwide Express'],
            ['code' => '08', 'description' => 'UPS Worldwide Expedited'],
            ['code' => '54', 'description' => 'UPS Worldwide Express Plus'],
            ['code' => '65', 'description' => 'UPS Worldwide Saver']
        ];
    }

    /**
     * A list of UPS Freight Codes
     *
     */
    private function upsFreightCodes()
    {
        return [
            ['code' => '308', 'description' => 'UPS Freight LTL'],
            ['code' => '309', 'description' => 'UPS Freight LTL - Guaranteed'],
            ['code' => '334', 'description' => 'UPS Freight® LTL - Guaranteed A.M.'],
            ['code' => '349', 'description' => 'Standard LTL']
        ];
    }

    /**
     * Sets the rates and their codes based on the selected rate types
     *
     */
    private function selectUpsCodes()
    {
        $options = $this->rateTypes;
        
        $returnCodes = [];
        $codes = ($this->type == 'standard') ? $this->upsCodes() : $this->upsFreightCodes();

        foreach ($codes as $code) {
            if (in_array($code['description'],$options)) {
                $returnCodes[] = $code;
            }
        }
        return $returnCodes;
    }
}

