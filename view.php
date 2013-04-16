<?php

class Call_Stats_View {
    private $setup;

    public function __construct($setup) {
        $this->setup= $setup;
    }

    /**
     * Form for adding call record.
     */
    public function getFormHTML() {
        $html = '';
        $html .= '<form id="add-call" action="" method="post"><fieldset>';

        // personal id
        $html .= $this->getTextfield('personal_id', 'Fyll i din personliga identifieringskod:', TRUE);

        // platform
        $html .= $this->getSelect('platform', 'Plattform:', get_option($this->setup->_name . '_platform_options'), TRUE);

        // type
        $html .= $this->getSelect('type', 'Typ av samtal:', get_option($this->setup->_name . '_type_options'), TRUE);

        // minutes
        $html .= $this->getTextfield('minutes', 'Samtalstid i minuter:');

        // gender 
        $html .= $this->getSelect('gender', 'Kön:', get_option($this->setup->_name . '_gender_options'));

        // spouse
        $html .= $this->getSelect('spouse', 'Åldersgrupp:', get_option($this->setup->_name . '_spouse_options'));

        // topic
        $html .= $this->getCheckboxes('topic', 'Samtalsämne:', get_option($this->setup->_name . '_topic_options'));

        // other category
        $html .= $this->getTextarea('other_category', 'Annan samtalskategori:');

        // reference
        $html .= $this->getTextfield('reference', 'Hänvisning:');

        // report
        $html .= $this->getTextarea('report', 'Rapport om vane- eller jourmissbrukare:');

        // response
        $html .= $this->getTextarea('response', 'Hur bemötte du vane-eller jourmissbrukaren?');

        $html .= '<input name="' . $this->setup->_name . '_post" type="hidden" value="1">';
        $html .= '<input type="submit" value="Submit" class="btn btn-primary">';
        $html .= '</fieldset></form>';

        return $html;
    }

    /**
     * Generate statistics page.
     */
    public function getStatsHTML() {
        $html = "";
        global $wpdb;

        $stats_types = array(
            "personal_id" => "Personliga Identifieringskod",
            "platform" => "Plattform",
            "type" => "Typ av samtal",
            "gender" => "Kön",
            "spouse" => "Åldersgrupp",
        );

        foreach ($stats_types as $field => $display) {
            $sql = "SELECT $field, count(1) AS total FROM {$this->setup->calls_table_name} GROUP BY $field";
            $result = $wpdb->get_results($sql);
            $header = array($display, "#");
            $rows = array();
            foreach ($result as $item) {
                $rows[] = array($item->{$field}, $item->total);
            }
            $html .= $this->getTableHTML($header, $rows);
        }

        $sql = "SELECT topic, count(1) AS total FROM {$this->setup->call_topic_table_name} GROUP BY topic";
        $result = $wpdb->get_results($sql);
        $header = array("Samtalsämne", "#");
        $rows = array();
        foreach ($result as $item) {
            $rows[] = array($item->topic, $item->total);
        }
        $html .= $this->getTableHTML($header, $rows);

        return $html;
    }

    private function getTextfield($name, $label, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace($name, '_', '-');
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<input id="' . $id . '" type="text" name="' . $name . '" value="' . (isset($_POST[$name]) ? $_POST[$name] : '') . '">';
        return '<div>' . $html . '</div>';
    }

    private function getTextarea($name, $label, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace($name, '_', '-');
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<div><textarea id="' . $id . '" name="' . $name .'">' . (isset($_POST[$name]) ? $_POST[$name] : '') . '</textarea></div>';
        return '<div>' . $html . '</div>';
    }

    private function getSelect($name, $label, $options, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace($name, '_', '-');
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<select id="' . $id . '" name="' . $name .'">';
        foreach ($options as $option) {
            $html .= '<option value="' . $option . '" ' . selected(isset($_POST[$name]) ? $_POST[$name] : '', $option, TRUE) . '>' . $option . '</option>';
        }
        $html .= '</select>';
        return '<div>' . $html . '</div>';
    }

    private function getCheckboxes($name, $label, $options, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace($name, '_', '-');
        $html .= '<span>' . $label . '</span>' . ($required ? '<span class="required">*</span>' : '');
        foreach ($options as $index => $option) {
            $html .= '<label for="' . $id . '-' . $index . '">';
            $html .= '<input id="' . $id . '-' . $index . '" type="checkbox" name="' . $name . '[]" value="' . $option . '" ' . ((isset($_POST[$name]) && in_array($option, $_POST[$name])) ? 'checked="checked"' : '') . '> ' . $option;
            $html .= '</label>';
        }
        return '<div>' . $html . '</div>';
    }

    /**
     * Helper for generate table.
     */
    private function getTableHTML($header, $rows, $title = "") {
        $html = '<table class="stats table table-striped table-hover">';

        $html .= '<tr>';
        $col = 0;
        foreach ($header as $item) {
            $html .= '<th class="col-' . $col++ . '">' . $item . '</th>';
        }
        $html .= '</tr>';

        $odd_even = array("odd", "even");
        $count = 0;
        foreach ($rows as $row) {
            $html .= '<tr class="' . $odd_even[$count++ % 2] . '">';
            $col = 0;
            foreach ($row as $item) {
                $html .= '<td class="col-' . $col++ . '">' . $item . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        if ($title) {
            $html = '<h2>' . $title . '</h2>' . $html;
        }

        return $html;
    }
}
