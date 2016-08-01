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
 * Авторизация через Facebook
 * Class Facebook
 */
class Facebook extends \yii\authclient\clients\Facebook
{
    public $email       = 'email';
    public $first_name  = 'first_name';
    public $last_name   = 'last_name';
    public $avatar      = 'avatar';

    public $gender      = 'gender';
    public $female      = 1;
    public $male        = 2;

    public $status          = 'status';
    public $statusWait      = 0;
    public $statusActive    = 1;

    /**
     * Размеры Popap-окна
     * @return array
     */
    public function getViewOptions()
    {
        return [
            'popupWidth' => 900,
            'popupHeight' => 600
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
     * Получение аттрибутов
     * @return array
     * @throws \yii\base\Exception
     */
    protected function initUserAttributes()
    {
        $attributes = $this->api('me', 'GET', [
            'fields' => implode(',', [
                'id',
                'email',
                'first_name',
                'last_name',
                'picture.height(200).width(200)',
                'gender'
            ]),
        ]);

        $return_attributes = [
            'User' => [
                $this->email        => $attributes['email'],
                $this->first_name   => $attributes['first_name'],
                $this->last_name    => $attributes['last_name'],
                $this->avatar       => $attributes['picture']['data']['url'],
                $this->gender       => $this->normalizeSex()[$attributes['gender']],
                $this->status       => $this->statusActive,
            ],
            'provider_user_id' => $attributes['id'],
            'provider_id' => UserOauthKey::getAvailableClients()['facebook'],
            'page' => $attributes['id'],
        ];

        return $return_attributes;
    }
}
