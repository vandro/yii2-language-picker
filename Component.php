<?php

namespace lajax\languagepicker;

use Yii;

/**
 * Component.
 * 
 * Examples:
 * 
 * Minimal code:
 * 
 * ~~~
 * 'language' => 'en',
 * 'bootstrap' => ['languagepicker'],
 * 'components' => [
 *      'languagepicker' => [
 *          'class' => 'lajax\languagepicker\Component',
 *          'languages' => ['en', 'de', 'fr']               // List of available languages
 *      ]
 * ],
 * ~~~
 * 
 * Complete example:
 * 
 * ~~~
 * 'language' => 'en-US',
 * 'bootstrap' => ['languagepicker'],
 * 'components' => [
 *      'languagepicker' => [
 *          'class' => 'lajax\languagepicker\Component',
 *          'languages' => ['en-US', 'de-DE', 'fr-FR'],     // List of available languages
 *          'cookieName' => 'language',                     // Name of the cookie.
 *          'expireDays' => 64,                             // The expiration time of the cookie is 64 days.
 *          'callback' => function() {
 *              if (!\Yii::$app->user->isGuest) {
 *                  $user = User::findOne(\Yii::$app->user->id);
 *                  $user->language = \Yii::$app->language;
 *                  $user->save();
 *              }
 *          }
 *      ]
 * ]
 * ~~~
 * 
 *
 * @author Lajos Molnar <lajax.m@gmail.com>
 * @since 1.0
 */
class Component extends \yii\base\Component {

    /**
     * @var function - function to execute after changing the language of the site.
     */
    public $callback;

    /**
     * @var integer expiration date of the cookie storing the language of the site.
     */
    public $expireDays = 30;

    /**
     * @var string Name of the cookie.
     */
    public $cookieName = 'language';

    /**
     * @var array List of available languages
     *  Formats supported in the pre-defined skins:
     * 
     * ~~~
     *  ['en', 'de', 'es']
     *  ['en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français']
     *  ['en-US', 'de-DE', 'fr-FR']
     *  ['en-US' => 'English', 'de-DE' => 'Deutsch', 'fr-FR' => 'Français']
     * ~~~
     */
    public $languages;

    /**
     * @inheritdoc
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($config = array()) {

        if (empty($config['languages'])) {
            throw new \yii\base\InvalidConfigException('Missing languages');
        } else if (is_callable($config['languages'])) {
            $config['languages'] = call_user_func($config['languages']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init() {

        $this->initLanguage();

        parent::init();
    }

    /**
     * Setting the language of the site.
     */
    public function initLanguage() {
        if (isset($_GET['language-picker'], $_GET['language'])) {
            if ($this->_isValidLanguage($_GET['language'])) {
                return $this->saveLanguage($_GET['language']);
            } else if (!Yii::$app->request->isAjax) {
                return $this->_redirect();
            }
        } else if (Yii::$app->request->cookies->has($this->cookieName)) {
            if ($this->_isValidLanguage(Yii::$app->request->cookies->getValue($this->cookieName))) {
                Yii::$app->language = Yii::$app->request->cookies->getValue($this->cookieName);
            } else {
                Yii::$app->response->cookies->remove($this->cookieName);
            }
        }
    }

    /**
     * Saving laguage into cookie and database.
     * @param string $language - The language to save.
     * @return static
     */
    public function saveLanguage($language) {

        Yii::$app->language = $language;
        $cookie = new \yii\web\Cookie([
            'name' => $this->cookieName,
            'value' => $language,
            'expire' => time() + 86400 * $this->expireDays
        ]);

        Yii::$app->response->cookies->add($cookie);

        if (is_callable($this->callback)) {
            call_user_func($this->callback);
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->end();
        }

        return $this->_redirect();
    }

    /**
     * Redirects the browser to the referer URL.
     * @return static
     */
    private function _redirect() {
        $redirect = Yii::$app->request->absoluteUrl == Yii::$app->request->referrer ? '/' : Yii::$app->request->referrer;
        return Yii::$app->response->redirect($redirect);
    }

    /**
     * Determines whether the language received as a parameter can be processed.
     * @param string $language
     * @return boolean
     */
    private function _isValidLanguage($language) {
        return is_string($language) && (isset($this->languages[$language]) || in_array($language, $this->languages));
    }

}
