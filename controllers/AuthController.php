<?php
/**
 * Created by PhpStorm.
 * User: phpNT - http://phpnt.com
 * Date: 04.07.2016
 * Time: 11:32
 */

namespace phpnt\oAuth\controllers;

use phpnt\oAuth\models\UserOauthKey;
use yii\authclient\OAuth2;
use yii\web\Controller;

/**
 * Авторизация и регстрация через
 * соц. сети. Прикрепление и открепление
 * ключей авторизации
 * 
 * Class AuthController
 */
class AuthController extends Controller
{
    public $modelUser;

    /**
     * Сохранение адреса для возврата
     * @param \yii\base\Action $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if ($action->id == 'index' && \Yii::$app->request->referrer !== null) {
            \Yii::$app->session->set('returnUrl', \Yii::$app->request->referrer);
        }
        return parent::beforeAction($action);
    }

    /**
     * Авторизация в социальной сети
     * @return array
     */
    public function actions()
    {
        return [
            'index' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'successCallback']
            ],
        ];
    }

    /**
     * Результат успешной авторизации с помощью социальной сети
     * @param $client - социальная сеть, через которую происходит авторизация
     * @return bool
     */
    public function successCallback($client)
    {
        $modelUser = $this->modelUser;
        /* @var $client OAuth2 */
        $attributes = $client->userAttributes;

        $this->action->successUrl = \Yii::$app->session->get('returnUrl');

        /** @var UserOauthKey $model */
        $model = UserOauthKey::findOne([
            'provider_id' => $attributes['provider_id'],
            'provider_user_id' => $attributes['provider_user_id']
        ]);

        if ($model) {
            // Ключ авторизации соц. сети найден в базе
            if (\Yii::$app->user->isGuest) {
                // Авторзириуемся если Гость
                $user = $model->getUser($modelUser);
                return \Yii::$app->user->login($model->getUser($user), 3600 * 24 * 30);
            } else {
                // Запрщаем авторизацию если не свой ключ
                if ($model->user_id != \Yii::$app->user->id) {
                    \Yii::$app->session->set(
                        'message',
                        [
                            'type'      => 'danger',
                            'icon'      => 'glyphicon glyphicon-warning-sign',
                            'message'   => \Yii::t('app', 'Данный ключ уже закреплен за другим пользователем сайта.'),
                        ]
                    );
                    return true;
                }
            }
        } else {
            // Текущего ключа авторизации соц. сети нет в базе
            if (\Yii::$app->user->isGuest) {
                $user = false;
                if ($attributes['User']['email'] != null) {
                    // Пытаемся найти пользователя в базе по почте из соц. сети
                    $user = $modelUser::findByEmail($attributes['User']['email']);
                }
                if (!$user) {
                    // Не найден пользователь с Email, создаем нового
                    $user = new $modelUser ();
                    $user->load($attributes);
                    if ($user->save() && $this->createKey($attributes, $user->id)) {
                        \Yii::$app->session->set(
                            'message',
                            [
                                'type'      => 'success',
                                'icon'      => 'glyphicon glyphicon-ok',
                                'message'   => \Yii::t('app', 'Авторизация прошла успешно.'),
                            ]
                        );
                    }
                    return (\Yii::$app->user->login($user, 3600 * 24 * 30));
                } else {
                    // Найден Email. Добавляем ключ и авторизируемся
                    return ($this->createKey($attributes, $user->id) && \Yii::$app->user->login($user, 3600 * 24 * 30));
                }

            } else {
                // Добавляем ключ для авторизированного пользователя
                $this->createKey($attributes, \Yii::$app->user->id);
                \Yii::$app->session->set(
                    'message',
                    [
                        'type'      => 'danger',
                        'icon'      => 'glyphicon glyphicon-ok',
                        'message'   => \Yii::t('app', 'Ключ входа успешно добавлен.'),
                    ]
                );
                return true;
            }
        }
        return true;
    }

    /**
     * Создание ключа авторизации соц. сети (привязывание)
     * @param $attributes - аттрибуты пользователя
     * @param $user_id - ID пользователя
     * @return bool
     */
    protected function createKey($attributes, $user_id)
    {
        $model = new UserOauthKey;
        $model->provider_id = $attributes['provider_id'];
        $model->provider_user_id = (string) $attributes['provider_user_id'];
        $model->page = (string) $attributes['page'];
        $model->user_id = $user_id;
        return $model->save();
    }

    /**
     * Удлаение ключа авторизации соц. сети (отвзяывание)
     * @param $id - ID ключа авторизации
     * @return \yii\web\Response
     */
    public function actionUnbind($id)
    {
        $modelUser = $this->modelUser;
        /** @var UserOauthKey $model */
        $model = UserOauthKey::findOne(['user_id' => \Yii::$app->user->id, 'provider_id' => UserOauthKey::getAvailableClients()[$id]]);
        if (!$model) {
            \Yii::$app->session->set(
                'message',
                [
                    'type'      => 'danger',
                    'icon'      => 'glyphicon glyphicon-warning-sign',
                    'message'   => \Yii::t('app', 'Ключ не найден'),
                ]
            );
        } else {
            /** @var User $user */
            $user = $modelUser::findOne($model->user_id);
            if ($user) {
                if (UserOauthKey::isOAuth($user->id) <= 1 && $user->email === null) {
                    \Yii::$app->session->set(
                        'message',
                        [
                            'type'      => 'danger',
                            'icon'      => 'glyphicon glyphicon-warning-sign',
                            'message'   => \Yii::t('app', 'Нельзя отвязать единственную соц. сеть, не заполнив Email'),
                        ]
                    );
                } elseif (UserOauthKey::isOAuth($user->id)<=1 && $user->password_hash === null) {
                    \Yii::$app->session->set(
                        'message',
                        [
                            'type'      => 'danger',
                            'icon'      => 'glyphicon glyphicon-warning-sign',
                            'message'   => \Yii::t('app', 'Нельзя отвязать единственную соц. сеть, не заполнив пароль'),
                        ]
                    );
                } else {
                    $model->delete();
                    \Yii::$app->session->set(
                        'message',
                        [
                            'type'      => 'danger',
                            'icon'      => 'glyphicon glyphicon-ok',
                            'message'   => \Yii::t('app', 'Ключ входа удален'),
                        ]
                    );
                }
            }
        }
        return $this->redirect(\Yii::$app->request->referrer);
    }
}
