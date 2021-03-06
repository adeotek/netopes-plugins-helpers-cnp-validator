<?php
/**
 * CNPValidator class file
 * Validate Romanian CNP
 *
 * @package    NETopes\Plugins\Helpers
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    1.0.5
 * @filesource
 */
namespace NETopes\Plugins\Helpers;
/**
 * CNPValidator class
 * Validate Romanian CNP
 *
 * @package  NETopes\Plugins\Helpers
 */
class CNPValidator {
    /**
     * @var    integer Gender
     */
    protected $gender=NULL;
    /**
     * @var    integer Year
     */
    protected $year=NULL;
    /**
     * @var    integer Month
     */
    protected $month=NULL;
    /**
     * @var    integer Day
     */
    protected $day=NULL;
    /**
     * @var    integer Region
     */
    protected $region=NULL;
    /**
     * @var    integer Index
     */
    protected $index=NULL;
    /**
     * @var    integer Control digit
     */
    protected $control=NULL;
    /**
     * @var    integer Control sum
     */
    protected $checksum=NULL;
    /**
     * @var string
     */
    protected $input;
    /**
     * @var    mixed Errors
     */
    protected $errors=NULL;
    /**
     * @var array
     */
    protected $regions=[
        1=>"Alba",
        2=>"Arad",
        3=>"Arges",
        4=>"Bacau",
        5=>"Bihor",
        6=>"Bistrita-Nasaud",
        7=>"Botosani",
        8=>"Brasov",
        9=>"Braila",
        10=>"Buzau",
        11=>"Caras-Severin",
        12=>"Cluj",
        13=>"Constanta",
        14=>"Covasna",
        15=>"Dambovita",
        16=>"Dolj",
        17=>"Galati",
        18=>"Gorj",
        19=>"Harghita",
        20=>"Hunedoara",
        21=>"Ialomita",
        22=>"Iasi",
        23=>"Ilfov",
        24=>"Maramures",
        25=>"Mehedinti",
        26=>"Mures",
        27=>"Neamt",
        28=>"Olt",
        29=>"Prahova",
        30=>"Satu Mare",
        31=>"Salaj",
        32=>"Sibiu",
        33=>"Suceava",
        34=>"Teleorman",
        35=>"Timis",
        36=>"Tulcea",
        37=>"Vaslui",
        38=>"Valcea",
        39=>"Vrancea",
        41=>"Bucuresti/Sectorul 1",
        42=>"Bucuresti/Sectorul 2",
        43=>"Bucuresti/Sectorul 3",
        44=>"Bucuresti/Sectorul 4",
        45=>"Bucuresti/Sectorul 5",
        46=>"Bucuresti/Sectorul 6",
        51=>"Calarasi",
        52=>"Giurgiu",
    ];

    /**
     * Validate input CNP
     *
     * @param       $input
     * @param array $errors
     * @param bool  $checksum
     * @return bool Returns validation status
     */
    public static function Validate($input,array &$errors=[],bool $checksum=FALSE): bool {
        $instance=new CNPValidator($input,$checksum);
        $errors=$instance->GetErrors();
        return $instance->IsValid();
    }//END public static function Validate

    /**
     * Class constructor
     *
     * @param      $input
     * @param bool $checksum
     */
    public function __construct($input,bool $checksum=TRUE) {
        $this->input=(string)$input;
        $this->_validate($checksum);
    }//END public function __construct

    /**
     * Validate input
     *
     * @param bool $checksum
     * @return void
     */
    protected function _validate(bool $checksum=TRUE) {
        $errors=[];
        if(!isset($this->input) || !strlen(trim($this->input))) {
            $errors[]='empty_input';
        } else {
            if(strlen(trim($this->input))!==13) {
                $errors[]='invalid_characters_no';
            }
            if(!is_numeric($this->input)) {
                $errors[]='contains_invalid_characters';
            }
        }//if(!isset($this->input) && !strlen(trim($this->input)))
        if(count($errors)) {
            $this->errors=$errors;
            return;
        }//if(count($errors))
        // prima cifra din CNP reprezinta sexul si nu poate fi decat 1,2,5,6 (pentru cetatenii romani)
        // 1, 2 pentru cei nascuti intre anii 1900 si 1999
        // 5, 6 pentru cei nsacuti dupa anul 2000
        $this->gender=substr($this->input,0,1);
        if(!in_array($this->gender,[1,2,3,4,5,6,7,8])) {
            $errors[]='invalid_gender';
        }
        $this->year=($this->gender<3 ? 1900 : ($this->gender<5 ? 1800 : 2000)) + (int)substr($this->input,1,2);
        $this->month=substr($this->input,3,2);
        if($this->month==0 || $this->month>12) {
            $errors[]='invalid_month';
        }
        $this->day=substr($this->input,5,2);
        if($this->day==0 || $this->day>31) {
            $errors[]='invalid_day';
        }
        $this->region=substr($this->input,7,2);
        if($this->region==0 || $this->region>52) {
            $errors[]='invalid_region';
        }
        // cifrele 10,11,12 reprezinta un nr. poate fi intre 001 si 999.
        // Numerele din acest interval se impart pe judete,
        // birourilor de evidenta a populatiei, astfel inct un anumit numar din acel
        // interval sa fie alocat unei singure persoane intr-o anumita zi.
        $this->index=substr($this->input,9,3);
        if($this->index==0) {
            $errors[]='invalid_index';
        }
        if($checksum) {
            // cifra 13 reprezinta cifra de control aflata in relatie cu
            // toate celelate 12 cifre ale CNP-ului.
            // fiecare cifra din CNP este inmultita cu cifra de pe aceeasi pozitie
            // din numarul 279146358279; rezultatele sunt insumate,
            // iar rezultatul final este impartit cu rest la 11. Daca restul este 10,
            // atunci cifra de control este 1, altfel cifra de control este egala cu restul.
            $this->control=substr($this->input,12,1);
            $this->checksum=$this->input[0] * 2
                + $this->input[1] * 7
                + $this->input[2] * 9
                + $this->input[3] * 1
                + $this->input[4] * 4
                + $this->input[5] * 6
                + $this->input[6] * 3
                + $this->input[7] * 5
                + $this->input[8] * 8
                + $this->input[9] * 2
                + $this->input[10] * 7
                + $this->input[11] * 9;
            $computedControl=($this->checksum % 11)==10 ? 1 : ($this->checksum % 11);
            if($this->control!=$computedControl) {
                $errors[]='invalid_checksum';
            }
        }//if($checksum)
        $this->errors=(count($errors) ? $errors : FALSE);
    }//END protected function _validate

    /**
     * Get validation result
     *
     * @return bool Returns validation result
     */
    public function IsValid(): bool {
        return ($this->errors===FALSE);
    }//END public function IsValid

    /**
     * Get errors
     *
     * @return array Returns errors array
     */
    public function GetErrors(): array {
        if(!is_array($this->errors)) {
            return [];
        }
        return $this->errors;
    }//END public function GetErrors

    /**
     * Get gender
     *
     * @param bool $original
     * @return mixed Returns gender (1=male; 2=female)
     */
    public function GetGender(bool $original=FALSE) {
        if($this->errors!==FALSE) {
            return NULL;
        }
        if($original) {
            return $this->gender;
        }
        return (($this->gender % 2)==1 ? 1 : 2);
    }//END public function GetGender

    /**
     * Get birthday
     *
     * @return string|null Returns birthday in universal format (yyyy-mm-dd)
     */
    public function GetBirthday(): ?string {
        if($this->errors!==FALSE) {
            return NULL;
        }
        return ($this->year.'-'.$this->month.'-'.$this->day);
    }//END public function GetBirthday

    /**
     * Get birthday year
     *
     * @return int|null Returns birthday year
     */
    public function GetBirthdayYear(): ?int {
        if($this->errors!==FALSE) {
            return NULL;
        }
        return $this->year;
    }//END public function GetBirthdayYear

    /**
     * Get birthday month
     *
     * @return int|null Returns birthday month
     */
    public function GetBirthdayMonth(): ?int {
        if($this->errors!==FALSE) {
            return NULL;
        }
        return $this->month;
    }//END public function GetBirthdayMonth

    /**
     * Get birthday day
     *
     * @return int|null Returns birthday day
     */
    public function GetBirthdayDay(): ?int {
        if($this->errors!==FALSE) {
            return NULL;
        }
        return $this->day;
    }//END public function GetBirthdayDay

    /**
     * Get region
     *
     * @param bool $code
     * @return mixed Returns region
     */
    public function GetRegion(bool $code=FALSE) {
        if($this->errors!==FALSE) {
            return NULL;
        }
        return ($code ? $this->region : $this->regions[$this->region]);
    }//END public function GetRegion

    /**
     * Person has residence in Romania
     *
     * @return bool Returns residence state
     */
    public function IsResident(): bool {
        return !in_array($this->gender,[7,8]);
    }//END public function IsResident

    /**
     * Person is not of Romanian nationality
     *
     * @return bool Returns foreigner state
     */
    public function IsForeigner(): bool {
        return in_array($this->gender,[7,8,9]);
    }//END public function IsForeigner
}//END class CNPValidator