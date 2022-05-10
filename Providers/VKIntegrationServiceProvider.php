<?php

namespace Modules\VKIntegration\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use VK\Client\VKApiClient;
use App\Customer;
use App\Thread;

define('VK_INTEGRATION', 'vkintegration');

class VKIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->hooks();
    }

    public function hooks() {
        // Add module's css file to the application layout
        \Eventy::addFilter('stylesheets', function($value) {
            array_push($value, '/modules/'.VK_INTEGRATION.'/css/style.css');
            return $value;
        }, 20, 1);

        // Add script with translated vars
        \Eventy::addFilter('javascripts', function($value) {
            array_push($value, '/modules/'.VK_INTEGRATION.'/js/vars.js');
            return $value;
        }, 20, 1);

        // Add routes script
        \Eventy::addFilter('javascripts', function($value) {
            array_push($value, '/modules/'.VK_INTEGRATION.'/js/laroute.js');
            return $value;
        }, 20, 1);

        // Add module's JS file to the application layout
        \Eventy::addFilter('javascripts', function($value) {
            array_push($value, '/modules/'.VK_INTEGRATION.'/js/main.js');
            return $value;
        }, 20, 1);

        // Send replies to VK on new message
        \Eventy::addAction('conversation.user_replied', function($conversation) {
            $last_customer_message = Thread::where('conversation_id', $conversation->id)
                ->where('state', Thread::STATE_PUBLISHED)
                ->where('type', Thread::TYPE_CUSTOMER)
                ->latest()
                ->first();

            if (!$last_customer_message || !$last_customer_message->getMeta('vk_integration')) return;

            $customer = Customer::where('id', $conversation->customer_id)->first();
            $vk_url = null;

            foreach ($customer->getSocialProfiles() as $profile) {
                if ($profile['type'] == Customer::SOCIAL_TYPE_VK) {
                    $vk_url = $profile['value'];
                    break;
                }
            }

            if ($vk_url == null) return;

            $user_reply = Thread::where('conversation_id', $conversation->id)
                ->where('state', Thread::STATE_PUBLISHED)
                ->where('type', Thread::TYPE_MESSAGE)
                ->latest()
                ->first();

            $options = \Option::getOptions(['vkintegration.access_token']);
            $vk = new VKApiClient();
            $vk->messages()->send($options['vkintegration.access_token'], [
                'peer_id' => intval(substr($vk_url, strlen('https://vk.com/id'))),
                'message' => $user_reply->getBodyAsText(),
                'random_id' => $user_reply->id
            ]);
        });

        // Add a badge to the subject line on the overview pages
        \Eventy::addAction('conversations_table.before_subject', function($conversation) {
            $last_customer_message = Thread::where('conversation_id', $conversation->id)
                ->where('state', Thread::STATE_PUBLISHED)
                ->where('type', Thread::TYPE_CUSTOMER)
                ->latest()
                ->first();

            if (!$last_customer_message || !$last_customer_message->getMeta('vk_integration')) return;
            echo '<span class="vk-integration-tag">ВКонтакте</span>';
        });

        // Add a badge to the subject line on the conversation page
        \Eventy::addAction('conversation.after_subject', function($conversation) {
            $last_customer_message = Thread::where('conversation_id', $conversation->id)
                ->where('state', Thread::STATE_PUBLISHED)
                ->where('type', Thread::TYPE_CUSTOMER)
                ->latest()
                ->first();

            if (!$last_customer_message || !$last_customer_message->getMeta('vk_integration')) return;
            echo '<span class="vk-integration-tag">ВКонтакте</span>';
        });

        // Add a module section to the settings page
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections['vkintegration'] = ['title' => __('VK Integration'), 'icon' => 'comment', 'order' => 400];
            return $sections;
        }, 30);

        // TODO tf this does exactly?
        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section != 'vkintegration') {
                return $settings;
            }

            $settings = \Option::getOptions([
                'vkintegration.access_token',
                'vkintegration.confirmation_code',
                'vkintegration.default_mailbox',
                'vkintegration.secret',
            ]);

            return $settings;
        }, 20, 2);

        // Init v8n rules for settings
        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section != 'vkintegration') {
                return $params;
            }

            $params = [
                'template_vars' => [],
                'validator_rules' => [
                    'settings.vkintegration\.access_token' => 'required',
                    'settings.vkintegration\.confirmation_code' => 'required',
                    'settings.vkintegration\.default_mailbox' => 'required',
                    'settings.vkintegration\.secret' => 'required'
                ]
            ];

            return $params;
        }, 20, 2);

        // Display settings
        \Eventy::addFilter('settings.view', function ($view, $section) {
            return $section == 'vkintegration' ? 'vkintegration::settings' : $view;
        }, 20, 2);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([__DIR__.'/../Config/config.php' => config_path('vkintegration.php')], 'config');
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'vkintegration');
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/vkintegration');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([$sourcePath => $viewPath],'views');
        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/vkintegration';
        }, \Config::get('view.paths')), [$sourcePath]), 'vkintegration');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/vkintegration');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'vkintegration');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'vkintegration');
        }
    }
}
