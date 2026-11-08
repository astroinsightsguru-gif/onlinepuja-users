<?php

namespace App\Models\AstrologerModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class WithdrawRequest extends Model
{
    use HasFactory;
    protected $table = 'withdrawrequest';
    public $timestamps = false;
    protected $fillable = [
        'astrologerId',
        'withdrawAmount',
        'createdBy',
        'modifiedBy',
        'status',
        'inr_usd_conversion_rate'
    ];

  public $appends=['withdrawAmount'];


    public function getWithdrawAmountAttribute($val)
    {
        /*
        $usdtoinrvalue=convertusdtoinr($this->attributes['withdrawAmount'],$this->inr_usd_conversion_rate?:1);
        $country = '';
        if(authcheck() || Auth::guard('api')->user()){
            $authCountry = isset(authcheck()['country'])?authcheck()['country']:Auth::guard('api')->user()->country;
            $country = strtolower($authCountry) == 'india' ? 'india' : 'international';
        }
        elseif(astroauthcheck()){
            $astroCountry = isset(astroauthcheck()['country'])?astroauthcheck()['country']:Auth::guard('api')->user()->country;
            $country = strtolower($astroCountry) == 'india' ? 'india' : 'international';
        }elseif(Auth::guard('web')->user()){
            $country = strtolower('India') == 'india' ? 'india' : 'international';
        }
        return ($country=='india'?$usdtoinrvalue:$this->attributes['withdrawAmount']);
        */
        
        $usdtoinrvalue=convertinrtousd($this->attributes['withdrawAmount'],$this->inr_usd_conversion_rate?:1);
        $country = '';
        if(authcheck() || Auth::guard('api')->user()){
            $authCountryCode = isset(authcheck()['countryCode'])?authcheck()['countryCode']:Auth::guard('api')->user()->countryCode;
            $country = strtolower($authCountryCode) == '+91' ? 'india' : 'international';
        }
        elseif(astroauthcheck()){
            $astroCountryCode = isset(astroauthcheck()['countryCode'])?astroauthcheck()['countryCode']:Auth::guard('api')->user()->countryCode;
            $country = strtolower($astroCountryCode) == '+91' ? 'india' : 'international';
        }elseif(Auth::guard('web')->user()){
            $country = strtolower('India') == 'india' ? 'india' : 'international';
        }
        return ($country!='india' ? $usdtoinrvalue : $this->attributes['withdrawAmount']);
    }

}

class_alias(WithdrawRequest::class, 'App\Models\AstrologerModel\WithDrawRequest');
