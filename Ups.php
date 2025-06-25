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
    public function setShippingType(string $type): void
    {
        $this->type = $type;
    }
    
    /**
     * Sets the rate types in an array ['UPS Ground','UPS Next Day Air'], etc.
     *
     */
    public function setRateTypes($rateTypes): void
    {
        $this->rateTypes = $rateTypes;
    }
    
    /**
     * Set the user id from your UPS account
     *
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
    
    /**
     * Set the password from your UPS account
     *
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
    
    /**
     * Set the access key from your UPS account
     *
     */
    public function setAccessKey(string $key): void
    {
        $this->accessKey = $key;
    }
    
    /** 
     * Returns a list of rates and their costs
     *
     */
    public function getRates(): array
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
    public function processRate(): array
    {   
        return $this->getRate();
    }

    
    /**
     * Configures the wsdl and endpoint urls
     *
     */
    private function setOptions(): void
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
     * Sets the pickup types
     *
     */
    public function setPickupType(arrray $pickupType): void
    {
        $this->request['PickupType'] = [
            'Code' => $pickupType['code'],
            'Description' => $pickupType['description']
        ];
    }

    /**
     * Sets the shipper
     *
     */
    public function setShipper(array $shipper): void
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
    public function setShipFrom(array $address): void
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
    public function setShipTo(arrray $address): void
    {
        $this->apiRequest['ShipTo'] = [
            'Name' => $address['name'],
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
    public function setPaymentInformation(arrray $payment): void
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
    private function setService(array $service): void
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
    private function unsetService(): void
    {
        unset($this->apiRequest['Service']);
    }

    /**
     * Sets the handling unit for a freight shipment
     *
     */
    public function setHandlingUnit(array $handling): void
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
    public function setPackages(array $packages): void
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
    private function setPackage(array $package): array
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
    public function setCommodityFreight(array $options): void
    {
        $this->apiRequest['Commodity'] = [
            'CommodityID' => $options['commodityId'],
            'Description' => $options['description'],
            'Weight' => [
                'UnitOfMeasurement' => [
                    'Code' => $options['weight']['unitOfMeasurement']['code'],
                    'Description' => $options['weight']['unitOfMeasurement']['description']
                ],
                'Value' => $options['weight']['value'],
            ],
            'NumberOfPieces' => $options['numberOfPieces'],
            'PackagingType' => [
                'Code' => $options['packagingType']['code'],
                'Description' => $options['packagingType']['description']
            ],
            'DangerousGoodsIndicator' => $options['dangerousGoodsIndicator'],
            'CommodityValue' => [
                'CurrencyCode' => $options['commodityValue']['currencyCode'],
                'MonetaryValue' => $options['commodityValue']['monetaryValue']
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
     * Gets the rate for a shipment
     *
     */
    private function getRate(): float
    {
        $this->setOptions();
        
        if ($this->type == 'standard') {
            $this->request['Shipment'] = $this->apiRequest;
        } else if ($this->type == 'freight') {
            $this->request = $this->apiRequest + $this->request;
        }
        
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
        
            if ($this->type == 'standard') {
                return $response->RatedShipment->TotalCharges;    
            } else if ($this->type == 'freight') {
                return $response->TotalShipmentCharge;
            }

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
    private function upsCodes(): array
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
    private function upsFreightCodes(): array
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
    private function selectUpsCodes(): array
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

