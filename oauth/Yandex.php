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
 * Авторизация с помощью Яндекса
 * Class Yandex
 */
class Yandex extends \yii\authclient\clients\YandexOAuth
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
            'female' => $this->female,
            null => null
        ];
    }

    /**
     * Получение аттрибутов
     * @return array
     * @throws \yii\base\Exception
     */
    protected function initUserAttributes()
    {
        $attributes =  $this->api('info', 'GET');

        $return_attributes = [
            'User' => [
                $this->email        => $attributes['emails'][0],
                $this->first_name   => $attributes['first_name'],
                $this->last_name    => $attributes['last_name'],
                $this->avatar       => 'https://avatars.yandex.net/get-yapic/' . $attributes['default_avatar_id'] . '/islands-200',
                $this->gender       => $this->normalizeSex()[$attributes['sex']],
                $this->status       => $this->statusActive,
            ],
            'provider_user_id' => $attributes['id'],
            'provider_id' => UserOauthKey::getAvailableClients()['yandex'],
            'page' => null,
        ];

        return $return_attributes;
    }
}
