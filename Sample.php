<?php
    
    /* Sample Rate Returns for Standard and Freight */
    
    // Standard Shipment
    $shipment = new Ups();
    $shipment->setUserId('bobisyouruncle');
    $shipment->setPassword('youruncleisyourcousin');
    $shipment->setAccessKey('12344567890');
    $shipment->setShippingType('standard');
    $shipment->setRequestOption('Rate');
    $shipment->setPickupType(['code' => '01','description' => 'Daily Pickup']);
    $shipment->setCustomerClassification(['code' => '01','classification' => 'Classification']);
    $shipment->setShipper([
        'name' => 'Target Field',
        'shipperNumber' => '222006',
        'addressLine' => '1 Twins Way',
        'city' => 'Minneapolis',
        'stateProvinceCode' => 'MN',
        'postalCode' => '55403',
        'countryCode' => 'US'
    ]);
    $shipment->setShipFrom([
        'name' => 'Target Field',
        'addressLine' => '1 Twins Way',
        'city' => 'Minneapolis',
        'stateProvinceCode' => 'MN',
        'postalCode' => '55403',
        'countryCode' => 'US'
    ]);
    $shipment->setShipTo([
        'name' => 'Wrigley Field',
        'addressLine' => '1060 W. Addison St.',
        'city' => 'Chicago',
        'stateProvinceCode' => 'IL',
        'postalCode' => '60613',
        'countryCode' => 'US',
        'residentialAddressIndicator' => ''
    ]);
    $package1 = [
           'packagingType' => [
               'code' => '02',
               'description' => 'Rate'
            ],
            'dimensions' => [
                'unitOfMeasurement' => [
                    'code' => 'IN',
                    'description' => 'inches',
                ],
                'length' => 5,
                'width' => 4,
                'height' => 10
            ],
            'packageWeight' => [
                'weight' => 1,
                'unitOfMeasurement' => [
                    'code' => 'LBS',
                    'description' => 'Pounds'
                ]
            ]
        ];
    $package2 =[
           'packagingType' => [
               'code' => '02',
               'description' => 'Rate'
            ],
            'dimensions' => [
                'unitOfMeasurement' => [
                    'code' => 'IN',
                    'description' => 'inches',
                ],
                'length' => 5,
                'width' => 5,
                'height' => 5
            ],
            'packageWeight' => [
                'weight' => 5,
                'unitOfMeasurement' => [
                    'code' => 'LBS',
                    'description' => 'Pounds'
                ]
            ]
        ];
    $shipment->setPackages([$package1,$package2]);
    $shipment->setSpecialOptions(['shipmentServiceOptions' => '','largePackageIndicator' => '']);
    $shipment->setRateTypes(['UPS Ground','UPS 2nd Day Air','UPS 3 Day Select']);
    
    echo "<pre>";var_dump($shipment->getRates());exit;
    
    
    // Freight Shipment
    $shipment = new Ups();
    
    $shipment->setUserId('bobisyouruncle');
    $shipment->setPassword('youruncleisyourcousin');
    $shipment->setAccessKey('12344567890');
    
    $shipment->setRequestOption('RateChecking Option');
    $shipment->setShipFrom([
        'name' => 'Target Field',
        'addressLine' => '1 Twins Way',
        'city' => 'Minneapolis',
        'stateProvinceCode' => 'MN',
        'postalCode' => '55403',
        'countryCode' => 'US'
    ]);
    $shipment->setShipTo([
        'name' => 'Wrigley Field',
        'addressLine' => '1060 W. Addison St.',
        'city' => 'Chicago',
        'stateProvinceCode' => 'IL',
        'postalCode' => '60613',
        'countryCode' => 'US'
    ]);
    $shipment->setPaymentInformation([
        'name' => 'Target Field',
        'payer' => [
            'addressLine' => '1 Twins Way',
            'city' => 'Minneapolis',
            'stateProvinceCode' => 'MN',
            'postalCode' => '55403',
            'countryCode' => 'US'
        ],
        'shipmentBillingOption' => [
            'code' => '10',
            'description' => 'PREPAID'
        ]
    ]);
    $shipment->setHandlingUnit([
        'quantity' => '20',
        'type' => [
            'code' => 'PLT',
            'description' => 'PALLET'
        ]
    ]);
    $shipment->setCommodityFreight([
        'commodityId' => '',
        'description' => 'No Description',
        'weight' => [
            'unitOfMeasurement' => [
                'code' => 'LBS',
                'description' => 'Pounds'
            ],
            'value' => '4',
        ],
        'numberOfPieces' => '1',
        'packagingType' => [
            'code' => '02',
            'description' => 'BOX'
        ],
        'dangerousGoodsIndicator' => '',
        'commodityValue' => [
            'currencyCode' => 'USD',
            'monetaryValue' => '24.99'
        ],
        'freightClass' => '92.5',
        'nmfcCommodityCode' => '116030',
        'dimensions' => [
            'unitOfMeasurement' => [
                'code' => 'IN',
                'description' => 'Inches'
            ],
            'length' => '23',
            'width' => '17',
            'height' => '45'
        ]
    ]);
    
    $shipment->setRateTypes(['Standard LTL']);
    
    
    echo "<pre>";var_dump($shipment->getRates(['UPS Freight LTL','Standard LTL']));exit;
