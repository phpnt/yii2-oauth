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
 * Авторизация с помощью Twitter
 * Class Twitter
 */
class Twitter extends \yii\authclient\clients\Twitter
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
     * Получение аттрибутов
     * @return array
     * @throws \yii\base\Exception
     */
    protected function initUserAttributes()
    {
        $attributes = $this->api('account/verify_credentials.json', 'GET');

        $return_attributes = [
            'User' => [
                $this->email        => '',
                $this->first_name   => $attributes['name'],
                $this->avatar       => '',
                $this->gender       => $this->male,
                $this->status       => $this->statusActive
            ],
            'provider_user_id' => $attributes['id'],
            'provider_id' => UserOauthKey::getAvailableClients()['twitter'],
            'page' => $attributes['screen_name'],
        ];

        return $return_attributes;
    }
}
