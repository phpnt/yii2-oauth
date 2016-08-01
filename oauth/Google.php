<?php
/**
 * Created by PhpStorm.
 * User: phpNT - http://phpnt.com
 * Date: 04.07.2016
 * Time: 11:32
 */

namespace phpnt\oAuth\oauth;

use phpnt\oAuth\models\UserOauthKey;

/**
 * Авторизация через Google plus
 * Class Google
 */
class Google extends \yii\authclient\clients\GoogleOAuth
{
    public $email       = 'email';
    public $first_name  = 'first_name';
    public $last_name   = 'last_name';
    public $avatar      = 'avatar';

    public $gender      = 'gender';
    public $female      = 1;
    public $male        = 2;

    public $status          = 'status';
    public $statusActive    = 1;
    /**
     * Размеры Popap-окна
     * @return array
     */
    public function getViewOptions()
    {
        return [
            'popupWidth' => 900,
            'popupHeight' => 500
        ];
    }
    
    /**
     * Преобразование пола
     * @return array
     */
    public function normalizeSex()
    {
        return [
            'male' => $this->male,
            'female' => $this->female
        ];
    }

    /**
     * Инициализация
     */
    public function init()
    {
        parent::init();
        if ($this->scope === null) {
            $this->scope = implode(' ', [
                'profile',
                'email',
            ]);
        }
    }

    /**
     * Получение аттрибутов
     * @return array
     * @throws \yii\base\Exception
     */
    protected function initUserAttributes()
    {
        $attributes = $this->api('people/me', 'GET');

        $return_attributes = [
            'User' => [
                $this->email        => $attributes['emails'][0]['value'],
                $this->first_name   => $attributes['name']['givenName'],
                $this->last_name    => $attributes['name']['familyName'],
                $this->avatar       => str_replace('sz=50', 'sz=200', $attributes['image']['url']),
                $this->gender       => $this->normalizeSex()[$attributes['gender']],
                $this->status       => $this->statusActive
            ],
            'provider_user_id' => $attributes['id'],
            'provider_id' => UserOauthKey::getAvailableClients()['google'],
            'page' => $attributes['id'],
        ];
        return $return_attributes;
    }
}
