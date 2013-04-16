<?php
/*
 * Plugin Name: Call Statistics
 * Plugin URI: https://github.com/zhangxiao/call-statistics
 * Description: Collect calls information. 
 * Version: 1.0
 * Author: x1a0
 * Author URI: https://github.com/zhangxiao
 * */
?>
<?php

if (!class_exists("Call_Stats")) {

    class Call_Stats {
        private static $messages;

        public $_name;
        public $page_title;
        public $page_name;
        public $page_id;
        public $template_file_name; 
        public $calls_table_name;
        public $call_topic_table_name;

        public function __construct() {
            self::$messages = array();

            global $wpdb;

            $this->_name = "call-statistics";
            $this->page_title = "Call Statistics";
            $this->page_name = $this->_name;
            $this->page_id = get_option("{$this->_name}_page_id");
            $this->template_file_name = "page-{$this->_name}.php";
            $this->calls_table_name = $wpdb->prefix . "cs_calls";
            $this->call_topic_table_name = $wpdb->prefix . "cs_call_topic";

            register_activation_hook  (__FILE__, array($this, "onActivate"));
            register_deactivation_hook(__FILE__, array($this, "onDeactivate"));
            //register_uninstall_hook   (__FILE__, array($this, "onUninstall"));
            add_action("init", array($this, "onInit"));
            add_action("wp_enqueue_scripts", array($this, "onWpEnqueueScripts"));

            // for debug
            //add_action("activated_plugin", array($this, "onActivatedPlugin"));
        }

        public static function addMessage($message, $level = "info") {
            self::$messages[$level][] = $message;
        }

        public static function getMessages() {
            return self::$messages;
        }

        /* Event Handlers */

        public function onActivate() {
            $this->resetOptions();
            $the_page = get_page_by_title($this->page_title);

            if (!$the_page) {
                $_p = array(
                    "post_title"     => $this->page_title,
                    "post_name"      => $this->page_name,
                    "post_content"   => "",
                    "post_status"    => "publish",
                    "post_type"      => "page",
                    "comment_status" => "closed",
                    "ping_status"    => "closed",
                    "post_category"  => array(1), // the default "Uncatrgorised"
                );

                $this->page_id = wp_insert_post($_p);
            }
            else {
                // the plugin may have been previously active and the page may just be trashed...
                $this->page_id = $the_page->ID;

                // make sure the page is not trashed...
                $the_page->post_status = "publish";
                $this->page_id = wp_update_post($the_page);
            }

            delete_option("{$this->_name}_page_id");
            add_option("{$this->_name}_page_id", $this->page_id);

            $this->copyTemplateFile();
            $this->createTable();
        }

        public function onDeactivate() {
            $this->deletePage();
            $this->clearOptions();
        }

        public function onUninstall() {
            $this->deletePage(TRUE);
            $this->clearOptions();
        }

        public function onInit() {
            if (!isset($_POST["call_statistics_post"]) || $_POST["call_statistics_post"] != 1) {
                return;
            }

            $call = $this->validateCallData();
            if ($call === FALSE) return;

            global $wpdb;
            $rows_affected = $wpdb->insert($this->calls_table_name, $call);
            if (isset($_POST["topic"]) && is_array($_POST["topic"])) {
                $call_id = $wpdb->insert_id;
                foreach ($_POST["topic"] as $topic) {
                    $wpdb->insert($this->call_topic_table_name, array(
                        "call_id" => $call_id,
                        "topic" => sanitize_text_field($topic),
                    ));
                }
            }
            self::addMessage("Thanks! Call record is saved.", "success");
        }

        public function onWpEnqueueScripts() {
            // Respects SSL, Style.css is relative to the current file
            $name = $this->_name . "-style";
            wp_register_style($name, plugins_url('style.css', __FILE__));
            wp_enqueue_style($name);
        }

        /* end of event handlers */

        /**
         * For debug.
         */
        public function onActivatedPlugin() {
            file_put_contents("/tmp/{$this->_name}.txt", ob_get_contents());
        }

        /**
         * Generate statistics.
         */
        public function getStatsHTML() {
            $output = "";
            global $wpdb;

            $stats_types = array(
                "personal_id" => "Personliga Identifieringskod",
                "platform" => "Plattform",
                "type" => "Typ av samtal",
                "gender" => "Kön",
                "spouse" => "Åldersgrupp",
            );

            foreach ($stats_types as $field => $display) {
                $sql = "SELECT $field, count(1) AS total FROM $this->calls_table_name GROUP BY $field";
                $result = $wpdb->get_results($sql);
                $header = array($display, "#");
                $rows = array();
                foreach ($result as $item) {
                    $rows[] = array($item->{$field}, $item->total);
                }
                $output .= $this->getTableHTML($header, $rows);
            }

            $sql = "SELECT topic, count(1) AS total FROM $this->call_topic_table_name GROUP BY topic";
            $result = $wpdb->get_results($sql);
            $header = array("Samtalsämne", "#");
            $rows = array();
            foreach ($result as $item) {
                $rows[] = array($item->topic, $item->total);
            }
            $output .= $this->getTableHTML($header, $rows);

            return $output;
        }

        /**
         * Validate data in $_POST and return sanitized data.
         */
        private function validateCallData() {
            $call = array(
                "personal_id"    => sanitize_text_field($_POST["personal_id"]),
                "platform"       => sanitize_text_field($_POST["platform"]),
                "type"           => sanitize_text_field($_POST["type"]),
                "minutes"        => sanitize_text_field(intval($_POST["minutes"])),
                "gender"         => sanitize_text_field($_POST["gender"]),
                "spouse"         => sanitize_text_field($_POST["spouse"]),
                "other_category" => sanitize_text_field($_POST["other_category"]),
                "reference"      => sanitize_text_field($_POST["reference"]),
                "report"         => sanitize_text_field($_POST["report"]),
                "response"       => sanitize_text_field($_POST["response"]),
            );

            $relative_fields = array_keys($call);
            $relative_fields[] = "topic";

            $invalid = FALSE;
            if (empty($call["personal_id"])) {
                self::addMessage("Please fill personliga identifieringskod", "error");
                $invalid = TRUE;
            }

            if ($invalid) return FALSE;

            // unset relative $_POST values
            foreach ($relative_fields as $field) {
                unset($_POST[$field]);
            }

            $call["created"] = time();
            return $call;
        }

        /**
         * Helper for generate table.
         */
        private function getTableHTML($header, $rows, $title = "") {
            $output = '<table class="stats table table-striped table-hover">';

            $output .= '<tr>';
            $col = 0;
            foreach ($header as $item) {
                $output .= '<th class="col-' . $col++ . '">' . $item . '</th>';
            }
            $output .= '</tr>';

            $odd_even = array("odd", "even");
            $count = 0;
            foreach ($rows as $row) {
                $output .= '<tr class="' . $odd_even[$count++ % 2] . '">';
                $col = 0;
                foreach ($row as $item) {
                    $output .= '<td class="col-' . $col++ . '">' . $item . '</td>';
                }
                $output .= '</tr>';
            }

            $output .= '</table>';

            if ($title) {
                $output = '<h2>' . $title . '</h2>' . $output;
            }

            return $output;
        }

        /**
         * Copy page template to current theme.
         * This is only called when the plugin is activated. If theme changes the template
         * needs to be copied manually to the new theme's folder.
         */
        private function copyTemplateFile() {
            $src = __DIR__ . "/" . $this->template_file_name;
            $dst = get_stylesheet_directory() . "/" . $this->template_file_name;

            if (!file_exists($src)) {
                return FALSE;
            }

            $data = file_get_contents($src);
            if (!$data) {
                return FALSE;
            }

            return file_put_contents($dst, $data);
        }

        private function deletePage($hard = FALSE) {
            $id = get_option("{$this->_name}_page_id");
            if ($id) {
                wp_delete_post($id, $hard);
            }
        }

        private function setOptions() {
            add_option("{$this->_name}_page_title" , $this->page_title , "" , "yes");
            add_option("{$this->_name}_page_name"  , $this->page_name  , "" , "yes");
            add_option("{$this->_name}_page_id"    , $this->page_id    , "" , "yes");

            // form options
            add_option("{$this->_name}_topic_options", array(
                "Kärleksrelationer",
                "Ångest",
                "Familjerelationer",
                "Psykisk och fysisk ohälsa",
                "Depression",
                "Sex",
                "Ensamhet",
                "Självmordstankar",
                "Kompisrelationer",
                "Övrigt",
                "Oro",
                "Skola",
                "Mår dåligt",
                "Sexuella övergrepp/våldtäkt",
                "Missbruk",
                "Graviditet/föräldraskap",
                "Filosofiska tankar",
                "Sorg",
                "Dålig självkänsla",
                "Misshandel/våld",
                "Mobbing",
                "Prestation",
                "Bristande stöd",
                "Ätstörningar",
                "Jobb",
                "Kroppen",
                "Allmänt prat",
                "Ilska",
                "Kriminalitet",
                "Ekonomiska problem",
                "Sömnproblem",
                "Framtiden",
                "HBTQ",
            ));
            add_option("{$this->_name}_platform_options", array("Telefon", "Chatt"));
            add_option("{$this->_name}_type_options", array("Seriöst samtal", "Vaneringare/Vanechattare", "Jourmissbrukare", "Test/Klick"));
            add_option("{$this->_name}_gender_options", array("Tjej", "Kille", "Vet ej"));
            add_option("{$this->_name}_spouse_options", array("0-6 år", "7-13 år (Grundskola åk 1-6)", "14-16 år (Grundskola åk 7-9)", "17-19 år (Gymnasiet)", "20-25 år"));
        }

        private function clearOptions() {
            delete_option("{$this->_name}_page_title");
            delete_option("{$this->_name}_page_name");
            delete_option("{$this->_name}_page_id");
        }

        private function resetOptions() {
            $this->clearOptions();
            $this->setOptions();
        }

        private function createTable() {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            // call table
            $sql = "CREATE TABLE {$this->calls_table_name} (
                id INT(10) NOT NULL AUTO_INCREMENT,
                personal_id VARCHAR(16) NOT NULL,
                platform VARCHAR(32) NOT NULL,
                type VARCHAR(32),
                minutes SMALLINT(5),
                gender VARCHAR(32),
                spouse VARCHAR(64),
                other_category TEXT,
                reference VARCHAR(255),
                report TEXT,
                response TEXT,
                created INT(10) UNSIGNED NOT NULL,
                UNIQUE KEY id (id)
            );";
            dbDelta($sql);

            // call_topic table
            $sql = "CREATE TABLE {$this->call_topic_table_name} (
                call_id INT(10) NOT NULL,
                topic VARCHAR(64) NOT NULL,
                UNIQUE KEY call_topic (call_id, topic)
            );";
            dbDelta($sql);
        }
    }
}

// kick off
global $call_statistics;
$call_statistics = new Call_Stats();
?>
