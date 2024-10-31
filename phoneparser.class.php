<?php

class PhoneParser {
  var $original_phone;
  var $country_code;
  var $area;
  var $number;
  var $coutry_codes = array(1,7,20,27,30,31,32,33,34,36,39,40,41,43,44,45,46,47,
			    48,49,51,52,53,54,55,56,57,58,60,61,62,63,64,65,66,
			    81,82,84,86,90,91,92,93,94,95,98,212,213,216,218,
			    220,221,222,223,224,225,226,227,228,229,230,231,232,
			    233,234,235,236,237,238,239,240,241,242,243,244,245,
			    246,247,248,249,250,251,252,253,254,255,256,257,258,
			    260,261,262,263,264,265,266,267,268,269,290,291,297,
			    298,299,350,351,352,353,354,355,356,357,358,359,370,
			    371,372,373,374,375,376,377,378,380,381,385,386,387,
			    388,389,420,421,423,500,501,502,503,504,505,506,507,
			    508,509,590,591,592,593,594,595,596,597,598,599,670,
			    672,673,674,675,676,677,678,679,680,681,682,683,684,
			    685,686,687,688,689,690,691,692,800,808,850,852,853,
			    655,856,870,871,872,873,874,878,880,881,882,886,960,
			    961,962,963,964,965,966,967,968,970,971,972,973,974,
			    975,976,977,979,991,992,993,994,995,996,998);
			    
  var $error = "";
  var $min_phone_length = 8;
  var $max_phone_length = 13;
  
  function PhoneParser($number = ''){
    if (!empty($number)) {
      $this->original_phone = $number;
    }
  }
  
  function parse(){
    $this->error = "";
    $this->county_code = "";
    $this->number = "";
    if (empty($this->original_phone)) {
      $this->error = 'No phone number is set';
      return false;
    }
    $phone = $this->normalize_mobile($this->original_phone);
    if ((strlen($phone) < $this->min_phone_length) || (strlen($phone) > $this->max_phone_length)) {
      $this->error = "Incorrect phone number length";
      return false;
    }
    // check first digit
    if (!preg_match('~([\d]?)(.*)~', $phone,$matches)){
      $this->error = "Error parsing phone number";
      return false;
    }
    $cc = $matches[1];
    if(in_array($cc, $this->coutry_codes)) {
      $this->country_code = $cc;
      $this->number = $matches[2];
      return true;
    }
    // check first two digits
    if (!preg_match('~([\d]{2})(.*)~', $phone,$matches)){
      $this->error = "Error parsing phone number";
      return false;
    }
    $cc = $matches[1];
    if(in_array($cc, $this->coutry_codes)) {
      $this->country_code = $cc;
      $this->number = $matches[2];
      return true;
    }
    // check first three digits
    if (!preg_match('~([\d]{3})(.*)~', $phone,$matches)){
      $this->error = "Error parsing phone number";
      return false;
    }
    $cc = $matches[1];
    if(in_array($cc, $this->coutry_codes)) {
      $this->country_code = $cc;
      $this->number = $matches[2];
      return true;
    }
    $this->error = "Country code not found";
    return false;
  }
  
  function normalize_mobile( $number )
  {
    $number  = str_replace( '+', '', $number );
    $number  = str_replace( ' ', '', $number );
    $number  = str_replace( '(', '', $number );
    $number  = str_replace( ')', '', $number );
    $number  = str_replace( '-', '', $number );
    
    return $number;
  }
}
?>