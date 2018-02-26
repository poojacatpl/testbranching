<?php
class Zend_Controller_Action_Helper_Flight extends Zend_Controller_Action_Helper_Abstract
{
    public function getFlightClasses() {
        //return array('1' => 'Economy', '2' => 'Premium Economy', '3' => 'Business');
        return array('1' => 'All','2' => 'Economy', '3' => 'Premium Economy', '4' => 'Business','5' => 'PremiumBusiness','6' => 'First');
    }
    
    public function getTimeFromApiString($string) {
        if(empty($string)) return '';
        
        $arr = explode(" ", $string);
        $arr = explode(".",$arr[1]);
        return $arr[0];
    }
    
    
    public function getTwoWayTimeFromApiString($string) {
        if(empty($string)) return '';
        
        $arr = explode("T", $string);
        return $arr[1];
    }
    
    
    public function getDateFromApiString($string) {
        if(empty($string)) return '';
        
        $arr = explode("T", $string);
        return date("d-m-Y", strtotime($arr[0]));
        //return $arr[0];
    }
    
    
    public function getDateFromString($string) {
        if(empty($string)) return '';
        
        $arr = explode(" ", $string);
        return date("d-m-Y", strtotime($arr[0]));
    }
    
    
    
    
    public function formatTimeFromApiString($string) {
        if(empty($string)) return '';
        
        $arr = explode("T", $string);
        
        $date = new DateTime($arr[0]);
        
        return $date->format('d F Y').' '.$arr[1];
    }
    
    public function authenticateAPI() {
    
        $data = array(
                'ClientId' => FLIGHT_API_CLIENT_ID,
                'UserName' => FLIGHT_API_USER,
                'Password' => FLIGHT_API_PASSWORD,
                'EndUserIp' => $_SERVER['REMOTE_ADDR']
        );
    
        $data_string = json_encode($data); 

        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_URL, TBO_AUTHENTICATE_URL_PRODUCTION);
        curl_setopt($ch, CURLOPT_URL, TBO_AUTHENTICATE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        //curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($data_string)                                                                       
        ));       
        $output = curl_exec($ch);

        $response = json_decode($output,true); 

        $tokenId =  $response['TokenId'];
        curl_close($ch);
        
        return $tokenId ;
    }
    
    
    
    /*  Added  by Pardeep Panchal */
    
    

    public function generateInvoiceNumbers($start) {
        
        if (date('m') < 4) {//Upto March
            $strFinancialYear = (date('y')-1) . '' . date('y');
        } else {//After March
            $strFinancialYear = date('y') . '' . (date('y') + 1);
        }
        
        return "GTX/".$strFinancialYear."/".str_pad($start, 5, "0", STR_PAD_LEFT);;
    }
    
    
    public function convertAmountToWords($number) {

        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' point ';
        $dictionary = array(
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'fourty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety',
            100 => 'hundred',
            1000 => 'thousand',
            1000000 => 'million',
            1000000000 => 'billion',
            1000000000000 => 'trillion',
            1000000000000000 => 'quadrillion',
            1000000000000000000 => 'quintillion'
        );

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                    'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . $this->convertAmountToWords(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->convertAmountToWords($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertAmountToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertAmountToWords($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    public function getArrivalDepartureIndianFormat($string) {
        if(empty($string)) return '';
        
        $arr = explode(" ", $string);
        
        $date = new DateTime($arr[0]);
        
        return $date->format('d M Y').' '.substr(@$arr[1],0,5);
    }
    
    
    public function getDateTimeFromApiString($string) {
        if(empty($string)) return '';
        
        $arr = explode("T", $string);
        
        $date = new DateTime($arr[0]);
        
        return $date->format('d M Y').' '.substr(@$arr[1],0,5);
    }
    
    
    public function getDateFormatFromDbDates($string) { // get date & time
        $arrFormatedDateTime = array();
        $arr = explode(" ", $string);
        if(count($arr) > 0){
            $date = new DateTime($arr[0]);
            $strDate = $date->format('d M y');
            $strTime = @substr($arr[1],0,5);
            $arrFormatedDateTime = array(
                'strDate' => $strDate,
                'strTime' => $strTime
            );  
        }
        
        return $arrFormatedDateTime;
    }
    
    
    public function getTimeFlightApiResponse($string) {
        if(empty($string)) return '';
        
        $arr = explode("T", $string);
        $arr = explode(".",$arr[1]);
        return substr($arr[0],0,5);
    }
    
    
    
    public function searchApiFlights($arrSessionData = array()){
        
        
        
        $strFlightRoute = trim($arrSessionData['route']);
        $strSourceAirportCode = $arrSessionData['from'];
        $strDestinationAirportCode = $arrSessionData['to'];
        $strDepatureDate = $arrSessionData['departure_dates'];
        $adultCount = $arrSessionData['adults'];
        $childCount = $arrSessionData['child'];
        $infantCount = $arrSessionData['infant'];
        $intMemberCount = $adultCount + $childCount + $infantCount;

        $origin = $arrSessionData['sourceCityAirportCode'];
        $destination = $arrSessionData['destinationCityAirportCode'];
        $preferredDepartureTime = $arrSessionData['departure_dates'];
        $preferredArrivalTime = $arrSessionData['departure_dates'];
        $intSourceCityId = trim($arrSessionData['sourceCityId']);
        $intDestinationCityId = trim($arrSessionData['destinationCityId']);
        $interNationalSearch = isset($arrSessionData['interNationalSearch'])?$arrSessionData['interNationalSearch']:false;

        $preferredDepartureTime = Zend_Controller_Action_HelperBroker::getStaticHelper('DateFormat')->cal2Db($preferredDepartureTime, 'd/m/y')."T00:00:00";
        $preferredArrivalTime = Zend_Controller_Action_HelperBroker::getStaticHelper('DateFormat')->cal2Db($preferredArrivalTime, 'd/m/y')."T00:00:00";
        
        
        $strReturnOrigin = $arrSessionData['destinationCityAirportCode'];
        $strReturnDestination = $arrSessionData['sourceCityAirportCode'];
        $preferredReturnDepartureTime = $arrSessionData['return_dates'];
        $preferredReturnArrivalTime = $arrSessionData['return_dates'];
        $preferredReturnDepartureTime = Zend_Controller_Action_HelperBroker::getStaticHelper('DateFormat')->cal2Db($preferredReturnDepartureTime, 'd/m/y')."T00:00:00";
        $preferredReturnArrivalTime = Zend_Controller_Action_HelperBroker::getStaticHelper('DateFormat')->cal2Db($preferredReturnArrivalTime, 'd/m/y')."T00:00:00";
            
        
        
        $preferredFlightClassType = $arrSessionData['flight_class'];
        
        $tokenId   = $this->authenticateAPI();
        
        $arrFlightSearchResponse = array();
        
        if($strFlightRoute == '1'){
            $datah = array(
                        'EndUserIp' => $_SERVER['REMOTE_ADDR'],
                        'TokenId' => $tokenId,
                        "AdultCount" => $adultCount,
                        "ChildCount" => $childCount,
                        "InfantCount" => $infantCount,
                        "DirectFlight" => "false",
                        "OneStopFlight" => "false",
                        "JourneyType" => "1",
                        "PreferredAirlines" => NULL,
                        "Segments" => [array('Origin' => $origin, 'Destination' => $destination, 'FlightCabinClass' => $preferredFlightClassType, "PreferredDepartureTime" => $preferredDepartureTime,
                        'PreferredArrivalTime' => $preferredArrivalTime)],
                        "Sources" => NULL
                    );
            
            
            //echo "<pre>";print_r($datah);exit;
            $data_stringh = json_encode($datah);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, FLIGHT_API_SEARCH_URL);
            curl_setopt($ch,CURLOPT_ENCODING , "gzip");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Accept-Encoding: gzip',
                'Content-Length: ' . strlen($data_stringh)
            ));

            $outputH = curl_exec($ch);
            $response = json_decode($outputH, true); 
            //echo "<pre>";print_r($response);exit;
            $arrFlightSearchResponse['ResponseStatus'] = $response['Response']['ResponseStatus'];
            $arrFlightSearchResponse['TraceId'] = $response['Response']['TraceId'];
            $arrFlightSearchResponse['ErrorCode'] = $response['Response']['Error']['ErrorCode'];
            $arrFlightSearchResponse['ErrorMessage'] = $response['Response']['Error']['ErrorMessage'];
            $arrFlightSearchResponse['OutBoundFlightResults'] = isset($response['Response']['Results'][0])?$response['Response']['Results'][0]:array();
            $arrFlightSearchResponse['InBoundFlightResults'] = [];
            
            
            
        } else {
            $datah = array(
                    'EndUserIp' => $_SERVER['REMOTE_ADDR'],
                    'TokenId' => $tokenId,
                    "AdultCount" => $adultCount,
                    "ChildCount" => $childCount,
                    "InfantCount" => $infantCount,
                    "DirectFlight" => "false",
                    "OneStopFlight" => "false",
                    "JourneyType" => "2",
                    "ReturnDate" => $preferredReturnDepartureTime,
                    "PreferredAirlines" => NULL,
                    "Segments" => array(
                                        "0" => array(
                                                    'Origin' => $origin,
                                                    'Destination' => $destination,
                                                    'FlightCabinClass' => $preferredFlightClassType,
                                                    "PreferredDepartureTime" => $preferredDepartureTime,
                                                    'PreferredArrivalTime' => $preferredArrivalTime
                                                ),
                                         "1" => array(
                                                    'Origin' => $strReturnOrigin,
                                                    'Destination' => $strReturnDestination,
                                                    'FlightCabinClass' => $preferredFlightClassType,
                                                    'PreferredDepartureTime' => $preferredReturnDepartureTime,
                                                    'PreferredArrivalTime' => $preferredReturnArrivalTime
                                                 )     
                                       ) ,
                    "Sources" => NULL
                );
            
            
            $data_stringh = json_encode($datah);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, FLIGHT_API_SEARCH_URL);
            curl_setopt($ch,CURLOPT_ENCODING , "gzip");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Accept-Encoding: gzip',
                'Content-Length: ' . strlen($data_stringh)
            ));

            $outputH = curl_exec($ch);
            $response = json_decode($outputH, true); 
            
            //echo "<pre>";print_r($response);exit;
            if($interNationalSearch){
                $arrFlightSearchResponse['ResponseStatus'] = $response['Response']['ResponseStatus'];
                $arrFlightSearchResponse['TraceId'] = $response['Response']['TraceId'];
                $arrFlightSearchResponse['ErrorCode'] = $response['Response']['Error']['ErrorCode'];
                $arrFlightSearchResponse['ErrorMessage'] = $response['Response']['Error']['ErrorMessage'];
                $arrFlightSearchResponse['InterNationalFlightResults'] = isset($response['Response']['Results'][0])?$response['Response']['Results'][0]:array();
            }else{
                $arrFlightSearchResponse['ResponseStatus'] = $response['Response']['ResponseStatus'];
                $arrFlightSearchResponse['TraceId'] = $response['Response']['TraceId'];
                $arrFlightSearchResponse['ErrorCode'] = $response['Response']['Error']['ErrorCode'];
                $arrFlightSearchResponse['ErrorMessage'] = $response['Response']['Error']['ErrorMessage'];
                $arrFlightSearchResponse['OutBoundFlightResults'] = isset($response['Response']['Results'][0])?$response['Response']['Results'][0]:array();
                $arrFlightSearchResponse['InBoundFlightResults'] = isset($response['Response']['Results'][1])?$response['Response']['Results'][1]:array();
            }
            
            
            
            
        }
        
        
        
//        echo "<pre>";print_r($arrFlightSearchResponse);
//        echo "<pre>";print_r($response);exit;
        
        
        return $arrFlightSearchResponse;
        
        
    }
    
    
    public function fareQuoteDetails($data){
        
        $tokenId = $this->authenticateAPI();
        $datah = array(
            'EndUserIp' => $_SERVER['REMOTE_ADDR'],
            'TokenId' => $tokenId,
            'TraceId' => $data['apiTraceId'],
            'ResultIndex' => $data['ApiResultIndex']
        );
        $data_stringh = json_encode($datah);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, FLIGHT_API_FARE_QUOTE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_stringh)
        ));

        $outputH = curl_exec($ch);
        $response = json_decode($outputH, true);
        return $response;
    }
    
    public function fareRuleDetails($data){
        
        $tokenId = $this->authenticateAPI();
        $datah = array(
            'EndUserIp' => $_SERVER['REMOTE_ADDR'],
            'TokenId' => $tokenId,
            'TraceId' => $data['TraceId'],
            'ResultIndex' => $data['ResultIndex']
        );
        $data_stringh = json_encode($datah);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, FLIGHT_API_FARE_RULE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_stringh)
        ));

        $outputH = curl_exec($ch);
        $response = json_decode($outputH, true);
        return $response;
    }
    
    public function apiFlightBooking($data) {

        $TraceId = trim($data['TraceId']);
        $ResultIndex = trim($data['ResultIndex']);
        $strOrigin = $data['Origin'];
        $strDestination = $data['Destination'];
        $arrFairDetails = $data['arrFairDetails'];
        $arrTrevllerDetails = $data['arrTrevllerDetails'];
        $tokenId = $this->authenticateAPI();

        if (count($arrTrevllerDetails) > 0) {
            $ARR_SALUTION = unserialize(ARR_SALUTION);
//            $intK = 0;
//            $arrPassengers = [];
            for ($intI = 0; $intI < count($arrFairDetails); $intI++) {
                $intK = 0;
                $arrPassengers = [];
                $intPassengerCount = $arrFairDetails[$intI]['PassengerCount'];
                for ($intJ = 0; $intJ < $intPassengerCount; $intJ++) {
                    $intPassengerType = $arrFairDetails[$intI]['PassengerType'];
                    $paxTitle = $arrTrevllerDetails[$intK][0]['Title'];
                    if ($paxTitle <= 2) {
                        $intGender = $paxTitle;
                    } else {
                        $intGender = 2;
                    }


                    $paxTitle = trim($ARR_SALUTION[$paxTitle], ".");
                    $paxFirstName = trim($arrTrevllerDetails[$intK][0]['FirstName']);
                    $paxLastName = trim($arrTrevllerDetails[$intK][0]['LastName']);
                    $paxDOB = (array) $arrTrevllerDetails[$intK][0]['DOB'];
                    $arrDOB = explode(" ", $paxDOB['date']);
                    $strPaxDateOfBirth = $arrDOB[0] . "T00:00:00";
                    $paxAddress = trim($arrTrevllerDetails[$intK][0]['Address']);
                    $paxCityTitle = trim($arrTrevllerDetails[$intK][0]['CityTitle']);
                    $paxCountryTitle = trim($arrTrevllerDetails[$intK][0]['CountryTitle']);
                    $paxCountryCode = trim($arrTrevllerDetails[$intK][0]['CountryCode']);
                    $intMemberSysId = trim($arrTrevllerDetails[$intK][0]['MemberSysId']);
                    $paxContactNo = trim($arrTrevllerDetails[$intK][0]['Contacts']);
                    $paxEmailId = trim($arrTrevllerDetails[$intK][0]['EmailId']);

                    $intBaseFare = $arrFairDetails[$intI]['BaseFare'];
                    $intTax = $arrFairDetails[$intI]['Tax'];
                    $intYQTax = $arrFairDetails[$intI]['YQTax'];
                    $intAdditionalTxnFeeOfrd = $arrFairDetails[$intI]['AdditionalTxnFeeOfrd'];
                    $intAdditionalTxnFeePub = $arrFairDetails[$intI]['AdditionalTxnFeePub'];
                    $intAirTransFee = ".00";
                    $intTransactionFee = ".00";


                    $arrPassengers[] = [
                        'Title' => $paxTitle,
                        'FirstName' => $paxFirstName,
                        'LastName' => $paxLastName,
                        'PaxType' => $intPassengerType,
                        'DateOfBirth' => $strPaxDateOfBirth,
                        'Gender' => $intGender,
                        'PassportNo' => '',
                        'PassportExpiry' => '',
                        'AddressLine1' => $paxAddress,
                        'AddressLine2' => '',
                        'City' => $paxCityTitle,
                        'CountryCode' => $paxCountryCode,
                        'CountryName' => $paxCountryTitle,
                        'ContactNo' => $paxContactNo,
                        'Email' => $paxEmailId,
                        'IsLeadPax' => ($intMemberSysId == 0) ? 1 : 0,
                        'FFAirline' => '',
                        'FFNumber' => '',
                        'Fare' =>
                            [
                            'BaseFare' => $intBaseFare,
                            'Tax' => $intTax,
                            'TransactionFee' => $intTransactionFee,
                            'YQTax' => $intYQTax,
                            'AdditionalTxnFeeOfrd' => $intAdditionalTxnFeeOfrd,
                            'AdditionalTxnFeePub' => $intAdditionalTxnFeePub,
                            'AirTransFee' => $intAirTransFee
                        ],
                        'Meal' =>
                            [
                            'Code' => '',
                            'Description' => ''
                        ],
                        'Seat' =>
                            [
                            'Code' => '',
                            'Description' => ''
                        ]
                    ];

                    $intK++;
                }
            }
            //echo "<pre>";print_r($arrPassengers);exit;
            $data = array(
                'EndUserIp' => $_SERVER['REMOTE_ADDR'],
                'TokenId' => $tokenId,
                'TraceId' => $TraceId,
                'ResultIndex' => $ResultIndex,
                'Passengers' => $arrPassengers
            );

            $data_stringh = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, FLIGHT_API_BOOKING_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_stringh)
            ));

            $outputH = curl_exec($ch);
            $response = json_decode($outputH, true);
            //echo "<pre>";print_r($response);exit;
            return $response;
        } else {
            return $response = [];
        }
    }

    public function apiFlightTicket($data){
        
//        echo "<pre>";
//        print_r($data);
        //exit;
        
        $TraceId = trim($data['TraceId']);
        $ResultIndex = trim($data['ResultIndex']);
        $strOrigin = $data['Origin'];
        $strDestination = $data['Destination'];
        $arrFairDetails = $data['arrFairDetails'];
        $arrTrevllerDetails = $data['arrTrevllerDetails'];
        $tokenId = $this->authenticateAPI();
        
        // Passenger Details...
        
        
        if (count($arrTrevllerDetails) > 0) {
            $ARR_SALUTION = unserialize(ARR_SALUTION);
            //$intK = 0;
            //$arrPassengers = [];
            for ($intI = 0; $intI < count($arrFairDetails); $intI++) {
                $arrPassengers = [];
                $intK = 0;
                $intPassengerCount = $arrFairDetails[$intI]['PassengerCount'];
                for ($intJ = 0; $intJ < $intPassengerCount; $intJ++) {
                    $intPassengerType = $arrFairDetails[$intI]['PassengerType'];
                    $paxTitle = $arrTrevllerDetails[$intK][0]['Title'];
                    if ($paxTitle <= 2) {
                        $intGender = $paxTitle;
                    } else {
                        $intGender = 2;
                    }


                    $paxTitle = trim($ARR_SALUTION[$paxTitle], ".");
                    $paxFirstName = trim($arrTrevllerDetails[$intK][0]['FirstName']);
                    $paxLastName = trim($arrTrevllerDetails[$intK][0]['LastName']);
                    $paxDOB = (array) $arrTrevllerDetails[$intK][0]['DOB'];
                    $arrDOB = explode(" ", $paxDOB['date']);
                    $strPaxDateOfBirth = $arrDOB[0] . "T00:00:00";
                    $paxAddress = trim($arrTrevllerDetails[$intK][0]['Address']);
                    $paxCityTitle = trim($arrTrevllerDetails[$intK][0]['CityTitle']);
                    $paxCountryTitle = trim($arrTrevllerDetails[$intK][0]['CountryTitle']);
                    $paxCountryCode = trim($arrTrevllerDetails[$intK][0]['CountryCode']);
                    $intMemberSysId = trim($arrTrevllerDetails[$intK][0]['MemberSysId']);
                    $paxContactNo = trim($arrTrevllerDetails[$intK][0]['Contacts']);
                    $paxEmailId = trim($arrTrevllerDetails[$intK][0]['EmailId']);

                    $intBaseFare = $arrFairDetails[$intI]['BaseFare'];
                    $intTax = $arrFairDetails[$intI]['Tax'];
                    $intYQTax = $arrFairDetails[$intI]['YQTax'];
                    $intAdditionalTxnFeeOfrd = $arrFairDetails[$intI]['AdditionalTxnFeeOfrd'];
                    $intAdditionalTxnFeePub = $arrFairDetails[$intI]['AdditionalTxnFeePub'];
                    $intAirTransFee = ".00";
                    $intTransactionFee = ".00";


                    $arrPassengers[] = [
                        'Title' => $paxTitle,
                        'FirstName' => $paxFirstName,
                        'LastName' => $paxLastName,
                        'PaxType' => $intPassengerType,
                        'DateOfBirth' => $strPaxDateOfBirth,
                        'Gender' => $intGender,
                        'PassportNo' => '',
                        'PassportExpiry' => '',
                        'AddressLine1' => $paxAddress,
                        'AddressLine2' => '',
                        'City' => $paxCityTitle,
                        'CountryCode' => $paxCountryCode,
                        'CountryName' => $paxCountryTitle,
                        'ContactNo' => $paxContactNo,
                        'Email' => $paxEmailId,
                        'IsLeadPax' => ($intMemberSysId == 0) ? 1 : 0,
                        'FFAirline' => '',
                        'FFNumber' => '',
                        'Fare' =>
                            [
                            'BaseFare' => $intBaseFare,
                            'Tax' => $intTax,
                            'TransactionFee' => $intTransactionFee,
                            'YQTax' => $intYQTax,
                            'AdditionalTxnFeeOfrd' => $intAdditionalTxnFeeOfrd,
                            'AdditionalTxnFeePub' => $intAdditionalTxnFeePub,
                            'AirTransFee' => $intAirTransFee
                        ],
                        'MealDynamic' =>
                            [
                            'WayType' => '2',
                            'Code' => 'VGML',
                            'Description' => '2',
                            'AirlineDescription' => 'VEG MEAL',
                            'Quantity' => '1',
                            'Price' => '250',
                            'Currency' => 'INR',
                            'Origin' => $strOrigin,
                            'Destination' => $strDestination
                        ],
                        'Baggage' =>
                            [
                            'WayType' => '2',
                            'Code' => 'XBPA2',
                            'Description' => '',
                            'Weight' => '5',
                            'Currency' => 'INR',
                            'Price' => '1000',
                            'Origin' => $strOrigin,
                            'Destination' => $strDestination
                        ]
                    ];

                    $intK++;
                }
            }

            //echo "<pre>";print_r($arrPassengers);exit;
            $data = array(
                'PreferredCurrency' => 'INR',
                'IsBaseCurrencyRequired' => 'true',
                'EndUserIp' => $_SERVER['REMOTE_ADDR'],
                'TokenId' => $tokenId,
                'TraceId' => $TraceId,
                'ResultIndex' => $ResultIndex,
                'Passengers' => $arrPassengers

            );
        
        
            $data_stringh = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, FLIGHT_API_TICKET_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_stringh)
            ));


            $outputH = curl_exec($ch);
            $response = json_decode($outputH, true);
            //echo "<pre>";print_r($response);exit;
            return $response;
            
            
        }else{
            return $response = [];
        }
        exit;
    
    }
    
    
    
    public function cancelFlightBooking($data){
            $intIsLCC = $data['intIsLCC'];
            $intCancellationType = $data['CancellationType'];
            $TOKEN_ID  = $this->authenticateAPI();
            
            if($intIsLCC == 1){
                
                if($intCancellationType == 2){
                    $datah = array(
                    "BookingId" => $data['booking_id'],
                    "RequestType" => $intCancellationType,
                    "CancellationType" => 2,
                    "Sectors" =>array(
                                    "Origin" => $data['flightOrigin'],
                                    "Destination" => $data['flightDestination'],
                                ),
                    "TicketId" => array($data['ticket_id']),
                    "Remarks" => $data['remarks'],
                    "EndUserIp" => $_SERVER['REMOTE_ADDR'],
                    "TokenId" => $TOKEN_ID,
                    );
                }else{
                    $datah = array(
                        "BookingId" => $data['booking_id'],
                        "RequestType" => 1,
                        "CancellationType" => 2,
                        "Remarks" => $data['remarks'],
                        "EndUserIp" => $_SERVER['REMOTE_ADDR'],
                        "TokenId" => $TOKEN_ID,
                    );
                }
                
                
                
                $cURL = FLIGHT_API_BOOKING_CHANGE_REQUEST;
                
            }else{
                $datah = array(
                    "EndUserIp" => $_SERVER['REMOTE_ADDR'],
                    "TokenId" => $TOKEN_ID,
                    "BookingId" => $data['booking_id'],
                    "Source" => $data['source'],
                );
                
                $cURL = FLIGHT_API_BOOKING_RELEASE_PNR;
            }
            
            
             
            
            
            //echo "<pre>"; print_r($datah); exit;
            $data_stringh = json_encode($datah);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $cURL);
            curl_setopt($ch, CURLOPT_ENCODING , "gzip");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_stringh)
            ));
            $outputH = curl_exec($ch);
            $responseTBO = json_decode($outputH, true);
            //echo "<pre>"; print_r($responseTBO);exit;
            $intChangeRequestId = isset($responseTBO['Response']['TicketCRInfo'][0]['ChangeRequestId'])?$responseTBO['Response']['TicketCRInfo'][0]['ChangeRequestId']:'';
            
            if(!empty($intChangeRequestId)){
                
            
                $datah = array(
                    "ChangeRequestId" => $intChangeRequestId,
                    "EndUserIp" => $_SERVER['REMOTE_ADDR'],
                    "TokenId" => $TOKEN_ID
                );
                $data_stringh = json_encode($datah);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, FLIGHT_API_BOOKING_CHANGE_REQUEST_STATUS);
                curl_setopt($ch, CURLOPT_ENCODING , "gzip");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_stringh)
                ));
                $outputH = curl_exec($ch);
                $responseTBO = json_decode($outputH, true);
                return $responseTBO;
                //echo "<pre>"; print_r($responseTBO); exit; 
            }
    
            return $responseTBO;
        
    }
    
    
    public function dataEncode($arrData){
        return base64_encode(json_encode($arrData));
    }
    
    public function dataDecode($strData){
        return json_decode(base64_decode($strData),1);
    }
    
    
    public function getApiPriceWithMarkupAndServiceTax_bkp($intCommissionEarned,$intOfferedFare){
        echo "<pre>";print_r($arrAllCommissions);
        $arrPriceAndMarkUps = array();
        $objFlight = new Travel_Model_TblFlight();
        if($intCommissionEarned >0){
        // For GTX MarkUps...
        $arrGTXMarkups = $objFlight->getGTXMarkups();
        if($arrGTXMarkups[0]['MarkUp'] > 0 && count($arrGTXMarkups) > 0){
            $intGTXCurrencySysId = $arrGTXMarkups[0]['Currency'];
            $intGTXAccomType = $arrGTXMarkups[0]['AirType'];
            $intGTXMarkUpType = $arrGTXMarkups[0]['MarkUpType'];
            $intGTXMarkUp = $arrGTXMarkups[0]['MarkUp'];
            if($intCommissionEarned > $intGTXMarkUp){
            if($intGTXMarkUpType == 1){ // For Flat
               $intGTXMarkUp = $intGTXMarkUp;
               $intOfferedFareWithGTXCommision = $intOfferedFare + $intGTXMarkUp;
               $intCommisionLeftForAgency = $intCommissionEarned - $intGTXMarkUp;
                
            }else{ // For Percentage
                $intGTXMarkUp = ($intCommissionEarned*$intGTXMarkUp)/100; 
                $intOfferedFareWithGTXCommision = $intOfferedFare + $intGTXMarkUp;
                $intCommisionLeftForAgency = $intCommissionEarned - $intGTXMarkUp;
            }
            }else{
                $intGTXMarkUp = $intCommissionEarned;
                $intOfferedFareWithGTXCommision = $intOfferedFare + $intGTXMarkUp;
                $intCommisionLeftForAgency = 0;
            }
            
        }else{
               $intGTXMarkUp = 0;
               $intOfferedFareWithGTXCommision = $intOfferedFare;
               $intCommisionLeftForAgency = $intCommissionEarned;
        }
        
        
        
        
        // For Agency MarkUps...
        $arrAgencyMarkups = $objFlight->getAgencyMarkups();
        //echo "<pre>";print_r($arrAgencyMarkups);
        if($arrAgencyMarkups[0]['StdMarkUpPer'] > 0 && count($arrAgencyMarkups) > 0){
            $intAgencyCurrencySysId = $arrAgencyMarkups[0]['Currency'];
            $intAgencyAccomType = $arrAgencyMarkups[0]['AirType'];
            $intAgencyMarkUpType = $arrAgencyMarkups[0]['MarkUpType'];
            $intAgencyMarkUp = $arrAgencyMarkups[0]['StdMarkUpPer'];
            $percentAgencySTax = $arrAgencyMarkups[0]['TaxPer'];
            if($intCommisionLeftForAgency > $intAgencyMarkUp){
                if($intAgencyMarkUpType == 1){ // For Flat
                    //$intAgencyMarkUp = $intCommisionLeftForAgency - $intAgencyMarkUp;
                    $intAgencyMarkUp = $intAgencyMarkUp;
                    $intTDSOnAgencyMarkUp = ($intCommisionLeftForAgency*20)/100; 
                }else{ // For Percentage
                    $intAgencyMarkUp = ($intCommisionLeftForAgency*$intAgencyMarkUp)/100; 
                    //$intAgencyMarkUp = $intCommisionLeftForAgency - $intAgencyMarkUp;

                    $intTDSOnAgencyMarkUp = ($intCommisionLeftForAgency*20)/100; 

                }
            }else{
                $intAgencyMarkUp = $intCommisionLeftForAgency;
                $intTDSOnAgencyMarkUp = ($intCommisionLeftForAgency*20)/100;
            }
            
            
        
        }else{
            $intAgencyMarkUp = 0;
            $intTDSOnAgencyMarkUp = 0;
        } 
        
        
        $arrPriceAndMarkUps = array(
                    "intOfferedFare" => $intOfferedFare,
                    "intOfferedFareWithGTXCommision" => $intOfferedFareWithGTXCommision,
                    "intCommissionEarned" => $intCommissionEarned,
                    "intCommisionLeftForAgency" => $intCommisionLeftForAgency,
                    "intGTXMarkUp" => $intGTXMarkUp,
                    "intSTaxOnGTXMarkUp" => 0,
                    "intGTXMarkUpWithSTax" => 0,
                    "intAgencyMarkUp" => $intAgencyMarkUp,
                    "intTDSOnAgencyMarkUp" => $intTDSOnAgencyMarkUp,
                    "intAgencyMarkUpWithSTax" => 0,
                    "intNetSTax" => 0
                );
        
        }else{
            $arrPriceAndMarkUps = array(
                    "intOfferedFare" => $intOfferedFare,
                    "intOfferedFareWithGTXCommision" => $intOfferedFare,
                    "intCommissionEarned" => $intCommissionEarned,
                    "intCommisionLeftForAgency" => 0,
                    "intGTXMarkUp" => 0,
                    "intSTaxOnGTXMarkUp" => 0,
                    "intGTXMarkUpWithSTax" => 0,
                    "intAgencyMarkUp" => 0,
                    "intTDSOnAgencyMarkUp" => 0,
                    "intAgencyMarkUpWithSTax" => 0,
                    "intNetSTax" => 0
                );
        }
        
        
        
        
        $sessionFlightPriceAndMarkupsDetails = new Zend_Session_Namespace('sessionFlightPriceAndMarkupsDetails');
        $sessionFlightPriceAndMarkupsDetails->params = $arrPriceAndMarkUps;
        
        return $arrPriceAndMarkUps;   
        
    }
    
    
    function getApiServiceTax($intAmount=NULL,$strType,$intSource){ // $intSource 8 For TBO , 9 For GRN
        $arrSerciceTax = array();
        if($strType == "F"){
            $objHotel = new Travel_Model_TblBuyHotel();
            $arrApiServiceTax = $objHotel->getApiServiceTax($intSource);
            if(count($arrApiServiceTax) > 0){
                $percentAgencySTax = $arrApiServiceTax[0]['Percentage'];
                $intNetSTax = ($intAmount*$percentAgencySTax)/100;
                $BasePriceWithSTax = $intNetSTax + $intAmount;

                $arrSerciceTax = array(
                    "BasePrice" => $intAmount,
                    "serviceTaxAmount" => $intNetSTax,
                    "BasePriceWithSTax" => $BasePriceWithSTax,
                    "ServiceTaxPercentage" => $percentAgencySTax,
                    "Type" => $strType
                    );
            }
        }
        //echo "<pre>";print_r($arrSerciceTax);echo "</pre>";exit;
        return $arrSerciceTax;
    }
    
    
    public function getApiPriceWithMarkupAndServiceTax($arrAllCommissions = array(),$intOfferedFare,$strCountryCode = NULL,$AgencySysId = NULL){
        
        $intCommissionEarned = $arrAllCommissions['intCommissionEarned'];
        $intPLBEarned = $arrAllCommissions['intPLBEarned'];
        $intIncentiveEarned = $arrAllCommissions['intIncentiveEarned'];
        
        if(!empty(trim($strCountryCode)) && trim($strCountryCode) != "IN"){
            $intAirType = 2;
        }else{
            $intAirType = 1; 
        }
        //echo $intAirType;
        $arrPriceAndMarkUps = array();
        $objFlight = new Travel_Model_TblFlight();
        
        $intGTXMarkUp = 0; 
        $intOfferedFareWithGTXCommision = 0;
        
        // For GTX MarkUps...
        $arrGTXMarkups = $objFlight->getGTXMarkups($intAirType,$AgencySysId);
        if($arrGTXMarkups[0]['MarkUp'] > 0 && count($arrGTXMarkups) > 0){
            $intGTXCurrencySysId = $arrGTXMarkups[0]['Currency'];
            $intGTXAccomType = $arrGTXMarkups[0]['AirType'];
            $intGTXMarkUpType = $arrGTXMarkups[0]['MarkUpType'];
            $intGTXMarkUp = $arrGTXMarkups[0]['MarkUp'];
            if($intGTXMarkUpType == 1){ // For Flat
               $intGTXMarkUp = $intGTXMarkUp;
               $intOfferedFareWithGTXCommision = $intOfferedFare + $intGTXMarkUp;
                
            }else{ // For Percentage
                $intGTXMarkUp = ($intOfferedFare*$intGTXMarkUp)/100; 
                $intOfferedFareWithGTXCommision = $intOfferedFare + $intGTXMarkUp;
            }
        }else{
            $intGTXMarkUp = 0; 
            $intOfferedFareWithGTXCommision = $intOfferedFare + $intGTXMarkUp;
        }
        
        
        
        
        // For Agency MarkUps...
        $arrAgencyMarkups = $objFlight->getAgencyMarkups($intAirType,$AgencySysId);
        //echo "<pre>";print_r($arrAgencyMarkups); exit;
        if(count($arrAgencyMarkups) > 0){ // For Agency Mark UP....
            $intAgencyCurrencySysId = $arrAgencyMarkups[0]['Currency'];
            $intAgencyAccomType = $arrAgencyMarkups[0]['AirType'];
            $intAgencyMarkUpType = $arrAgencyMarkups[0]['MarkUpType'];
            $intAgencyMarkUp = $arrAgencyMarkups[0]['StdMarkUpPer']; // Agency Fix Mark UP...
            $percentAgencySTax = $arrAgencyMarkups[0]['TaxPer'];
            
            $intCommssionType = $arrAgencyMarkups[0]['CommssionType']; // 2 For percentage
            $intCommssionVal = $arrAgencyMarkups[0]['CommssionVal']; // Percntage Value For Agency Commision From Actual Commision Retuned From API...
            
            
            $intFareWithAgencyFixMarkUp = $intOfferedFareWithGTXCommision + $intAgencyMarkUp;
            
            
            if($intCommssionType == 2){ // For Agency Commision In Percentage Only...
                $intAgencyCommisionEarnedFromAcutalCommision    = ($intCommissionEarned*$intCommssionVal)/100;
                $intAgencyPLBEarnedFromAcutalPLB                = ($intPLBEarned*$intCommssionVal)/100;
                $intAgencyIncentiveEarnedFromAcutalIncentive    = ($intIncentiveEarned*$intCommssionVal)/100;
            }
        }else{
            $intFareWithAgencyFixMarkUp = $intOfferedFareWithGTXCommision;
            $intAgencyCommisionEarnedFromAcutalCommision = 0;
            $intAgencyPLBEarnedFromAcutalPLB = 0;
            $intAgencyIncentiveEarnedFromAcutalIncentive = 0;
        } 
        
        $intTotalEarningsForAgency = $intAgencyCommisionEarnedFromAcutalCommision + $intAgencyPLBEarnedFromAcutalPLB + $intAgencyIncentiveEarnedFromAcutalIncentive;
        
        /* Service Tax Calculation */
        $arrSTOnGTXMarkUp = $this->getApiServiceTax($intGTXMarkUp,"F",0);
        $intSTOnGTXMarkUp = $arrSTOnGTXMarkUp['serviceTaxAmount'];
        
        $arrSTOnAgencyFixMarkUp = $this->getApiServiceTax($intAgencyMarkUp,"F",0);
        $intSTOnAgencyFixMarkUp = $arrSTOnAgencyFixMarkUp['serviceTaxAmount'];
        
        /* Service Tax Calculation */
        
        $arrPriceAndMarkUps = array(
                    "intOfferedFare" => $intOfferedFare,
                    "intFareWithGTXMarkUp" => $intOfferedFareWithGTXCommision,
                    "intFareWithAgencyFixMarkUp" => $intFareWithAgencyFixMarkUp,
                    "intPublishFare" => ($intFareWithAgencyFixMarkUp + $intTotalEarningsForAgency + $intSTOnGTXMarkUp + $intSTOnAgencyFixMarkUp),
                    "intCommissionEarned" => $intCommissionEarned,
                    "intCommisionEarnedForAgency" => $intAgencyCommisionEarnedFromAcutalCommision,
                    "intPLBEarned" => $intPLBEarned,
                    "intPLBEarnedForAgency" => $intAgencyPLBEarnedFromAcutalPLB,
                    "intIncentiveEarned" => $intIncentiveEarned,
                    "intIncentiveEarnedForAgency" => $intAgencyIncentiveEarnedFromAcutalIncentive,
                    "intTotalEarningsForAgency" => $intTotalEarningsForAgency,
                    "intGTXMarkUp" => $intGTXMarkUp,
                    "intAgencyFixMarkUp" => $intAgencyMarkUp,
                    "intSTaxOnGTXMarkUp" => $intSTOnGTXMarkUp,
                    "intSTaxOnAgencyFixMarkUp" => $intSTOnAgencyFixMarkUp
            
                );
        
        
        //echo "<pre>";print_r($arrPriceAndMarkUps);echo "</pre>";//exit;
        
        
        
        $sessionFlightPriceAndMarkupsDetails = new Zend_Session_Namespace('sessionFlightPriceAndMarkupsDetails');
        $sessionFlightPriceAndMarkupsDetails->params = $arrPriceAndMarkUps;
        
        return $arrPriceAndMarkUps;   
        
    }
    
    
    
    public function getAllStatusType($type=NULL,$statusTypeId=NULL){
        
        
            $objTravPlanStatus = new Travel_Model_CRM_TravelPlanStatus();
            $result = $objTravPlanStatus->GetTravelPlanStatusByTypeName('2',$statusTypeId);
            //echo "<pre>";print_r($result);exit;
            $statusTypeArray = array();
            
            foreach ($result as $res){
                $statusTypeArray[$res['TPStatusSysId']] = $res['TPStatus'];
                
            }
            
		if(isset($statusTypeId) & !empty($statusTypeId)){
			$response=$statusTypeArray[$statusTypeId];
		}else{
			$response=$statusTypeArray;
		}
	  return $response;
   }
    
   
   
   
   
   public function searchApiProposalFlights($arrData = array()){
        
        
        $JourneyType = trim($arrData['JourneyType']);
        $IsInterNational = trim($arrData['IsInterNational']);
        $adultCount = trim($arrData['AdultCount']);
        $childCount = trim($arrData['ChildCount']);
        $infantCount = trim($arrData['InfantCount']);
        
        $origin = trim($arrData['Origin']);
        $destination = trim($arrData['Destination']);
        $preferredDepartureTime = trim($arrData['PreferredDepartureTime']);
        $preferredArrivalTime = trim($arrData['PreferredDepartureTime']);
        
        $preferredDepartureTime = $preferredDepartureTime."T00:00:00";
        $preferredArrivalTime = $preferredArrivalTime."T00:00:00";
        
        
        $PreferredReturnDate = $arrData['PreferredReturnDate'];
        
        $preferredReturnDepartureTime = $PreferredReturnDate."T00:00:00";
        $preferredReturnArrivalTime = $PreferredReturnDate."T00:00:00";
        
        $tokenId   = $this->authenticateAPI();
        
        $arrFlightSearchResponse = array();
        if($JourneyType == 1 && !$IsInterNational){
            $datah = array(
                        'EndUserIp' => $_SERVER['REMOTE_ADDR'],
                        'TokenId' => $tokenId,
                        "AdultCount" => $adultCount,
                        "ChildCount" => $childCount,
                        "InfantCount" => $infantCount,
                        "DirectFlight" => "false",
                        "OneStopFlight" => "false",
                        "JourneyType" => "1",
                        "PreferredAirlines" => [trim($arrData['PreferredAirlines'])],
                        "Segments" =>   [
                                        array(
                                            'Origin' => $origin, 
                                            'Destination' => $destination, 
                                            'FlightCabinClass' => 1, 
                                            "PreferredDepartureTime" => $preferredDepartureTime,
                                            'PreferredArrivalTime' => $preferredArrivalTime
                                            )
                                        ],
                        "Sources" => [trim($arrData['PreferredAirlines'])]
                    );
            
        }else{
            
            $datah = array(
                    'EndUserIp' => $_SERVER['REMOTE_ADDR'],
                    'TokenId' => $tokenId,
                    "AdultCount" => $adultCount,
                    "ChildCount" => $childCount,
                    "InfantCount" => $infantCount,
                    "DirectFlight" => "false",
                    "OneStopFlight" => "false",
                    "JourneyType" => "2",
                    "ReturnDate" => $PreferredReturnDate,
                    "PreferredAirlines" => isset($arrData['PreferredAirlinesReturn'])?[trim($arrData['PreferredAirlines']),trim($arrData['PreferredAirlinesReturn'])]:[trim($arrData['PreferredAirlines'])],
                    "Segments" => array(
                                        "0" => array(
                                                    'Origin' => $origin,
                                                    'Destination' => $destination,
                                                    'FlightCabinClass' => 1,
                                                    "PreferredDepartureTime" => $preferredDepartureTime,
                                                    'PreferredArrivalTime' => $preferredArrivalTime
                                                ),
                                         "1" => array(
                                                    'Origin' => $destination,
                                                    'Destination' => $origin,
                                                    'FlightCabinClass' => 1,
                                                    'PreferredDepartureTime' => $preferredReturnDepartureTime,
                                                    'PreferredArrivalTime' => $preferredReturnArrivalTime
                                                 )     
                                       ) ,
                    "Sources" => isset($arrData['PreferredAirlinesReturn'])?[trim($arrData['PreferredAirlines']),trim($arrData['PreferredAirlinesReturn'])]:[trim($arrData['PreferredAirlines'])]
                );
        }    
            
            
            
            
            
            
            
            
            //echo "<pre>";print_r($datah);exit;
            $data_stringh = json_encode($datah);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, FLIGHT_API_SEARCH_URL);
            curl_setopt($ch,CURLOPT_ENCODING , "gzip");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stringh);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Accept-Encoding: gzip',
                'Content-Length: ' . strlen($data_stringh)
            ));

            $outputH = curl_exec($ch);
            $response = json_decode($outputH, true); 
            //echo "<pre>";print_r($response);exit;
            $arrFlightSearchResponse = [];
            $arrFlightSearchResponse['ResponseStatus'] = $response['Response']['ResponseStatus'];
            $arrFlightSearchResponse['TraceId'] = $response['Response']['TraceId'];
            $arrFlightSearchResponse['ErrorCode'] = $response['Response']['Error']['ErrorCode'];
            $arrFlightSearchResponse['ErrorMessage'] = $response['Response']['Error']['ErrorMessage'];
            $arrFlightSearchResponse['FlightResults'] = isset($response['Response']['Results'][0])?$response['Response']['Results'][0]:array();
            $arrFlightSearchResponse['FlightResultsInBound'] = isset($response['Response']['Results'][1])?$response['Response']['Results'][1]:array();
            return $arrFlightSearchResponse;
            
            
        
    }
   
   
   
    
    /*  Added  by Pardeep Panchal */
   
    
}
