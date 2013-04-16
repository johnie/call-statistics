<?php

Class Call_Stats_Options {
    public $setup;

    public function __construct($setup) {
        $this->setup = $setup;

        if (is_admin()) {
            add_action('admin_menu', array($this, 'onAdminMenu'));
            add_action('admin_init', array($this, 'onAdminInit'));
        }
    }

    public function onAdminMenu() {
        add_options_page('Settings Admin', 'Call Statistics', 'manage_options', $this->setup->_name . '-setting-admin', array($this, 'createAdminPage'));
    }

    public function createAdminPage() {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Call Statistics Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields($this->setup->_name . '_option_group'); ?>
                <?php do_settings_sections($this->setup->_name . '-setting-admin'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function onAdminInit() {
        register_setting('call_statistics_option_group', $this->setup->_name, array($this, 'updateSettings'));

        add_settings_section(
            $this->setup->_name,
            'Form options',
            array($this, 'printSettingsSectionInfo'),
            $this->setup->_name . '-setting-admin'
        );

        add_settings_field(
            'topic_options',
            'Topic Options',
            array($this, 'printTopicOptionsField'),
            $this->setup->_name . '-setting-admin',
            $this->setup->_name
        );
    }

    public function updateSettings($input) {
        if (!empty($input['topic_options'])) {
            $key = $this->setup->_name . '_topic_options';
            $value = explode("\n", $input['topic_options']);
            $func = get_option($key) === FALSE ? 'add_option' : 'update_option';
            $func($key, $value);
        }
    }

    public function printSettingsSectionInfo() {
        print 'Enter settings below:';
    }

    public function printTopicOptionsField() {
        $options = get_option($this->setup->_name . '_topic_options');

        if ($options) {
            $value = implode("\n", $options);
        }
        else {
            $value = '';
        }

        print '<textarea name="' . $this->setup->_name . '[topic_options]" style="width: 100%; min-height: 150px;">' . $value . '</textarea>';
        print '<div class="desc">Put each option item in one line.</div>';
    }
}
